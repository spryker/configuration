<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business;

use Codeception\Test\Unit;
use Spryker\Service\UtilEncryption\UtilEncryptionServiceInterface;
use Spryker\Zed\Configuration\Business\ConfigurationBusinessFactory;
use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Configuration\ConfigurationDependencyProvider;
use SprykerTest\Zed\Configuration\ConfigurationBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Facade
 * @group SyncConfigurationSchemasFacadeTest
 * Add your own group annotations below this line
 */
class SyncConfigurationSchemasFacadeTest extends Unit
{
    protected ConfigurationBusinessTester $tester;

    protected string $tempSchemaOutputPath;

    protected string $tempSettingsMapOutputPath;

    protected string $tempSchemaDirectory;

    protected function _before(): void
    {
        $this->tempSchemaDirectory = sys_get_temp_dir() . '/spryker_config_test_' . uniqid();
        $this->tempSchemaOutputPath = $this->tempSchemaDirectory . '/merged-schema.php';
        $this->tempSettingsMapOutputPath = $this->tempSchemaDirectory . '/settings-map.php';

        mkdir($this->tempSchemaDirectory, 0755, true);
    }

    protected function _after(): void
    {
        if (file_exists($this->tempSchemaOutputPath)) {
            unlink($this->tempSchemaOutputPath);
        }

        if (file_exists($this->tempSettingsMapOutputPath)) {
            unlink($this->tempSettingsMapOutputPath);
        }

        $syncDir = __DIR__ . '/../_data/sync_schemas';

        if (file_exists($syncDir . '/test.configuration.yaml')) {
            unlink($syncDir . '/test.configuration.yaml');
            rmdir($syncDir);
        }

        if (is_dir($this->tempSchemaDirectory)) {
            rmdir($this->tempSchemaDirectory);
        }
    }

    public function testSyncConfigurationSchemasReturnsSuccessWhenSchemasExist(): void
    {
        // Arrange
        $this->createTestSchemaYaml();
        $facade = $this->createFacadeWithSchemaDirectory($this->getRelativeSchemaPath());

        // Act
        $result = $facade->syncConfigurationSchemas();

        // Assert
        $this->assertTrue($result->getIsSuccess());
        $this->assertGreaterThan(0, $result->getProcessedCount());
    }

    public function testSyncConfigurationSchemasReturnsSuccessWithNoSchemas(): void
    {
        // Arrange
        $facade = $this->createFacadeWithSchemaDirectory('non_existent_path_' . uniqid());

        // Act
        $result = $facade->syncConfigurationSchemas();

        // Assert
        $this->assertTrue($result->getIsSuccess());
        $this->assertSame(0, $result->getProcessedCount());
    }

    public function testSyncConfigurationSchemasWritesMergedSchemaFile(): void
    {
        // Arrange
        $this->createTestSchemaYaml();
        $facade = $this->createFacadeWithSchemaDirectory($this->getRelativeSchemaPath());

        // Act
        $facade->syncConfigurationSchemas();

        // Assert
        $this->assertFileExists($this->tempSchemaOutputPath);

        $schema = require $this->tempSchemaOutputPath;
        $this->assertArrayHasKey('features', $schema);
    }

    public function testSyncConfigurationSchemasWritesSettingsMapFile(): void
    {
        // Arrange
        $this->createTestSchemaYaml();
        $facade = $this->createFacadeWithSchemaDirectory($this->getRelativeSchemaPath());

        // Act
        $facade->syncConfigurationSchemas();

        // Assert
        $this->assertFileExists($this->tempSettingsMapOutputPath);

        $settingsMap = require $this->tempSettingsMapOutputPath;
        $this->assertIsArray($settingsMap);
        $this->assertArrayHasKey('test_feature:general:display:test_setting', $settingsMap);
        $this->assertSame('string', $settingsMap['test_feature:general:display:test_setting']['type']);
        $this->assertSame('default', $settingsMap['test_feature:general:display:test_setting']['default_value']);
    }

    protected function createFacadeWithSchemaDirectory(string $schemaPath): ConfigurationFacade
    {
        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('getCoreConfigSchemaPattens')->willReturn([$schemaPath]);
        $configMock->method('getProjectConfigSchemaPattens')->willReturn([]);
        $configMock->method('getMergedSchemaFilePath')->willReturn($this->tempSchemaOutputPath);
        $configMock->method('getSettingsMapFilePath')->willReturn($this->tempSettingsMapOutputPath);
        $configMock->method('getAvailableScopes')->willReturn(['global', 'store']);

        $factory = new ConfigurationBusinessFactory();
        $factory->setConfig($configMock);

        $this->tester->setDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION, $this->createMock(UtilEncryptionServiceInterface::class));

        $facade = $this->tester->getFacade();
        $facade->setFactory($factory);

        return $facade;
    }

    protected function getRelativeSchemaPath(): string
    {
        $absolutePath = realpath(__DIR__ . '/../_data/sync_schemas');

        return ltrim(str_replace(APPLICATION_ROOT_DIR, '', $absolutePath), DIRECTORY_SEPARATOR);
    }

    protected function createTestSchemaYaml(): void
    {
        $dir = __DIR__ . '/../_data/sync_schemas';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $yaml = <<<'YAML'
features:
  - key: test_feature
    name: Test Feature
    tabs:
      - key: general
        name: General
        groups:
          - key: display
            name: Display
            scopes:
              - global
            settings:
              - key: test_setting
                name: Test Setting
                type: string
                default_value: "default"
                scopes:
                  - global
YAML;

        file_put_contents($dir . '/test.configuration.yaml', $yaml);
    }
}
