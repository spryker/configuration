<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Plugin\Publisher;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\EventEntityTransfer;
use Orm\Zed\Configuration\Persistence\SpyConfigurationStorageQuery;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValue;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValueQuery;
use Spryker\Service\UtilEncryption\UtilEncryptionServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConfig as SprykerConfigurationConfig;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\ConfigurationBusinessFactory;
use Spryker\Zed\Configuration\Communication\Plugin\Publisher\ConfigurationValueWritePublisherPlugin;
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
 * @group Publisher
 * @group ConfigurationValueWritePublisherPluginTest
 * Add your own group annotations below this line
 */
class ConfigurationValueWritePublisherPluginTest extends Unit
{
    protected ConfigurationCommunicationTester $tester;

    public function testHandleBulkPublishesStorefrontSettings(): void
    {
        // Arrange
        $this->createConfigurationValueEntity('catalog:general:display:items_per_page', 'global', '24');

        $plugin = $this->createPlugin();

        $eventEntityTransfers = [
            (new EventEntityTransfer())->setAdditionalValues([
                'spy_configuration_value.scope' => 'global',
                'spy_configuration_value.scope_identifier' => null,
            ]),
        ];

        // Act
        $plugin->handleBulk($eventEntityTransfers, ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_UPDATE);

        // Assert
        $storageEntity = SpyConfigurationStorageQuery::create()
            ->filterByScope('global')
            ->findOne();

        $this->assertNotNull($storageEntity);

        $data = $storageEntity->getData();
        $this->assertArrayHasKey('catalog:general:display:items_per_page', $data);
        $this->assertSame('24', $data['catalog:general:display:items_per_page']);
    }

    public function testHandleBulkExcludesSecretSettings(): void
    {
        // Arrange
        $this->createConfigurationValueEntity('catalog:email:notifications:api_key', 'global', 'secret-key-value');

        $plugin = $this->createPlugin();

        $eventEntityTransfers = [
            (new EventEntityTransfer())->setAdditionalValues([
                'spy_configuration_value.scope' => 'global',
                'spy_configuration_value.scope_identifier' => null,
            ]),
        ];

        // Act
        $plugin->handleBulk($eventEntityTransfers, ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_UPDATE);

        // Assert
        $storageEntity = SpyConfigurationStorageQuery::create()
            ->filterByScope('global')
            ->findOne();

        $this->assertNotNull($storageEntity);
        $this->assertArrayNotHasKey('catalog:email:notifications:api_key', $storageEntity->getData());
    }

    public function testHandleBulkDeletesStorageWhenNoValues(): void
    {
        // Arrange - use a unique store scope to avoid pre-existing global data
        $this->createConfigurationValueEntity('catalog:general:display:items_per_page', 'store', '24', 'TEST_DELETE');

        $plugin = $this->createPlugin();

        $eventEntityTransfers = [
            (new EventEntityTransfer())->setAdditionalValues([
                'spy_configuration_value.scope' => 'store',
                'spy_configuration_value.scope_identifier' => 'TEST_DELETE',
            ]),
        ];

        $plugin->handleBulk($eventEntityTransfers, ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_CREATE);

        $this->assertNotNull(
            SpyConfigurationStorageQuery::create()->filterByScope('store:TEST_DELETE')->findOne(),
        );

        // Now delete the configuration value so storage rebuild finds nothing
        SpyConfigurationValueQuery::create()
            ->filterBySettingKey('catalog:general:display:items_per_page')
            ->filterByScope('store')
            ->filterByScopeIdentifier('TEST_DELETE')
            ->delete();

        // Act
        $plugin->handleBulk($eventEntityTransfers, ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_DELETE);

        // Assert
        $deletedStorageEntity = SpyConfigurationStorageQuery::create()
            ->filterByScope('store:TEST_DELETE')
            ->findOne();

        $this->assertNull($deletedStorageEntity);
    }

    public function testHandleBulkHandlesEmptyEventList(): void
    {
        // Arrange
        $plugin = $this->createPlugin();
        $storageCountBefore = SpyConfigurationStorageQuery::create()->count();

        // Act & Assert - should not throw
        $plugin->handleBulk([], ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_UPDATE);

        $storageCountAfter = SpyConfigurationStorageQuery::create()->count();

        $this->assertSame($storageCountBefore, $storageCountAfter);
    }

    public function testHandleBulkDeduplicatesScopeEvents(): void
    {
        // Arrange
        $this->createConfigurationValueEntity('catalog:general:display:items_per_page', 'store', '48', 'DE');

        $plugin = $this->createPlugin();

        // Two events for the same scope should be deduplicated
        $eventEntityTransfers = [
            (new EventEntityTransfer())->setAdditionalValues([
                'spy_configuration_value.scope' => 'store',
                'spy_configuration_value.scope_identifier' => 'DE',
            ]),
            (new EventEntityTransfer())->setAdditionalValues([
                'spy_configuration_value.scope' => 'store',
                'spy_configuration_value.scope_identifier' => 'DE',
            ]),
        ];

        // Act
        $plugin->handleBulk($eventEntityTransfers, ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_UPDATE);

        // Assert
        $storageCount = SpyConfigurationStorageQuery::create()
            ->filterByScope('store:DE')
            ->count();

        $this->assertSame(1, $storageCount);
    }

    public function testGetSubscribedEventsReturnsConfigurationValueEvents(): void
    {
        // Arrange
        $plugin = new ConfigurationValueWritePublisherPlugin();

        // Act
        $subscribedEvents = $plugin->getSubscribedEvents();

        // Assert
        $this->assertContains(ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_DELETE, $subscribedEvents);
        $this->assertContains(ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_CREATE, $subscribedEvents);
        $this->assertContains(ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_UPDATE, $subscribedEvents);
    }

    protected function createPlugin(): ConfigurationValueWritePublisherPlugin
    {
        $schemaFilePath = __DIR__ . '/../../../_data/test-schema.php';

        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('getMergedSchemaFilePath')->willReturn($schemaFilePath);
        $configMock->method('getCoreConfigSchemaPattens')->willReturn([]);
        $configMock->method('getProjectConfigSchemaPattens')->willReturn([]);
        $configMock->method('getSharedModuleConfig')->willReturn($this->createSharedConfigMock($schemaFilePath));

        $factory = new ConfigurationBusinessFactory();
        $factory->setConfig($configMock);

        $this->tester->setDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION, $this->createMock(UtilEncryptionServiceInterface::class));

        $plugin = new ConfigurationValueWritePublisherPlugin();
        $plugin->setBusinessFactory($factory);

        return $plugin;
    }

    protected function createSharedConfigMock(string $schemaFilePath): SprykerConfigurationConfig
    {
        $sharedConfigMock = $this->createMock(SprykerConfigurationConfig::class);
        $sharedConfigMock->method('getMergedSchemaFilePath')->willReturn($schemaFilePath);
        $sharedConfigMock->method('getSettingsMapFilePath')->willReturn(__DIR__ . '/../../../_data/test-settings-map.php');

        return $sharedConfigMock;
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
