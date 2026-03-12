<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationScopeTransfer;
use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValue;
use ReflectionProperty;
use Spryker\Service\UtilEncryption\UtilEncryptionServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConfig as SprykerConfigurationConfig;
use Spryker\Shared\Configuration\Reader\AbstractConfigurationValueResolver;
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
 * @group GetConfigurationValueFacadeTest
 * Add your own group annotations below this line
 */
class GetConfigurationValueFacadeTest extends Unit
{
    protected ConfigurationBusinessTester $tester;

    protected function _before(): void
    {
        $settingCache = new ReflectionProperty(AbstractConfigurationValueResolver::class, 'settingCache');
        $settingCache->setValue(null, null);

        $resolvedValueCache = new ReflectionProperty(AbstractConfigurationValueResolver::class, 'resolvedValueCache');
        $resolvedValueCache->setValue(null, []);
    }

    public function testGetConfigurationValueReturnsStoredValue(): void
    {
        // Arrange
        $this->createConfigurationValueEntity('catalog:general:display:items_per_page', 'global', '24');

        $facade = $this->createFacade();
        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey('catalog:general:display:items_per_page');

        // Act
        $result = $facade->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertSame('24', $result);
    }

    public function testGetConfigurationValueReturnsDefaultWhenNotFound(): void
    {
        // Arrange
        $facade = $this->createFacade();
        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey('catalog:general:display:items_per_page');

        // Act
        $result = $facade->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertSame('12', $result);
    }

    public function testGetConfigurationValueResolvesStoreScope(): void
    {
        // Arrange
        $this->createConfigurationValueEntity('catalog:general:display:items_per_page', 'store', '48', 'DE');

        $facade = $this->createFacade();
        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey('catalog:general:display:items_per_page')
            ->addScope(
                (new ConfigurationScopeTransfer())
                    ->setKey('store')
                    ->setIdentifier('DE'),
            );

        // Act
        $result = $facade->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertSame('48', $result);
    }

    public function testGetConfigurationValueFallsBackToGlobalScope(): void
    {
        // Arrange
        $this->createConfigurationValueEntity('catalog:general:display:items_per_page', 'global', '36');

        $facade = $this->createFacade();
        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey('catalog:general:display:items_per_page')
            ->addScope(
                (new ConfigurationScopeTransfer())
                    ->setKey('store')
                    ->setIdentifier('DE'),
            );

        // Act
        $result = $facade->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertSame('36', $result);
    }

    public function testGetConfigurationValueReturnsNullForUnknownKey(): void
    {
        // Arrange
        $facade = $this->createFacade();
        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey('unknown:feature:group:key');

        // Act
        $result = $facade->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertNull($result);
    }

    protected function createFacade(): ConfigurationFacade
    {
        $configMock = $this->createConfigMock();

        $factory = new ConfigurationBusinessFactory();
        $factory->setConfig($configMock);

        $this->tester->setDependency(ConfigurationDependencyProvider::PLUGINS_CONFIGURATION_VALUE_REQUEST_EXPANDER, []);
        $this->tester->setDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION, $this->createMock(UtilEncryptionServiceInterface::class));

        $facade = $this->tester->getFacade();
        $facade->setFactory($factory);

        return $facade;
    }

    protected function createConfigMock(): ConfigurationConfig
    {
        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('isCacheEnabled')->willReturn(false);
        $configMock->method('getScopeHierarchy')->willReturn([
            'global' => null,
            'store' => 'global',
        ]);
        $configMock->method('getAvailableScopes')->willReturn(['global', 'store']);
        $configMock->method('getMergedSchemaFilePath')->willReturn($this->getTestSchemaFilePath());
        $configMock->method('getSharedModuleConfig')->willReturn($this->createSharedConfigMock());

        return $configMock;
    }

    protected function createSharedConfigMock(): SprykerConfigurationConfig
    {
        $sharedConfigMock = $this->createMock(SprykerConfigurationConfig::class);
        $sharedConfigMock->method('getMergedSchemaFilePath')->willReturn($this->getTestSchemaFilePath());
        $sharedConfigMock->method('getSettingsMapFilePath')->willReturn($this->getTestSettingsMapFilePath());
        $sharedConfigMock->method('getScopeHierarchy')->willReturn([
            'global' => null,
            'store' => 'global',
        ]);
        $sharedConfigMock->method('getAvailableScopes')->willReturn(['global', 'store']);

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
