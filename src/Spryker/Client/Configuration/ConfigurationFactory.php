<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Configuration;

use Spryker\Client\Configuration\Dependency\Facade\ConfigurationFacadeBridge;
use Spryker\Client\Configuration\Reader\ConfigurationReaderInterface;
use Spryker\Client\Configuration\Reader\ConfigurationReaderResolver;
use Spryker\Client\Configuration\Reader\ConfigurationStorageReader;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Service\Synchronization\SynchronizationServiceInterface;
use Spryker\Service\UtilEncryption\UtilEncryptionServiceInterface;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptor;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptorInterface;
use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReader;
use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReaderInterface;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;

/**
 * @method \Spryker\Client\Configuration\ConfigurationConfig getConfig()
 */
class ConfigurationFactory extends AbstractFactory
{
    public function createConfigurationReaderResolver(): ConfigurationReaderInterface
    {
        return new ConfigurationReaderResolver(
            $this->createConfigurationStorageReader(),
            $this->createConfigurationFacadeReader(),
            $this->getIsConfigurationServiceProvided(),
        );
    }

    public function createConfigurationFacadeReader(): ConfigurationReaderInterface
    {
        return new ConfigurationFacadeBridge(
            $this->getConfigurationService(),
        );
    }

    public function createConfigurationStorageReader(): ConfigurationReaderInterface
    {
        return new ConfigurationStorageReader(
            $this->getConfig()->getSharedModuleConfig(),
            $this->createConfigurationSchemaReader(),
            $this->createConfigurationValueEncryptor(),
            $this->getStorageClient(),
            $this->getSynchronizationService(),
            $this->getConfigurationValueRequestExpanderPlugins(),
        );
    }

    public function getIsConfigurationServiceProvided(): bool
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::IS_CONFIGURATION_SERVICE_PROVIDED);
    }

    public function getConfigurationService(): ?ConfigurationFacadeInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::SERVICE_CONFIGURATION);
    }

    /**
     * @return array<\Spryker\Client\ConfigurationExtension\Dependency\Plugin\ConfigurationValueRequestExpanderPluginInterface>
     */
    public function getConfigurationValueRequestExpanderPlugins(): array
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::PLUGINS_CONFIGURATION_VALUE_REQUEST_EXPANDER);
    }

    public function createConfigurationValueEncryptor(): ConfigurationValueEncryptorInterface
    {
        return new ConfigurationValueEncryptor(
            $this->getUtilEncryptionService(),
            $this->getConfig()->getSharedModuleConfig(),
        );
    }

    public function createConfigurationSchemaReader(): ConfigurationSchemaReaderInterface
    {
        return new ConfigurationSchemaReader($this->getConfig()->getSharedModuleConfig());
    }

    public function getUtilEncryptionService(): UtilEncryptionServiceInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION);
    }

    public function getSynchronizationService(): SynchronizationServiceInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::SERVICE_SYNCHRONIZATION);
    }

    public function getStorageClient(): StorageClientInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::CLIENT_STORAGE);
    }
}
