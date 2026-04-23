<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Configuration\Reader;

use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Generated\Shared\Transfer\SynchronizationDataTransfer;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Service\Synchronization\SynchronizationServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConfig;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptorInterface;
use Spryker\Shared\Configuration\Reader\AbstractConfigurationValueResolver;
use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReaderInterface;

class ConfigurationStorageReader extends AbstractConfigurationValueResolver implements ConfigurationReaderInterface
{
    /**
     * @var array<string, mixed>
     */
    protected static array $storageDataCache = [];

    /**
     * @param array<\Spryker\Client\ConfigurationExtension\Dependency\Plugin\ConfigurationValueRequestExpanderPluginInterface> $valueRequestExpanderPlugins
     */
    public function __construct(
        ConfigurationConfig $sharedConfig,
        ConfigurationSchemaReaderInterface $schemaReader,
        ConfigurationValueEncryptorInterface $encryptor,
        protected StorageClientInterface $storageClient,
        protected SynchronizationServiceInterface $synchronizationService,
        protected array $valueRequestExpanderPlugins,
    ) {
        parent::__construct($sharedConfig, $schemaReader, $encryptor);
    }

    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed
    {
        $configurationValueRequestTransfer = $this->executeValueRequestExpanderPlugins($configurationValueRequestTransfer);

        return parent::getConfigurationValue($configurationValueRequestTransfer);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurationValues(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): array
    {
        $configurationValueRequestTransfer = $this->executeValueRequestExpanderPlugins($configurationValueRequestTransfer);

        return parent::getConfigurationValues($configurationValueRequestTransfer);
    }

    public function getStorageDataForScope(string $scope, ?string $scopeIdentifier = null): array
    {
        $storageKey = $this->generateStorageKey($scope, $scopeIdentifier);

        if (!isset(static::$storageDataCache[$storageKey])) {
            static::$storageDataCache[$storageKey] = $this->storageClient->get($storageKey);
        }

        $data = static::$storageDataCache[$storageKey];

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    protected function fetchRawValue(string $key, string $scope, ?string $scopeIdentifier = null): ?string
    {
        $data = $this->getStorageDataForScope($scope, $scopeIdentifier);

        return $data[$key] ?? null;
    }

    protected function executeValueRequestExpanderPlugins(
        ConfigurationValueRequestTransfer $configurationValueRequestTransfer,
    ): ConfigurationValueRequestTransfer {
        foreach ($this->valueRequestExpanderPlugins as $plugin) {
            $configurationValueRequestTransfer = $plugin->expand($configurationValueRequestTransfer);
        }

        return $configurationValueRequestTransfer;
    }

    protected function generateStorageKey(string $scope, ?string $scopeIdentifier): string
    {
        $referenceParts = [$scope];

        if ($scopeIdentifier !== null) {
            $referenceParts[] = $scopeIdentifier;
        }

        $synchronizationDataTransfer = (new SynchronizationDataTransfer())
            ->setReference(implode(ConfigurationConstants::STORAGE_KEY_SEPARATOR, $referenceParts));

        return $this->synchronizationService
            ->getStorageKeyBuilder(ConfigurationConstants::STORAGE_KEY_PREFIX)
            ->generateKey($synchronizationDataTransfer);
    }
}
