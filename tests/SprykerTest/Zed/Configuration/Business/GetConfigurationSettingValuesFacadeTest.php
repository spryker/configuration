<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationSettingValuesCriteriaTransfer;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValue;
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
 * @group GetConfigurationSettingValuesFacadeTest
 * Add your own group annotations below this line
 */
class GetConfigurationSettingValuesFacadeTest extends Unit
{
    protected ConfigurationBusinessTester $tester;

    public function testGetConfigurationSettingValuesReturnsDirectValues(): void
    {
        // Arrange
        $this->createConfigurationValueEntity('catalog:general:display:items_per_page', 'global', '24');
        $this->createConfigurationValueEntity('catalog:general:display:sort_order', 'global', 'name_asc');

        $facade = $this->createFacade();

        $criteria = (new ConfigurationSettingValuesCriteriaTransfer())
            ->setSettingKeys(['catalog:general:display:items_per_page', 'catalog:general:display:sort_order'])
            ->setScope('global');

        // Act
        $result = $facade->getConfigurationSettingValues($criteria);

        // Assert
        $this->assertSame('24', $result->getDirectValues()['catalog:general:display:items_per_page']);
        $this->assertSame('name_asc', $result->getDirectValues()['catalog:general:display:sort_order']);
    }

    public function testGetConfigurationSettingValuesReturnsInheritedValues(): void
    {
        // Arrange
        $this->createConfigurationValueEntity('catalog:general:display:items_per_page', 'store', '24', 'DE');
        $this->createConfigurationValueEntity('catalog:general:display:sort_order', 'global', 'price_asc');

        $facade = $this->createFacade();

        $criteria = (new ConfigurationSettingValuesCriteriaTransfer())
            ->setSettingKeys(['catalog:general:display:items_per_page', 'catalog:general:display:sort_order'])
            ->setScope('store')
            ->setScopeIdentifier('DE');

        // Act
        $result = $facade->getConfigurationSettingValues($criteria);

        // Assert
        $this->assertCount(1, $result->getDirectValues());
        $this->assertSame('24', $result->getDirectValues()['catalog:general:display:items_per_page']);
        $this->assertCount(1, $result->getInheritedValues());
        $this->assertSame('price_asc', $result->getInheritedValues()['catalog:general:display:sort_order']);
    }

    public function testGetConfigurationSettingValuesReturnsEmptyWhenNoValues(): void
    {
        // Arrange
        $facade = $this->createFacade();

        $criteria = (new ConfigurationSettingValuesCriteriaTransfer())
            ->setSettingKeys(['non:existent:group:key'])
            ->setScope('global');

        // Act
        $result = $facade->getConfigurationSettingValues($criteria);

        // Assert
        $this->assertEmpty($result->getDirectValues());
    }

    protected function createFacade(): ConfigurationFacade
    {
        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('getScopeHierarchy')->willReturn([
            'global' => null,
            'store' => 'global',
        ]);

        $factory = new ConfigurationBusinessFactory();
        $factory->setConfig($configMock);

        $this->tester->setDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION, $this->createMock(UtilEncryptionServiceInterface::class));

        $facade = $this->tester->getFacade();
        $facade->setFactory($factory);

        return $facade;
    }

    protected function createConfigurationValueEntity(string $settingKey, string $scope, string $value, ?string $scopeIdentifier = null): void
    {
        $entity = new SpyConfigurationValue();
        $entity->setSettingKey($settingKey);
        $entity->setScope($scope);
        $entity->setScopeIdentifier($scopeIdentifier);
        $entity->setValue($value);
        $entity->save();
    }
}
