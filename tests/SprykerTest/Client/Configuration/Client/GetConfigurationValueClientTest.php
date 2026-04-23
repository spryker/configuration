<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Configuration\Client;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationScopeTransfer;
use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Generated\Shared\Transfer\SynchronizationDataTransfer;
use ReflectionProperty;
use Spryker\Client\Configuration\Reader\ConfigurationReaderInterface;
use Spryker\Client\Configuration\Reader\ConfigurationStorageReader;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Service\Synchronization\Dependency\Plugin\SynchronizationKeyGeneratorPluginInterface;
use Spryker\Service\Synchronization\SynchronizationServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConfig as SprykerConfigurationConfig;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptorInterface;
use Spryker\Shared\Configuration\Reader\AbstractConfigurationValueResolver;
use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReaderInterface;
use SprykerTest\Client\Configuration\ConfigurationClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Configuration
 * @group Client
 * @group GetConfigurationValueClientTest
 * Add your own group annotations below this line
 */
class GetConfigurationValueClientTest extends Unit
{
    /**
     * @var string
     */
    protected const SETTING_KEY_MAIN_COLOR = 'theme:storefront:colors:yves_main_color';

    /**
     * @var string
     */
    protected const SETTING_KEY_UNKNOWN = 'unknown:key:that:does:not:exist';

    /**
     * @var string
     */
    protected const DEFAULT_COLOR = '#ffffff';

    /**
     * @var string
     */
    protected const STORED_GLOBAL_COLOR = '#000000';

    /**
     * @var string
     */
    protected const STORED_STORE_COLOR = '#aabbcc';

    /**
     * @var array<string, array<string, mixed>>
     */
    protected const TEST_SETTINGS_MAP = [
        'theme:storefront:colors:yves_main_color' => [
            'type' => 'string',
            'default_value' => self::DEFAULT_COLOR,
            'secret' => false,
            'storefront' => true,
            'scopes' => ['global', 'store'],
            'constraints' => [],
            'sanitize_xss' => [],
        ],
    ];

    protected ConfigurationClientTester $tester;

    protected function _before(): void
    {
        $this->clearStaticCaches();
        $this->tester->mockFacadeReaderPathAsUnavailable($this->createMock(ConfigurationReaderInterface::class));
        $this->mockStorageReader(static::TEST_SETTINGS_MAP, []);
    }

    public function testGetConfigurationValueReturnsStoredGlobalValue(): void
    {
        // Arrange
        $this->mockStorageReader(static::TEST_SETTINGS_MAP, [
            static::SETTING_KEY_MAIN_COLOR => static::STORED_GLOBAL_COLOR,
        ]);

        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::SETTING_KEY_MAIN_COLOR);

        // Act
        $result = $this->tester->getClient()->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertSame(static::STORED_GLOBAL_COLOR, $result);
    }

    public function testGetConfigurationValueReturnsDefaultWhenKeyNotInStorage(): void
    {
        // Arrange
        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::SETTING_KEY_MAIN_COLOR);

        // Act
        $result = $this->tester->getClient()->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertSame(static::DEFAULT_COLOR, $result);
    }

    public function testGetConfigurationValueReturnsNullForUnknownSchemaKey(): void
    {
        // Arrange
        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::SETTING_KEY_UNKNOWN);

        // Act
        $result = $this->tester->getClient()->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertNull($result);
    }

    public function testGetConfigurationValueResolvesMostSpecificScopeFirst(): void
    {
        // Arrange
        $this->mockStorageReader(
            static::TEST_SETTINGS_MAP,
            globalStorageData: [],
            storeStorageData: [static::SETTING_KEY_MAIN_COLOR => static::STORED_STORE_COLOR],
        );

        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::SETTING_KEY_MAIN_COLOR)
            ->addScope(
                (new ConfigurationScopeTransfer())
                    ->setKey('store')
                    ->setIdentifier('DE'),
            );

        // Act
        $result = $this->tester->getClient()->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertSame(static::STORED_STORE_COLOR, $result);
    }

    public function testGetConfigurationValueFallsBackToGlobalWhenStoreScopeEmpty(): void
    {
        // Arrange
        $this->mockStorageReader(
            static::TEST_SETTINGS_MAP,
            globalStorageData: [static::SETTING_KEY_MAIN_COLOR => static::STORED_GLOBAL_COLOR],
            storeStorageData: [],
        );

        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::SETTING_KEY_MAIN_COLOR)
            ->addScope(
                (new ConfigurationScopeTransfer())
                    ->setKey('store')
                    ->setIdentifier('DE'),
            );

        // Act
        $result = $this->tester->getClient()->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertSame(static::STORED_GLOBAL_COLOR, $result);
    }

    protected function clearStaticCaches(): void
    {
        $settingCache = new ReflectionProperty(AbstractConfigurationValueResolver::class, 'settingCache');
        $settingCache->setValue(null, null);

        $resolvedValueCache = new ReflectionProperty(AbstractConfigurationValueResolver::class, 'resolvedValueCache');
        $resolvedValueCache->setValue(null, []);

        $storageDataCache = new ReflectionProperty(ConfigurationStorageReader::class, 'storageDataCache');
        $storageDataCache->setValue(null, []);
    }

    /**
     * @param array<string, array<string, mixed>> $settingsMap
     * @param array<string, string> $globalStorageData
     * @param array<string, string> $storeStorageData
     */
    protected function mockStorageReader(
        array $settingsMap,
        array $globalStorageData,
        array $storeStorageData = [],
    ): void {
        $this->tester->mockFactoryMethod(
            'createConfigurationStorageReader',
            $this->createStorageReader($settingsMap, $globalStorageData, $storeStorageData),
        );
    }

    /**
     * @param array<string, array<string, mixed>> $settingsMap
     * @param array<string, string> $globalStorageData
     * @param array<string, string> $storeStorageData
     */
    protected function createStorageReader(
        array $settingsMap,
        array $globalStorageData,
        array $storeStorageData,
    ): ConfigurationReaderInterface {
        $sharedConfigMock = $this->createMock(SprykerConfigurationConfig::class);
        $sharedConfigMock->method('getScopeHierarchy')->willReturn([
            'global' => null,
            'store' => 'global',
        ]);

        $schemaReaderMock = $this->createMock(ConfigurationSchemaReaderInterface::class);
        $schemaReaderMock->method('getSettingsMap')->willReturn($settingsMap);

        $storageClientMock = $this->createMock(StorageClientInterface::class);
        $storageClientMock->method('get')->willReturnCallback(
            function (string $key) use ($globalStorageData, $storeStorageData): array {
                return str_contains($key, 'store') ? $storeStorageData : $globalStorageData;
            },
        );

        $keyGeneratorMock = $this->createMock(SynchronizationKeyGeneratorPluginInterface::class);
        $keyGeneratorMock->method('generateKey')->willReturnCallback(
            function (SynchronizationDataTransfer $transfer): string {
                return 'configuration:' . $transfer->getReference();
            },
        );

        $syncServiceMock = $this->createMock(SynchronizationServiceInterface::class);
        $syncServiceMock->method('getStorageKeyBuilder')->willReturn($keyGeneratorMock);

        return new ConfigurationStorageReader(
            $sharedConfigMock,
            $schemaReaderMock,
            $this->createMock(ConfigurationValueEncryptorInterface::class),
            $storageClientMock,
            $syncServiceMock,
            [],
        );
    }
}
