<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Plugin\DataImport;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\DataImporterConfigurationTransfer;
use Generated\Shared\Transfer\DataImporterDataSourceConfigurationTransfer;
use Generated\Shared\Transfer\DataImporterReaderConfigurationTransfer;
use Generated\Shared\Transfer\DataImporterReportTransfer;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValueQuery;
use Spryker\Service\UtilEncryption\UtilEncryptionServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConfig as SharedConfigurationConfig;
use Spryker\Zed\Configuration\Business\ConfigurationBusinessFactory;
use Spryker\Zed\Configuration\Communication\Plugin\DataImport\ConfigurationValueDataImportPlugin;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Configuration\ConfigurationDependencyProvider;
use SprykerTest\Zed\Configuration\ConfigurationCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Communication
 * @group Plugin
 * @group DataImport
 * @group ConfigurationValueDataImportPluginTest
 * Add your own group annotations below this line
 */
class ConfigurationValueDataImportPluginTest extends Unit
{
    protected ConfigurationCommunicationTester $tester;

    public function testImportImportsConfigurationValuesFromCsv(): void
    {
        // Arrange
        $dataImporterReaderConfigurationTransfer = new DataImporterReaderConfigurationTransfer();
        $dataImporterReaderConfigurationTransfer->setFileName(codecept_data_dir() . 'import/configuration_value.csv');

        $dataImporterConfigurationTransfer = (new DataImporterConfigurationTransfer())
            ->setReaderConfiguration($dataImporterReaderConfigurationTransfer)
            ->setThrowException(true);

        $factory = $this->createBusinessFactory();

        // Act
        // Exercise the factory directly: `AbstractPlugin::getBusinessFactory()` resolves via the
        // class resolver and would bypass the mocked config/schema wired up here.
        $dataImporterReportTransfer = $factory
            ->createConfigurationValueDataImporter()
            ->import($dataImporterConfigurationTransfer);

        // Assert
        $this->assertInstanceOf(DataImporterReportTransfer::class, $dataImporterReportTransfer);
        $this->assertTrue($dataImporterReportTransfer->getIsSuccessOrFail(), 'Data import should finish successfully.');

        $globalEntity = SpyConfigurationValueQuery::create()
            ->filterBySettingKey('catalog:general:display:items_per_page')
            ->filterByScope('global')
            ->findOne();

        $this->assertNotNull($globalEntity, 'Global configuration value should exist in database.');
        $this->assertSame('24', $globalEntity->getValue());

        $storeEntity = SpyConfigurationValueQuery::create()
            ->filterBySettingKey('catalog:general:display:items_per_page')
            ->filterByScope('store')
            ->filterByScopeIdentifier('DE')
            ->findOne();

        $this->assertNotNull($storeEntity, 'Store-scoped configuration value should exist in database.');
        $this->assertSame('48', $storeEntity->getValue());
    }

    public function testGetImportTypeReturnsConfigurationValueImportType(): void
    {
        // Arrange
        $plugin = new ConfigurationValueDataImportPlugin();

        // Act
        $importType = $plugin->getImportType();

        // Assert
        $this->assertSame(ConfigurationConfig::IMPORT_TYPE_CONFIGURATION_VALUE, $importType);
    }

    protected function createBusinessFactory(): ConfigurationBusinessFactory
    {
        $schemaFilePath = __DIR__ . '/../../../_data/test-schema.php';

        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('getMergedSchemaFilePath')->willReturn($schemaFilePath);
        $configMock->method('getCoreConfigSchemaPattens')->willReturn([]);
        $configMock->method('getProjectConfigSchemaPattens')->willReturn([]);
        $configMock->method('getSharedModuleConfig')->willReturn($this->createSharedConfigMock($schemaFilePath));
        $configMock->method('isCacheEnabled')->willReturn(false);
        $configMock->method('getAvailableScopes')->willReturn(['global', 'store']);
        $configMock->method('getConfigurationValueImportType')->willReturn(ConfigurationConfig::IMPORT_TYPE_CONFIGURATION_VALUE);
        $configMock->method('getConfigurationValueDataImporterDataSourceConfiguration')->willReturn(
            $this->createDefaultDataImporterDataSourceConfiguration(),
        );

        $factory = new ConfigurationBusinessFactory();
        $factory->setConfig($configMock);

        $this->tester->setDependency(ConfigurationDependencyProvider::PLUGINS_CONFIGURATION_VALUE_PRE_SAVE, []);
        $this->tester->setDependency(ConfigurationDependencyProvider::PLUGINS_CONFIGURATION_VALUE_POST_SAVE, []);
        $this->tester->setDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION, $this->createMock(UtilEncryptionServiceInterface::class));

        return $factory;
    }

    protected function createDefaultDataImporterDataSourceConfiguration(): DataImporterDataSourceConfigurationTransfer
    {
        return (new DataImporterDataSourceConfigurationTransfer())
            ->setImportType(ConfigurationConfig::IMPORT_TYPE_CONFIGURATION_VALUE)
            ->setModuleName('Configuration')
            ->setFileName('configuration_value.csv')
            ->setDirectory(codecept_data_dir() . 'import' . DIRECTORY_SEPARATOR);
    }

    protected function createSharedConfigMock(string $schemaFilePath): SharedConfigurationConfig
    {
        $sharedConfigMock = $this->createMock(SharedConfigurationConfig::class);
        $sharedConfigMock->method('getMergedSchemaFilePath')->willReturn($schemaFilePath);
        $sharedConfigMock->method('getSettingsMapFilePath')->willReturn(__DIR__ . '/../../../_data/test-settings-map.php');

        return $sharedConfigMock;
    }
}
