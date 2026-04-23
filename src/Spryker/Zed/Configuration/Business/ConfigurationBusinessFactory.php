<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business;

use Spryker\Service\FileSystem\FileSystemServiceInterface;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
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
use Spryker\Zed\Configuration\Business\DataImport\ConfigurationValueDataImportStep;
use Spryker\Zed\Configuration\Business\DataImport\Step\ConfigurationValueScopeIdentifierValidatorStep;
use Spryker\Zed\Configuration\Business\DataImport\Step\ConfigurationValueScopeValidatorStep;
use Spryker\Zed\Configuration\Business\DataImport\Step\ConfigurationValueSettingKeyValidatorStep;
use Spryker\Zed\Configuration\Business\Logger\ConfigurationAuditLogger;
use Spryker\Zed\Configuration\Business\Logger\ConfigurationAuditLoggerInterface;
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
use Spryker\Zed\Configuration\Business\Search\ConfigurationOverrideCollector;
use Spryker\Zed\Configuration\Business\Search\ConfigurationOverrideCollectorInterface;
use Spryker\Zed\Configuration\Business\Search\ConfigurationSchemaSearcher;
use Spryker\Zed\Configuration\Business\Search\ConfigurationSchemaSearcherInterface;
use Spryker\Zed\Configuration\Business\Search\ConfigurationUsageScanner;
use Spryker\Zed\Configuration\Business\Search\ConfigurationUsageScannerInterface;
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
use Spryker\Zed\DataImport\Business\DataImportFactoryTrait;
use Spryker\Zed\DataImport\Business\Model\DataImporterInterface;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetStepBroker;
use Spryker\Zed\FileManager\Business\FileManagerFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Translator\Business\TranslatorFacadeInterface;

/**
 * @method \Spryker\Zed\Configuration\ConfigurationConfig getConfig()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface getRepository()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationEntityManagerInterface getEntityManager()
 */
class ConfigurationBusinessFactory extends AbstractBusinessFactory
{
    use DataImportFactoryTrait;

    public function createConfigurationSchemaSync(): ConfigurationSchemaSyncInterface
    {
        return new ConfigurationSchemaSync(
            $this->createConfigurationSchemaLoader(),
            $this->createConfigurationSchemaMerger(),
            $this->createSchemaParser(),
            $this->createConfigurationSchemaSettingsMapper(),
            $this->getConfig(),
            $this->createConfigurationUsageScanner(),
            $this->getUtilEncodingService(),
        );
    }

    public function createConfigurationUsageScanner(): ConfigurationUsageScannerInterface
    {
        return new ConfigurationUsageScanner(
            $this->getConfig(),
            $this->createConfigurationOverrideCollector(),
        );
    }

    public function createConfigurationOverrideCollector(): ConfigurationOverrideCollectorInterface
    {
        return new ConfigurationOverrideCollector();
    }

    public function createConfigurationSchemaSettingsMapper(): ConfigurationSchemaSettingsMapperInterface
    {
        return new ConfigurationSchemaSettingsMapper(
            $this->getUtilEncodingService(),
        );
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
            $this->createConfigurationAuditLogger(),
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

    public function createConfigurationAuditLogger(): ConfigurationAuditLoggerInterface
    {
        return new ConfigurationAuditLogger();
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

    public function createConfigurationValueDataImporter(): DataImporterInterface
    {
        /** @var \Spryker\Zed\DataImport\Business\Model\DataImporterInterface&\Spryker\Zed\DataImport\Business\Model\DataSet\DataSetStepBrokerAwareInterface $dataImporter */
        $dataImporter = $this->getCsvDataImporterFromConfig(
            $this->getConfig()->getConfigurationValueDataImporterDataSourceConfiguration(),
        );

        $dataSetStepBroker = new DataSetStepBroker();
        $dataSetStepBroker->addStep($this->createConfigurationValueSettingKeyValidatorStep());
        $dataSetStepBroker->addStep($this->createConfigurationValueScopeValidatorStep());
        $dataSetStepBroker->addStep($this->createConfigurationValueScopeIdentifierValidatorStep());
        $dataSetStepBroker->addStep($this->createConfigurationValueDataImportStep());

        $dataImporter->addDataSetStepBroker($dataSetStepBroker);

        return $dataImporter;
    }

    public function createConfigurationValueSettingKeyValidatorStep(): DataImportStepInterface
    {
        return new ConfigurationValueSettingKeyValidatorStep(
            $this->createConfigurationSchemaProvider(),
        );
    }

    public function createConfigurationValueScopeValidatorStep(): DataImportStepInterface
    {
        return new ConfigurationValueScopeValidatorStep(
            $this->getConfig(),
        );
    }

    public function createConfigurationValueScopeIdentifierValidatorStep(): DataImportStepInterface
    {
        return new ConfigurationValueScopeIdentifierValidatorStep();
    }

    public function createConfigurationValueDataImportStep(): DataImportStepInterface
    {
        return new ConfigurationValueDataImportStep(
            $this->createConfigurationValueWriter(),
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

    public function createConfigurationSchemaSearcher(): ConfigurationSchemaSearcherInterface
    {
        return new ConfigurationSchemaSearcher(
            $this->createConfigurationSchemaProvider(),
            $this->getTranslatorFacade(),
        );
    }

    public function getTranslatorFacade(): TranslatorFacadeInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::FACADE_TRANSLATOR);
    }

    public function getUtilEncodingService(): UtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCODING);
    }
}
