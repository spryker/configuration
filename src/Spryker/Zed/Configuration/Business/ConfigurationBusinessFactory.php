<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business;

use Spryker\Service\FileSystem\FileSystemServiceInterface;
use Spryker\Service\UtilEncryption\UtilEncryptionServiceInterface;
use Spryker\Service\UtilSanitizeXss\UtilSanitizeXssServiceInterface;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptor;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptorInterface;
use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReader;
use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReaderInterface;
use Spryker\Shared\Configuration\Schema\SchemaMerger;
use Spryker\Shared\Configuration\Schema\SchemaParser;
use Spryker\Zed\Configuration\Business\Cache\ConfigurationCacheManager;
use Spryker\Zed\Configuration\Business\Cache\ConfigurationCacheManagerInterface;
use Spryker\Zed\Configuration\Business\Collector\ConfigurationValuesCollector;
use Spryker\Zed\Configuration\Business\Collector\ConfigurationValuesCollectorInterface;
use Spryker\Zed\Configuration\Business\Creator\ConfigurationFileUploadCreator;
use Spryker\Zed\Configuration\Business\Creator\ConfigurationFileUploadCreatorInterface;
use Spryker\Zed\Configuration\Business\Reader\ConfigurationReader;
use Spryker\Zed\Configuration\Business\Reader\ConfigurationReaderInterface;
use Spryker\Zed\Configuration\Business\Resolver\ConfigurationScopeIdentifierResolver;
use Spryker\Zed\Configuration\Business\Resolver\ConfigurationScopeIdentifierResolverInterface;
use Spryker\Zed\Configuration\Business\Sanitizer\ConfigurationValueSanitizer;
use Spryker\Zed\Configuration\Business\Sanitizer\ConfigurationValueSanitizerInterface;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProvider;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaSettingsMapper;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaSettingsMapperInterface;
use Spryker\Zed\Configuration\Business\Storage\ConfigurationStorageWriter;
use Spryker\Zed\Configuration\Business\Storage\ConfigurationStorageWriterInterface;
use Spryker\Zed\Configuration\Business\Sync\ConfigurationSchemaLoader;
use Spryker\Zed\Configuration\Business\Sync\ConfigurationSchemaLoaderInterface;
use Spryker\Zed\Configuration\Business\Sync\ConfigurationSchemaMerger;
use Spryker\Zed\Configuration\Business\Sync\ConfigurationSchemaMergerInterface;
use Spryker\Zed\Configuration\Business\Sync\ConfigurationSchemaSync;
use Spryker\Zed\Configuration\Business\Sync\ConfigurationSchemaSyncInterface;
use Spryker\Zed\Configuration\Business\Validator\ConfigurationConstraintMapper;
use Spryker\Zed\Configuration\Business\Validator\ConfigurationConstraintMapperInterface;
use Spryker\Zed\Configuration\Business\Validator\ConfigurationValueValidator;
use Spryker\Zed\Configuration\Business\Validator\ConfigurationValueValidatorInterface;
use Spryker\Zed\Configuration\Business\Writer\ConfigurationValueWriter;
use Spryker\Zed\Configuration\Business\Writer\ConfigurationValueWriterInterface;
use Spryker\Zed\Configuration\ConfigurationDependencyProvider;
use Spryker\Zed\FileManager\Business\FileManagerFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Spryker\Zed\Configuration\ConfigurationConfig getConfig()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface getRepository()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationEntityManagerInterface getEntityManager()
 */
class ConfigurationBusinessFactory extends AbstractBusinessFactory
{
    public function createConfigurationSchemaSync(): ConfigurationSchemaSyncInterface
    {
        return new ConfigurationSchemaSync(
            $this->createConfigurationSchemaLoader(),
            $this->createConfigurationSchemaMerger(),
            $this->createSchemaParser(),
            $this->createConfigurationSchemaSettingsMapper(),
            $this->getConfig(),
        );
    }

    public function createConfigurationSchemaSettingsMapper(): ConfigurationSchemaSettingsMapperInterface
    {
        return new ConfigurationSchemaSettingsMapper();
    }

    public function createConfigurationSchemaLoader(): ConfigurationSchemaLoaderInterface
    {
        return new ConfigurationSchemaLoader(
            $this->getConfig(),
        );
    }

    public function createConfigurationSchemaMerger(): ConfigurationSchemaMergerInterface
    {
        return new ConfigurationSchemaMerger(
            $this->createSchemaParser(),
            $this->createSchemaMerger(),
        );
    }

