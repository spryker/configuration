<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Configuration;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;

class ConfigurationDependencyProvider extends AbstractDependencyProvider
{
    public const string CLIENT_STORAGE = 'CLIENT_STORAGE';

    public const string SERVICE_SYNCHRONIZATION = 'SERVICE_SYNCHRONIZATION';

    public const string PLUGINS_CONFIGURATION_VALUE_REQUEST_EXPANDER = 'PLUGINS_CONFIGURATION_VALUE_REQUEST_EXPANDER';

    public const string SERVICE_UTIL_ENCRYPTION = 'SERVICE_UTIL_ENCRYPTION';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addStorageClient($container);
        $container = $this->addSynchronizationService($container);
        $container = $this->addUtilEncryptionService($container);
        $container = $this->addConfigurationValueRequestExpanderPlugins($container);

        return $container;
    }

    protected function addSynchronizationService(Container $container): Container
    {
        $container->set(static::SERVICE_SYNCHRONIZATION, function (Container $container) {
            return $container->getLocator()->synchronization()->service();
        });

        return $container;
    }

    protected function addStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_STORAGE, function (Container $container) {
            return $container->getLocator()->storage()->client();
        });

        return $container;
    }

    protected function addConfigurationValueRequestExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_CONFIGURATION_VALUE_REQUEST_EXPANDER, function () {
            return $this->getConfigurationValueRequestExpanderPlugins();
        });

        return $container;
    }

    /**
     * Specification:
     * - Returns a stack of plugins that expand configuration value requests with scope context.
     * - Plugins add `ConfigurationScopeTransfer` objects (e.g. current store) to the request before scope resolution.
     *
     * @return array<\Spryker\Client\ConfigurationExtension\Dependency\Plugin\ConfigurationValueRequestExpanderPluginInterface>
     */
    protected function getConfigurationValueRequestExpanderPlugins(): array
    {
        return [];
    }

    protected function addUtilEncryptionService(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_ENCRYPTION, function (Container $container) {
            return $container->getLocator()->utilEncryption()->service();
        });

        return $container;
    }
}
