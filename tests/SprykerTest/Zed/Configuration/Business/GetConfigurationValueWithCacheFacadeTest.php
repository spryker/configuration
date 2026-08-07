<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationScopeTransfer;
use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValue;
use ReflectionProperty;
use Spryker\Service\UtilEncryption\UtilEncryptionServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConfig as SprykerConfigurationConfig;
use Spryker\Shared\Configuration\Reader\AbstractConfigurationValueResolver;
use Spryker\Zed\Configuration\Business\Cache\ConfigurationCacheManager;
use Spryker\Zed\Configuration\Business\ConfigurationBusinessFactory;
use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Configuration\ConfigurationDependencyProvider;
use Spryker\Zed\Configuration\Persistence\ConfigurationRepository;
use SprykerTest\Zed\Configuration\ConfigurationBusinessTester;

/**
 * Covers the reads that run with `ConfigurationConfig::isCacheEnabled()` enabled, which is the
 * production default. The sibling facade tests all disable the cache, so the warm-up path that
 * loads a whole scope in one query is only exercised here.
 *
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Facade
 * @group GetConfigurationValueWithCacheFacadeTest
 * Add your own group annotations below this line
 */
class GetConfigurationValueWithCacheFacadeTest extends Unit
{
    /**
     * @var string
     */
    protected const PREFIX_DISPLAY = 'catalog:general:display';

    /**
     * @var string
     */
    protected const KEY_ITEMS_PER_PAGE = 'catalog:general:display:items_per_page';

    /**
     * @var string
     */
    protected const KEY_SORT_ORDER = 'catalog:general:display:sort_order';

    /**
     * @var string
     */
    protected const KEY_API_KEY = 'catalog:email:notifications:api_key';

    /**
     * @var string
     */
    protected const SCOPE_GLOBAL = 'global';

    /**
     * @var string
     */
    protected const SCOPE_STORE = 'store';

    /**
     * @var string
     */
    protected const DECRYPTED_SECRET = 'decrypted-api-key';

    protected ConfigurationBusinessTester $tester;

    protected function _before(): void
    {
        $settingCache = new ReflectionProperty(AbstractConfigurationValueResolver::class, 'settingCache');
        $settingCache->setValue(null, null);

        $resolvedValueCache = new ReflectionProperty(AbstractConfigurationValueResolver::class, 'resolvedValueCache');
        $resolvedValueCache->setValue(null, []);

        (new ConfigurationCacheManager())->clearAll();
    }

    public function testGetConfigurationValuesResolvesStoredValueAndSchemaDefaultFromOneScopeRead(): void
    {
        // Arrange
        $this->createConfigurationValueEntity(static::KEY_ITEMS_PER_PAGE, static::SCOPE_GLOBAL, '50');

        $facade = $this->createFacade();
        $configurationValueRequestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::PREFIX_DISPLAY);

        // Act
        $configurationValues = $facade->getConfigurationValues($configurationValueRequestTransfer);

