<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Migrator;

use Generated\Shared\Transfer\ConfigurationSyncResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptorInterface;
use Spryker\Zed\Configuration\Business\Cache\ConfigurationCacheManagerInterface;
use Spryker\Zed\Configuration\Persistence\ConfigurationEntityManagerInterface;
use Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;

class ConfigurationSecretValueMigrator implements ConfigurationSecretValueMigratorInterface
{
    use TransactionTrait;

    protected const string WARNING_MESSAGE_ENCRYPTED = 'Setting "%s" (scope: %s) is secret, but its value was stored as plain text: the value is now encrypted. Rotate this credential, because it was stored unencrypted.';

    protected const string WARNING_MESSAGE_UNDECRYPTABLE = 'Setting "%s" (scope: %s) is secret, and its value looks encrypted, but it cannot be decrypted with the configured encryption key and initialization vector: the value is not changed. Check the encryption configuration or save the value again.';

    protected const string WARNING_MESSAGE_DECRYPTED = 'Setting "%s" (scope: %s) is not secret, but its value was stored encrypted: the value is now decrypted and stored as plain text.';

    protected const int CIPHER_BLOCK_SIZE = 16;

    public function __construct(
        protected ConfigurationRepositoryInterface $repository,
        protected ConfigurationEntityManagerInterface $entityManager,
        protected ConfigurationValueEncryptorInterface $encryptor,
        protected ConfigurationCacheManagerInterface $cacheManager,
    ) {
    }

    /**
     * @param array<string, array<string, mixed>> $settingsMap
     */
    public function migrate(array $settingsMap, ConfigurationSyncResponseTransfer $configurationSyncResponseTransfer): void
    {
        if ($settingsMap === []) {
            return;
        }

        $this->getTransactionHandler()->handleTransaction(function () use ($settingsMap, $configurationSyncResponseTransfer): void {
            $this->executeMigrateTransaction($settingsMap, $configurationSyncResponseTransfer);
        });
    }

    /**
     * @param array<string, array<string, mixed>> $settingsMap
     */
    protected function executeMigrateTransaction(array $settingsMap, ConfigurationSyncResponseTransfer $configurationSyncResponseTransfer): void
    {
        foreach ($this->repository->getConfigurationValuesBySettingKeys(array_keys($settingsMap)) as $configurationValueTransfer) {
            $this->migrateConfigurationValue(
                $configurationValueTransfer,
                !empty($settingsMap[$configurationValueTransfer->getSettingKeyOrFail()][ConfigurationSchemaConstants::SCHEMA_KEY_SECRET]),
                $configurationSyncResponseTransfer,
            );
        }
    }

    protected function migrateConfigurationValue(
        ConfigurationValueTransfer $configurationValueTransfer,
        bool $isSecret,
        ConfigurationSyncResponseTransfer $configurationSyncResponseTransfer,
    ): void {
        $value = (string)$configurationValueTransfer->getValue();

        if ($value === '' || $this->isEncrypted($value) === $isSecret) {
            return;
        }

        if ($isSecret && $this->hasCiphertextFormat($value)) {
            $configurationSyncResponseTransfer->addWarningMessage(
                $this->createWarningMessage(static::WARNING_MESSAGE_UNDECRYPTABLE, $configurationValueTransfer),
            );

            return;
        }

        $configurationValueTransfer->setValue($isSecret ? $this->encryptor->encrypt($value) : $this->encryptor->decrypt($value));
        $this->saveConfigurationValue($configurationValueTransfer);

        $configurationSyncResponseTransfer->addWarningMessage(
            $this->createWarningMessage($isSecret ? static::WARNING_MESSAGE_ENCRYPTED : static::WARNING_MESSAGE_DECRYPTED, $configurationValueTransfer),
        );
    }

    protected function isEncrypted(string $value): bool
    {
        $decryptedValue = $this->encryptor->decrypt($value);

        return $decryptedValue !== '' && $this->encryptor->encrypt($decryptedValue) === $value;
    }

    protected function hasCiphertextFormat(string $value): bool
    {
        // The encryptor stores base64(base64(cipher)); a value of this shape is treated as encrypted with another key and is never re-encrypted.
        $innerValue = base64_decode($value, true);

        if ($innerValue === false) {
            return false;
        }

        $cipherBytes = base64_decode($innerValue, true);

        return $cipherBytes !== false && $cipherBytes !== '' && strlen($cipherBytes) % static::CIPHER_BLOCK_SIZE === 0;
    }

    protected function saveConfigurationValue(ConfigurationValueTransfer $configurationValueTransfer): void
    {
        $this->entityManager->saveConfigurationValue($configurationValueTransfer);

        $this->cacheManager->invalidate(
            $configurationValueTransfer->getSettingKeyOrFail(),
            $configurationValueTransfer->getScopeOrFail(),
            $configurationValueTransfer->getScopeIdentifier(),
        );
    }

    protected function createWarningMessage(string $messageTemplate, ConfigurationValueTransfer $configurationValueTransfer): string
    {
        $scope = $configurationValueTransfer->getScopeOrFail();

        if ($configurationValueTransfer->getScopeIdentifier() !== null) {
            $scope = sprintf('%s %s', $scope, $configurationValueTransfer->getScopeIdentifier());
        }

        return sprintf($messageTemplate, $configurationValueTransfer->getSettingKeyOrFail(), $scope);
    }
}
