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
 * @group GetAllConfigurationSettingsFacadeTest
 * Add your own group annotations below this line
 */
class GetAllConfigurationSettingsFacadeTest extends Unit
{
    protected ConfigurationBusinessTester $tester;

    public function testGetAllConfigurationSettingsReturnsSettingTransfers(): void
    {
        // Arrange
        $facade = $this->createFacadeWithTestSchema();

        // Act
        $result = $facade->getAllConfigurationSettings();

        // Assert
        $this->assertCount(4, $result);
        $this->assertSame('catalog:general:display:items_per_page', $result[0]->getKey());
        $this->assertSame('catalog:general:display:sort_order', $result[1]->getKey());
        $this->assertSame('catalog:email:notifications:sender_email', $result[2]->getKey());
        $this->assertSame('catalog:email:notifications:api_key', $result[3]->getKey());
    }

    public function testGetAllConfigurationSettingsPreservesSettingProperties(): void
    {
        // Arrange
        $facade = $this->createFacadeWithTestSchema();

        // Act
        $result = $facade->getAllConfigurationSettings();

        // Assert
        $itemsPerPage = $result[0];
        $this->assertSame('Items Per Page', $itemsPerPage->getName());
        $this->assertSame('integer', $itemsPerPage->getType());
        $this->assertSame('12', $itemsPerPage->getDefaultValue());
        $this->assertSame('catalog', $itemsPerPage->getFeatureKey());
        $this->assertSame('general', $itemsPerPage->getTabKey());
        $this->assertFalse($itemsPerPage->getIsSecret());
        $this->assertTrue($itemsPerPage->getIsStorefront());
    }

    public function testGetAllConfigurationSettingsIdentifiesSecretSettings(): void
    {
        // Arrange
        $facade = $this->createFacadeWithTestSchema();

        // Act
        $result = $facade->getAllConfigurationSettings();

        // Assert
        $apiKey = $result[3];
        $this->assertSame('catalog:email:notifications:api_key', $apiKey->getKey());
        $this->assertTrue($apiKey->getIsSecret());
    }

    public function testGetAllConfigurationSettingsReturnsEmptyArrayWhenNoSchema(): void
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
        $result = $facade->getAllConfigurationSettings();

        // Assert
        $this->assertSame([], $result);
    }

    protected function createFacadeWithTestSchema(): ConfigurationFacade
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
