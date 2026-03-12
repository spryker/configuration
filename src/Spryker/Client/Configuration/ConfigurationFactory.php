<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Configuration;

use Spryker\Client\Configuration\Reader\ConfigurationStorageReader;
use Spryker\Client\Configuration\Reader\ConfigurationStorageReaderInterface;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Service\Synchronization\SynchronizationServiceInterface;
use Spryker\Service\UtilEncryption\UtilEncryptionServiceInterface;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptor;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptorInterface;
use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReader;
use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReaderInterface;

/**
 * @method \Spryker\Client\Configuration\ConfigurationConfig getConfig()
 */
class ConfigurationFactory extends AbstractFactory
{
    public function createConfigurationStorageReader(): ConfigurationStorageReaderInterface
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