    public function createConfigurationReader(): ConfigurationReaderInterface
    {
        return new ConfigurationReader(
            $this->getRepository(),
            $this->getConfig(),
            $this->createConfigurationCacheManager(),
            $this->createConfigurationValueEncryptor(),
            $this->createConfigurationSchemaProvider(),
            $this->getConfigurationValueRequestExpanderPlugins(),
        );
    }

    public function createConfigurationCacheManager(): ConfigurationCacheManagerInterface
    {
        return new ConfigurationCacheManager();
    }

    public function createConfigurationValueWriter(): ConfigurationValueWriterInterface
    {
        return new ConfigurationValueWriter(
            $this->getEntityManager(),
            $this->createConfigurationCacheManager(),
            $this->createConfigurationValueValidator(),
            $this->createConfigurationValueEncryptor(),
            $this->createConfigurationSchemaProvider(),
            $this->getConfigurationValuePreSavePlugins(),
            $this->getConfigurationValuePostSavePlugins(),
            $this->createConfigurationValueSanitizer(),
        );
    }

    public function createConfigurationValueSanitizer(): ConfigurationValueSanitizerInterface
    {
        return new ConfigurationValueSanitizer(
            $this->createConfigurationSchemaProvider(),
            $this->getUtilSanitizeXssService(),
        );
    }

    public function getUtilSanitizeXssService(): UtilSanitizeXssServiceInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::SERVICE_UTIL_SANITIZE_XSS);
    }

    public function createConfigurationValueValidator(): ConfigurationValueValidatorInterface
    {
        return new ConfigurationValueValidator(
            $this->createConfigurationSchemaProvider(),
            $this->createConfigurationConstraintMapper(),
        );
    }

    public function createConfigurationConstraintMapper(): ConfigurationConstraintMapperInterface
    {
        return new ConfigurationConstraintMapper();
    }

    public function createConfigurationSchemaProvider(): ConfigurationSchemaProviderInterface
    {
        return new ConfigurationSchemaProvider(
            $this->createConfigurationSchemaReader(),
            $this->createConfigurationSchemaSettingsMapper(),
        );
    }

    public function createConfigurationSchemaReader(): ConfigurationSchemaReaderInterface
    {
        return new ConfigurationSchemaReader(
            $this->getConfig()->getSharedModuleConfig(),
        );
    }

    public function createConfigurationValuesCollector(): ConfigurationValuesCollectorInterface
    {
        return new ConfigurationValuesCollector(
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    public function createConfigurationScopeIdentifierResolver(): ConfigurationScopeIdentifierResolverInterface
    {
        return new ConfigurationScopeIdentifierResolver(
            $this->getScopeIdentifierProviderPlugins(),
        );
    }

    public function createConfigurationStorageWriter(): ConfigurationStorageWriterInterface
    {
        return new ConfigurationStorageWriter(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createConfigurationSchemaProvider(),
        );
    }

    /**
     * @return array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationScopeIdentifierProviderPluginInterface>
     */
    public function getScopeIdentifierProviderPlugins(): array
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::PLUGINS_SCOPE_IDENTIFIER_PROVIDER);
    }

    /**
     * @return array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationValuePreSavePluginInterface>
     */
    public function getConfigurationValuePreSavePlugins(): array
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::PLUGINS_CONFIGURATION_VALUE_PRE_SAVE);
    }

    /**
     * @return array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationValuePostSavePluginInterface>
     */
    public function getConfigurationValuePostSavePlugins(): array
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::PLUGINS_CONFIGURATION_VALUE_POST_SAVE);
    }

    /**
     * @return array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationValueRequestExpanderPluginInterface>
     */
    public function getConfigurationValueRequestExpanderPlugins(): array
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::PLUGINS_CONFIGURATION_VALUE_REQUEST_EXPANDER);
    }

    public function createConfigurationFileUploadCreator(): ConfigurationFileUploadCreatorInterface
    {
        return new ConfigurationFileUploadCreator(
            $this->getFileManagerFacade(),
            $this->getFileSystemService(),
        );
    }

    public function getFileSystemService(): FileSystemServiceInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::SERVICE_FILE_SYSTEM);
    }

    public function getFileManagerFacade(): FileManagerFacadeInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::FACADE_FILE_MANAGER);
    }

    public function createConfigurationValueEncryptor(): ConfigurationValueEncryptorInterface
    {
        return new ConfigurationValueEncryptor(
            $this->getUtilEncryptionService(),
            $this->getConfig()->getSharedModuleConfig(),
        );
    }

    public function getUtilEncryptionService(): UtilEncryptionServiceInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION);
    }

    public function createSchemaParser(): SchemaParser
    {
        return new SchemaParser();
    }

    public function createSchemaMerger(): SchemaMerger
    {
        return new SchemaMerger();
    }
}
