<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business;

use Codeception\Test\Unit;
use Spryker\Service\UtilEncryption\UtilEncryptionServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConfig as SprykerConfigurationConfig;
use Spryker\Zed\Configuration\Business\ConfigurationBusinessFactory;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
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
 * @group GetMergedConfigurationSchemaFacadeTest
 * Add your own group annotations below this line
 */
class GetMergedConfigurationSchemaFacadeTest extends Unit
{
    protected ConfigurationBusinessTester $tester;

    public function testGetMergedConfigurationSchemaReturnsSchemaArray(): void
    {
        // Arrange
        $facade = $this->createFacadeWithTestSchema();

        // Act
        $result = $facade->getMergedConfigurationSchema();

        // Assert
        $this->assertArrayHasKey('features', $result);
        $this->assertCount(1, $result['features']);
        $this->assertSame('catalog', $result['features'][0]['key']);
    }

    public function testGetMergedConfigurationSchemaContainsSettingsStructure(): void
    {
        // Arrange
        $facade = $this->createFacadeWithTestSchema();

        // Act
        $result = $facade->getMergedConfigurationSchema();

        // Assert
        $tabs = $result['features'][0]['tabs'];
        $this->assertCount(2, $tabs);
        $this->assertSame('general', $tabs[0]['key']);
        $this->assertSame('email', $tabs[1]['key']);
    }

    public function testGetMergedConfigurationSchemaReturnsEmptyArrayWhenNoSchemaFile(): void
    {
        // Arrange
        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('getMergedSchemaFilePath')->willReturn('/non/existent/path.php');
        $configMock->method('getCoreConfigSchemaPattens')->willReturn([]);
        $configMock->method('getProjectConfigSchemaPattens')->willReturn([]);
        $configMock->method('getSharedModuleConfig')->willReturn($this->createSharedConfigMock('/non/existent/path.php'));

        $factory = new ConfigurationBusinessFactory();
        $factory->setConfig($configMock);

        $this->tester->setDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION, $this->createMock(UtilEncryptionServiceInterface::class));

        $facade = $this->tester->getFacade();
        $facade->setFactory($factory);

        // Act
        $result = $facade->getMergedConfigurationSchema();

        // Assert
        $this->assertSame([], $result);
    }

    protected function createFacadeWithTestSchema(): ConfigurationFacadeInterface
    {
        $schemaFilePath = __DIR__ . '/../_data/test-schema.php';

        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('getMergedSchemaFilePath')->willReturn($schemaFilePath);
        $configMock->method('getCoreConfigSchemaPattens')->willReturn([]);
        $configMock->method('getProjectConfigSchemaPattens')->willReturn([]);
        $configMock->method('getSharedModuleConfig')->willReturn($this->createSharedConfigMock($schemaFilePath));

        $factory = new ConfigurationBusinessFactory();
        $factory->setConfig($configMock);

        $this->tester->setDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION, $this->createMock(UtilEncryptionServiceInterface::class));

        $facade = $this->tester->getFacade();
        $facade->setFactory($factory);

        return $facade;
    }

    protected function createSharedConfigMock(string $schemaFilePath): SprykerConfigurationConfig
    {
        $sharedConfigMock = $this->createMock(SprykerConfigurationConfig::class);
        $sharedConfigMock->method('getMergedSchemaFilePath')->willReturn($schemaFilePath);
        $sharedConfigMock->method('getSettingsMapFilePath')->willReturn(__DIR__ . '/../_data/test-settings-map.php');

        return $sharedConfigMock;
    }
}