        // Assert
        $this->assertSame('50', $configurationValues['items_per_page']);
        $this->assertSame('name_asc', $configurationValues['sort_order']);
        $this->assertSame('', $configurationValues['description']);
    }

    public function testGetConfigurationValueReturnsSchemaDefaultWhenNoRowExists(): void
    {
        // Arrange
        $facade = $this->createFacade();
        $configurationValueRequestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::KEY_ITEMS_PER_PAGE);

        // Act
        $configurationValue = $facade->getConfigurationValue($configurationValueRequestTransfer);

        // Assert
        $this->assertSame('12', $configurationValue);
    }

    public function testGetConfigurationValueServesSecondKeyOfTheSameScopeFromTheWarmedUpCache(): void
    {
        // Arrange
        $this->createConfigurationValueEntity(static::KEY_ITEMS_PER_PAGE, static::SCOPE_GLOBAL, '50');
        $this->createConfigurationValueEntity(static::KEY_SORT_ORDER, static::SCOPE_GLOBAL, 'price_asc');

        $facade = $this->createFacade();

        // Act
        $itemsPerPage = $facade->getConfigurationValue(
            (new ConfigurationValueRequestTransfer())->setKey(static::KEY_ITEMS_PER_PAGE),
        );
        $sortOrder = $facade->getConfigurationValue(
            (new ConfigurationValueRequestTransfer())->setKey(static::KEY_SORT_ORDER),
        );

        // Assert
        $this->assertSame('50', $itemsPerPage);
        $this->assertSame('price_asc', $sortOrder);
    }

    public function testGetConfigurationValueDecryptsSecretValueTakenFromTheWarmedUpCache(): void
    {
        // Arrange
        $this->createConfigurationValueEntity(static::KEY_API_KEY, static::SCOPE_GLOBAL, 'encrypted-api-key');

        $facade = $this->createFacade();
        $configurationValueRequestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::KEY_API_KEY);

        // Act
        $configurationValue = $facade->getConfigurationValue($configurationValueRequestTransfer);

        // Assert
        $this->assertSame(static::DECRYPTED_SECRET, $configurationValue);
    }

    public function testGetConfigurationValueFallsBackToGlobalScopeWhenStoreScopeHasNoRow(): void
    {
        // Arrange
        $this->createConfigurationValueEntity(static::KEY_ITEMS_PER_PAGE, static::SCOPE_GLOBAL, '30');

        $facade = $this->createFacade();
        $configurationValueRequestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::KEY_ITEMS_PER_PAGE)
            ->addScope(
                (new ConfigurationScopeTransfer())
                    ->setKey(static::SCOPE_STORE)
                    ->setIdentifier('AT'),
            );

        // Act
        $configurationValue = $facade->getConfigurationValue($configurationValueRequestTransfer);

        // Assert
        $this->assertSame('30', $configurationValue);
    }

    public function testGetConfigurationValuePrefersStoreScopeOverGlobalScope(): void
    {
        // Arrange
        $this->createConfigurationValueEntity(static::KEY_ITEMS_PER_PAGE, static::SCOPE_GLOBAL, '30');
        $this->createConfigurationValueEntity(static::KEY_ITEMS_PER_PAGE, static::SCOPE_STORE, '100', 'DE');

        $facade = $this->createFacade();
        $configurationValueRequestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::KEY_ITEMS_PER_PAGE)
            ->addScope(
                (new ConfigurationScopeTransfer())
                    ->setKey(static::SCOPE_STORE)
                    ->setIdentifier('DE'),
            );

        // Act
        $configurationValue = $facade->getConfigurationValue($configurationValueRequestTransfer);

        // Assert
        $this->assertSame('100', $configurationValue);
    }

    public function testGetConfigurationValueReadsTheScopeOnceAndNeverFallsBackToPerKeyLookups(): void
    {
        // Arrange
        $configurationRepositoryMock = $this->createMock(ConfigurationRepository::class);
        $configurationRepositoryMock->expects($this->once())
            ->method('findAllConfigurationValuesByScope')
            ->with(static::SCOPE_GLOBAL, null)
            ->willReturn([
                static::KEY_ITEMS_PER_PAGE => (new ConfigurationValueTransfer())->setValue('50'),
            ]);
        $configurationRepositoryMock->expects($this->never())
            ->method('findConfigurationValueByKeyAndScope');

        $facade = $this->createFacade($configurationRepositoryMock);

        // Act
        $itemsPerPage = $facade->getConfigurationValue(
            (new ConfigurationValueRequestTransfer())->setKey(static::KEY_ITEMS_PER_PAGE),
        );
        $configurationValues = $facade->getConfigurationValues(
            (new ConfigurationValueRequestTransfer())->setKey(static::PREFIX_DISPLAY),
        );

        // Assert
        $this->assertSame('50', $itemsPerPage);
        $this->assertSame('50', $configurationValues['items_per_page']);
        $this->assertSame('name_asc', $configurationValues['sort_order']);
    }

    protected function createFacade(?ConfigurationRepository $configurationRepository = null): ConfigurationFacade
    {
        $factory = new ConfigurationBusinessFactory();
        $factory->setConfig($this->createConfigMock());

        if ($configurationRepository !== null) {
            $factory->setRepository($configurationRepository);
        }

        $this->tester->setDependency(ConfigurationDependencyProvider::PLUGINS_CONFIGURATION_VALUE_REQUEST_EXPANDER, []);
        $this->tester->setDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION, $this->createUtilEncryptionServiceMock());

        $facade = $this->tester->getFacade();
        $facade->setFactory($factory);

        return $facade;
    }

    protected function createConfigMock(): ConfigurationConfig
    {
        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('isCacheEnabled')->willReturn(true);
        $configMock->method('getScopeHierarchy')->willReturn([
            static::SCOPE_GLOBAL => null,
            static::SCOPE_STORE => static::SCOPE_GLOBAL,
        ]);
        $configMock->method('getAvailableScopes')->willReturn([static::SCOPE_GLOBAL, static::SCOPE_STORE]);
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
            static::SCOPE_GLOBAL => null,
            static::SCOPE_STORE => static::SCOPE_GLOBAL,
        ]);
        $sharedConfigMock->method('getAvailableScopes')->willReturn([static::SCOPE_GLOBAL, static::SCOPE_STORE]);

        return $sharedConfigMock;
    }

    protected function createUtilEncryptionServiceMock(): UtilEncryptionServiceInterface
    {
        $utilEncryptionServiceMock = $this->createMock(UtilEncryptionServiceInterface::class);
        $utilEncryptionServiceMock->method('decryptOpenSsl')->willReturn(static::DECRYPTED_SECRET);

        return $utilEncryptionServiceMock;
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
