<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Writer;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptorInterface;
use Spryker\Zed\Configuration\Business\Cache\ConfigurationCacheManagerInterface;
use Spryker\Zed\Configuration\Business\Logger\ConfigurationAuditLoggerInterface;
use Spryker\Zed\Configuration\Business\Sanitizer\ConfigurationValueSanitizerInterface;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;
use Spryker\Zed\Configuration\Business\Validator\ConfigurationValueValidatorInterface;
use Spryker\Zed\Configuration\Persistence\ConfigurationEntityManagerInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;

class ConfigurationValueWriter implements ConfigurationValueWriterInterface
{
    use TransactionTrait;

    /**
     * @param array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationValuePreSavePluginInterface> $preSavePlugins
     * @param array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationValuePostSavePluginInterface> $postSavePlugins
     */
    public function __construct(
        protected ConfigurationEntityManagerInterface $entityManager,
        protected ConfigurationCacheManagerInterface $cacheManager,
        protected ConfigurationValueValidatorInterface $validator,
        protected ConfigurationValueEncryptorInterface $encryptor,
        protected ConfigurationSchemaProviderInterface $schemaProvider,
        protected ConfigurationAuditLoggerInterface $auditLogger,
        protected array $preSavePlugins,
        protected array $postSavePlugins,
        protected ConfigurationValueSanitizerInterface $sanitizer,
    ) {
    }

    public function saveConfigurationValues(
        ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer,
    ): ConfigurationValueCollectionResponseTransfer {
        return $this->getTransactionHandler()->handleTransaction(function () use ($configurationValueCollectionRequestTransfer) {
            return $this->executeSaveConfigurationValuesTransaction($configurationValueCollectionRequestTransfer);
        });
    }

    protected function executeSaveConfigurationValuesTransaction(
        ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer,
    ): ConfigurationValueCollectionResponseTransfer {
        $configurationValueCollectionRequestTransfer = $this->executePreSavePlugins($configurationValueCollectionRequestTransfer);

        $errors = [];

        foreach ($configurationValueCollectionRequestTransfer->getConfigurationValues() as $configurationValueTransfer) {
            if ($this->sanitizer->isSanitizeXssEnabled($configurationValueTransfer->getSettingKeyOrFail())) {
                $this->sanitizer->sanitize($configurationValueTransfer);
            }

            $validationResponse = $this->validator->validate($configurationValueTransfer);

            if (!$validationResponse->getIsValid()) {
                $errors = array_merge($errors, (array)$validationResponse->getErrors());
            }
        }

        if ($errors !== []) {
            $this->auditLogger->logConfigurationValueSaveFailed($configurationValueCollectionRequestTransfer, $errors);

            return $this->buildResponse(0, $errors);
        }

        $savedCount = 0;

        foreach ($configurationValueCollectionRequestTransfer->getConfigurationValues() as $configurationValueTransfer) {
            $this->encryptIfSecret($configurationValueTransfer);
            $this->entityManager->saveConfigurationValue($configurationValueTransfer);
            $this->invalidateCache($configurationValueTransfer);
            $this->auditLogger->logConfigurationValueSaved($configurationValueTransfer);

            $savedCount++;
        }

        $savedCount = $this->deleteConfigurationValues($configurationValueCollectionRequestTransfer, $savedCount);

        return $this->executePostSavePlugins($this->buildResponse($savedCount, []));
    }

    /**
     * @param array<\Generated\Shared\Transfer\ConfigurationErrorTransfer> $errors
     */
    protected function buildResponse(int $savedCount, array $errors): ConfigurationValueCollectionResponseTransfer
    {
        $response = (new ConfigurationValueCollectionResponseTransfer())
            ->setIsSuccess($errors === [])
            ->setSavedCount($savedCount);

        foreach ($errors as $error) {
            $response->addError($error);
        }

        return $response;
    }

    protected function encryptIfSecret(ConfigurationValueTransfer $configurationValueTransfer): void
    {
        $settingKey = $configurationValueTransfer->getSettingKeyOrFail();
        $settingsMap = $this->schemaProvider->getSettingsMap();

        if (!isset($settingsMap[$settingKey]) || empty($settingsMap[$settingKey][ConfigurationSchemaConstants::SCHEMA_KEY_SECRET])) {
            return;
        }

        $value = $configurationValueTransfer->getValue();

        if ($value === null || $value === '') {
            return;
        }

        $configurationValueTransfer->setValue(
            $this->encryptor->encrypt($value),
        );
    }

    protected function executePreSavePlugins(
        ConfigurationValueCollectionRequestTransfer $requestTransfer,
    ): ConfigurationValueCollectionRequestTransfer {
        foreach ($this->preSavePlugins as $plugin) {
            $requestTransfer = $plugin->preSave($requestTransfer);
        }

        return $requestTransfer;
    }

    protected function executePostSavePlugins(
        ConfigurationValueCollectionResponseTransfer $responseTransfer,
    ): ConfigurationValueCollectionResponseTransfer {
        foreach ($this->postSavePlugins as $plugin) {
            $responseTransfer = $plugin->postSave($responseTransfer);
        }

        return $responseTransfer;
    }

    protected function invalidateCache(ConfigurationValueTransfer $configurationValueTransfer): void
    {
        $this->cacheManager->invalidate(
            $configurationValueTransfer->getSettingKeyOrFail(),
            $configurationValueTransfer->getScopeOrFail(),
            $configurationValueTransfer->getScopeIdentifier(),
        );
    }

    protected function deleteConfigurationValues(ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer, int $savedCount): int
    {
        foreach ($configurationValueCollectionRequestTransfer->getDeletionKeys() as $deletionTransfer) {
            $this->entityManager->deleteConfigurationValue(
                $deletionTransfer->getSettingKeyOrFail(),
                $deletionTransfer->getScopeOrFail(),
                $deletionTransfer->getScopeIdentifier(),
            );

            $this->cacheManager->invalidate(
                $deletionTransfer->getSettingKeyOrFail(),
                $deletionTransfer->getScopeOrFail(),
                $deletionTransfer->getScopeIdentifier(),
            );

            $savedCount++;
        }

        return $savedCount;
    }
}
