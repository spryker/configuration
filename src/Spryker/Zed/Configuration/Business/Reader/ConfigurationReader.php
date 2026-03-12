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

    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed
    {
        $configurationValueRequestTransfer = $this->executeValueRequestExpanderPlugins($configurationValueRequestTransfer);

        return parent::getConfigurationValue($configurationValueRequestTransfer);
    }

    protected function fetchRawValue(string $key, string $scope, ?string $scopeIdentifier = null): ?string
    {
        if ($this->config->isCacheEnabled()) {
            $cachedValue = $this->cacheManager->get($key, $scope, $scopeIdentifier);

            if ($cachedValue !== null) {
                return $cachedValue;
            }
        }

        $valueTransfer = $this->repository->findConfigurationValueByKeyAndScope($key, $scope, $scopeIdentifier);

        if (!$valueTransfer) {
            return null;
        }

        $value = $valueTransfer->getValue();
        $value = $this->decryptIfSecret($key, $value);

        if ($this->config->isCacheEnabled() && $value !== null) {
            $this->cacheManager->set($key, $scope, $scopeIdentifier, $value);
        }

        return $value;
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
