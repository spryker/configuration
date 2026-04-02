<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration;

use Spryker\Service\FileSystem\FileSystemServiceInterface;
use Spryker\Zed\FileManager\Business\FileManagerFacadeInterface;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

class ConfigurationDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string PLUGINS_SCOPE_IDENTIFIER_PROVIDER = 'PLUGINS_SCOPE_IDENTIFIER_PROVIDER';

    public const string PLUGINS_CONFIGURATION_VALUE_PRE_SAVE = 'PLUGINS_CONFIGURATION_VALUE_PRE_SAVE';

    public const string PLUGINS_CONFIGURATION_VALUE_POST_SAVE = 'PLUGINS_CONFIGURATION_VALUE_POST_SAVE';

    public const string PLUGINS_CONFIGURATION_VALUE_REQUEST_EXPANDER = 'PLUGINS_CONFIGURATION_VALUE_REQUEST_EXPANDER';

    public const string SERVICE_UTIL_ENCRYPTION = 'SERVICE_UTIL_ENCRYPTION';

    public const string CLIENT_CONFIGURATION = 'CLIENT_CONFIGURATION';

    public const string FACADE_ACL = 'FACADE_ACL';

    public const string FACADE_FILE_MANAGER = 'FACADE_FILE_MANAGER';

    public const string FACADE_TRANSLATOR = 'FACADE_TRANSLATOR';

    public const string SERVICE_FILE_SYSTEM = 'SERVICE_FILE_SYSTEM';

    public const string SERVICE_UTIL_SANITIZE_XSS = 'SERVICE_UTIL_SANITIZE_XSS';

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addConfigurationClient($container);
        $container = $this->addAclFacade($container);
        $container = $this->addTranslatorFacade($container);

        return $container;
    }

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addScopeIdentifierProviderPlugins($container);
        $container = $this->addConfigurationValuePreSavePlugins($container);
        $container = $this->addConfigurationValuePostSavePlugins($container);
        $container = $this->addConfigurationValueRequestExpanderPlugins($container);
        $container = $this->addUtilEncryptionService($container);
        $container = $this->addFileManagerFacade($container);
        $container = $this->addFlysystemService($container);
        $container = $this->addUtilSanitizeXssService($container);

        return $container;
    }

    protected function addScopeIdentifierProviderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_SCOPE_IDENTIFIER_PROVIDER, function () {
            return $this->getScopeIdentifierProviderPlugins();
        });

        return $container;
    }

    protected function addConfigurationValuePreSavePlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_CONFIGURATION_VALUE_PRE_SAVE, function () {
            return $this->getConfigurationValuePreSavePlugins();
        });

        return $container;
    }

    protected function addConfigurationValuePostSavePlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_CONFIGURATION_VALUE_POST_SAVE, function () {
            return $this->getConfigurationValuePostSavePlugins();
        });

        return $container;
    }

    /**
     * Specification:
     * - Returns a stack of plugins that provide scope identifiers for the Backoffice scope selector.
     * - Each plugin handles one scope type (e.g. store, locale) and returns its available identifiers.
     *
     * @return array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationScopeIdentifierProviderPluginInterface>
     */
    protected function getScopeIdentifierProviderPlugins(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns a stack of plugins executed before configuration values are validated and persisted.
     * - Plugins can modify the request transfer (e.g. sanitize values, normalize data).
     *
     * @return array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationValuePreSavePluginInterface>
     */
    protected function getConfigurationValuePreSavePlugins(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns a stack of plugins executed after configuration values have been persisted.
     * - Plugins can enrich the response or trigger side effects (e.g. cache invalidation, notifications).
     *
     * @return array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationValuePostSavePluginInterface>
     */
    protected function getConfigurationValuePostSavePlugins(): array
    {
        return [];
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
     * @return array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationValueRequestExpanderPluginInterface>
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

    protected function addConfigurationClient(Container $container): Container
    {
        $container->set(static::CLIENT_CONFIGURATION, function (Container $container) {
            return $container->getLocator()->configuration()->client();
        });

        return $container;
    }

    protected function addAclFacade(Container $container): Container
    {
        $container->set(static::FACADE_ACL, function (Container $container) {
            return $container->getLocator()->acl()->facade();
        });

        return $container;
    }

    protected function addFileManagerFacade(Container $container): Container
    {
        $container->set(static::FACADE_FILE_MANAGER, function (Container $container): FileManagerFacadeInterface {
            return $container->getLocator()->fileManager()->facade();
        });

        return $container;
    }

    protected function addTranslatorFacade(Container $container): Container
    {
        $container->set(static::FACADE_TRANSLATOR, function (Container $container) {
            return $container->getLocator()->translator()->facade();
        });

        return $container;
    }

    protected function addFlysystemService(Container $container): Container
    {
        $container->set(static::SERVICE_FILE_SYSTEM, function (Container $container): FileSystemServiceInterface {
            return $container->getLocator()->fileSystem()->service();
        });

        return $container;
    }

    protected function addUtilSanitizeXssService(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_SANITIZE_XSS, function (Container $container) {
            return $container->getLocator()->utilSanitizeXss()->service();
        });

        return $container;
    }
}
