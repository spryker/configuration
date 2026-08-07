<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Reader;

use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptorInterface;
use Spryker\Shared\Configuration\Reader\AbstractConfigurationValueResolver;
use Spryker\Zed\Configuration\Business\Cache\ConfigurationCacheManagerInterface;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface;

class ConfigurationReader extends AbstractConfigurationValueResolver implements ConfigurationReaderInterface
{
    /**
     * @param array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationValueRequestExpanderPluginInterface> $valueRequestExpanderPlugins
     */
    public function __construct(
        protected ConfigurationRepositoryInterface $repository,
        protected ConfigurationConfig $config,
        protected ConfigurationCacheManagerInterface $cacheManager,
        ConfigurationValueEncryptorInterface $encryptor,
        ConfigurationSchemaProviderInterface $schemaProvider,
        protected array $valueRequestExpanderPlugins,
    ) {
        parent::__construct(
            $config->getSharedModuleConfig(),
            $schemaProvider,
            $encryptor,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed
    {
        $configurationValueRequestTransfer = $this->executeValueRequestExpanderPlugins($configurationValueRequestTransfer);

        return parent::getConfigurationValue($configurationValueRequestTransfer);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationValues(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): array
    {
        $configurationValueRequestTransfer = $this->executeValueRequestExpanderPlugins($configurationValueRequestTransfer);

        return parent::getConfigurationValues($configurationValueRequestTransfer);
    }

    protected function fetchRawValue(string $key, string $scope, ?string $scopeIdentifier = null): ?string
    {
        if (!$this->config->isCacheEnabled()) {
            return $this->decryptIfSecret($key, $this->findStoredValue($key, $scope, $scopeIdentifier));
        }

        $this->warmUpScopeCache($scope, $scopeIdentifier);

        return $this->decryptIfSecret($key, $this->cacheManager->get($key, $scope, $scopeIdentifier));
    }

    protected function warmUpScopeCache(string $scope, ?string $scopeIdentifier): void
    {
        if ($this->cacheManager->isScopeWarm($scope, $scopeIdentifier)) {
            return;
        }

        $configurationValueTransfers = $this->repository->findAllConfigurationValuesByScope($scope, $scopeIdentifier);

        $rawValues = [];

        foreach ($configurationValueTransfers as $settingKey => $configurationValueTransfer) {
            $rawValues[$settingKey] = $configurationValueTransfer->getValueOrFail();
        }

        $this->cacheManager->warmUpScope($scope, $scopeIdentifier, $rawValues);
    }

    protected function findStoredValue(string $key, string $scope, ?string $scopeIdentifier): ?string
    {
        return $this->repository->findConfigurationValueByKeyAndScope($key, $scope, $scopeIdentifier)?->getValue();
    }

    protected function executeValueRequestExpanderPlugins(
        ConfigurationValueRequestTransfer $configurationValueRequestTransfer,
    ): ConfigurationValueRequestTransfer {
        foreach ($this->valueRequestExpanderPlugins as $plugin) {
            $configurationValueRequestTransfer = $plugin->expand($configurationValueRequestTransfer);
        }

        return $configurationValueRequestTransfer;
    }
}
