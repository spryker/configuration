<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business;

use Codeception\Test\Unit;
use Spryker\Zed\Configuration\ConfigurationDependencyProvider;
use Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationScopeIdentifierProviderPluginInterface;
use SprykerTest\Zed\Configuration\ConfigurationBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Facade
 * @group GetScopeIdentifiersFacadeTest
 * Add your own group annotations below this line
 */
class GetScopeIdentifiersFacadeTest extends Unit
{
    protected ConfigurationBusinessTester $tester;

    public function testGetScopeIdentifiersReturnsEmptyWithNoPlugins(): void
    {
        // Arrange
        $this->tester->setDependency(ConfigurationDependencyProvider::PLUGINS_SCOPE_IDENTIFIER_PROVIDER, []);

        // Act
        $result = $this->tester->getFacade()->getScopeIdentifiers('store');

        // Assert
        $this->assertSame([], $result);
    }

    public function testGetScopeIdentifiersReturnsIdentifiersFromPlugin(): void
    {
        // Arrange
        $pluginMock = $this->createMock(ConfigurationScopeIdentifierProviderPluginInterface::class);
        $pluginMock->method('getScopeKey')->willReturn('store');
        $pluginMock->method('getIdentifiers')->willReturn(['DE', 'AT', 'US']);

        $this->tester->setDependency(ConfigurationDependencyProvider::PLUGINS_SCOPE_IDENTIFIER_PROVIDER, [$pluginMock]);

        // Act
        $result = $this->tester->getFacade()->getScopeIdentifiers('store');

        // Assert
        $this->assertSame(['DE', 'AT', 'US'], $result);
        $this->assertCount(3, $result);
    }

    public function testGetScopeIdentifiersReturnsEmptyForUnhandledScope(): void
    {
        // Arrange
        $pluginMock = $this->createMock(ConfigurationScopeIdentifierProviderPluginInterface::class);
        $pluginMock->method('getScopeKey')->willReturn('store');
        $pluginMock->method('getIdentifiers')->willReturn(['DE']);

        $this->tester->setDependency(ConfigurationDependencyProvider::PLUGINS_SCOPE_IDENTIFIER_PROVIDER, [$pluginMock]);

        // Act
        $result = $this->tester->getFacade()->getScopeIdentifiers('locale');

        // Assert
        $this->assertSame([], $result);
    }

    public function testGetScopeIdentifiersMatchesCorrectPluginFromMultiple(): void
    {
        // Arrange
        $storePlugin = $this->createMock(ConfigurationScopeIdentifierProviderPluginInterface::class);
        $storePlugin->method('getScopeKey')->willReturn('store');
        $storePlugin->method('getIdentifiers')->willReturn(['DE', 'AT']);

        $localePlugin = $this->createMock(ConfigurationScopeIdentifierProviderPluginInterface::class);
        $localePlugin->method('getScopeKey')->willReturn('locale');
        $localePlugin->method('getIdentifiers')->willReturn(['de_DE', 'en_US']);

        $this->tester->setDependency(ConfigurationDependencyProvider::PLUGINS_SCOPE_IDENTIFIER_PROVIDER, [$storePlugin, $localePlugin]);

        // Act
        $storeResult = $this->tester->getFacade()->getScopeIdentifiers('store');
        $localeResult = $this->tester->getFacade()->getScopeIdentifiers('locale');

        // Assert
        $this->assertSame(['DE', 'AT'], $storeResult);
        $this->assertSame(['de_DE', 'en_US'], $localeResult);
    }
}
