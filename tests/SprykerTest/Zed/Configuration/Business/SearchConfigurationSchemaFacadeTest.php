<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business;

use Codeception\Test\Unit;
use Spryker\Shared\Configuration\ConfigurationConfig as SprykerConfigurationConfig;
use Spryker\Zed\Configuration\Business\ConfigurationBusinessFactory;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Configuration\ConfigurationDependencyProvider;
use Spryker\Zed\Translator\Business\TranslatorFacadeInterface;
use SprykerTest\Zed\Configuration\ConfigurationBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Facade
 * @group SearchConfigurationSchemaFacadeTest
 * Add your own group annotations below this line
 */
class SearchConfigurationSchemaFacadeTest extends Unit
{
    protected ConfigurationBusinessTester $tester;

    public function testSearchReturnsEmptyArrayForEmptyTerm(): void
    {
        // Arrange
        $facade = $this->createFacade();

        // Act
        $result = $facade->searchConfigurationSchema('', 'global');

        // Assert
        $this->assertSame([], $result);
    }

    public function testSearchMatchesFeatureByName(): void
    {
        // Arrange
        $facade = $this->createFacade();

        // Act
        $result = $facade->searchConfigurationSchema('catalog', 'global');

        // Assert
        $this->assertArrayHasKey('catalog', $result);
        $this->assertContains('general', $result['catalog']);
        $this->assertContains('email', $result['catalog']);
    }

    public function testSearchMatchesSettingByKey(): void
    {
        // Arrange
        $facade = $this->createFacade();

        // Act
        $result = $facade->searchConfigurationSchema('items_per_page', 'global');

        // Assert
        $this->assertArrayHasKey('catalog', $result);
        $this->assertContains('general', $result['catalog']);
    }

    public function testSearchMatchesSettingByName(): void
    {
        // Arrange
        $facade = $this->createFacade();

        // Act
        $result = $facade->searchConfigurationSchema('Items Per Page', 'global');

        // Assert
        $this->assertArrayHasKey('catalog', $result);
        $this->assertContains('general', $result['catalog']);
    }

    public function testSearchReturnsEmptyArrayForNoMatch(): void
    {
        // Arrange
        $facade = $this->createFacade();

        // Act
        $result = $facade->searchConfigurationSchema('nonexistent_xyz_term', 'global');

        // Assert
        $this->assertSame([], $result);
    }

    public function testSearchFiltersByScope(): void
    {
        // Arrange
        $facade = $this->createFacade();

        // Act — api_key is global-only, search in store scope should still match if setting exists in that scope
        $result = $facade->searchConfigurationSchema('sender_email', 'global');

        // Assert
        $this->assertArrayHasKey('catalog', $result);
        $this->assertContains('email', $result['catalog']);
    }

    public function testSearchIsCaseInsensitive(): void
    {
        // Arrange
        $facade = $this->createFacade();

        // Act
        $result = $facade->searchConfigurationSchema('CATALOG', 'global');

        // Assert
        $this->assertArrayHasKey('catalog', $result);
    }

    protected function createFacade(): ConfigurationFacadeInterface
    {
        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('getMergedSchemaFilePath')->willReturn($this->getTestSchemaFilePath());
        $configMock->method('getSharedModuleConfig')->willReturn($this->createSharedConfigMock());

        $translatorFacadeMock = $this->createMock(TranslatorFacadeInterface::class);
        $translatorFacadeMock->method('trans')->willReturnArgument(0);

        $this->tester->setDependency(ConfigurationDependencyProvider::FACADE_TRANSLATOR, $translatorFacadeMock);

        $factory = new ConfigurationBusinessFactory();
        $factory->setConfig($configMock);

        $facade = $this->tester->getFacade();
        $facade->setFactory($factory);

        return $facade;
    }

    protected function createSharedConfigMock(): SprykerConfigurationConfig
    {
        $sharedConfigMock = $this->createMock(SprykerConfigurationConfig::class);
        $sharedConfigMock->method('getMergedSchemaFilePath')->willReturn($this->getTestSchemaFilePath());
        $sharedConfigMock->method('getSettingsMapFilePath')->willReturn($this->getTestSettingsMapFilePath());

        return $sharedConfigMock;
    }

    protected function getTestSchemaFilePath(): string
    {
        return __DIR__ . '/../_data/test-schema.php';
    }

    protected function getTestSettingsMapFilePath(): string
    {
        return __DIR__ . '/../_data/test-settings-map.php';
    }
}
