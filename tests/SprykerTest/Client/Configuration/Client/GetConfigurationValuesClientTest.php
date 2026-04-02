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
use Spryker\Client\Configuration\Reader\ConfigurationStorageReader;
use Spryker\Client\Configuration\Reader\ConfigurationStorageReaderInterface;
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
 * @group GetConfigurationValuesClientTest
 * Add your own group annotations below this line
 */
class GetConfigurationValuesClientTest extends Unit
{
    /**
     * @var string
     */
    protected const PREFIX_COLORS = 'theme:storefront:colors';

    /**
     * @var string
     */
    protected const PREFIX_UNKNOWN = 'unknown:prefix:that:matches:nothing';

    /**
     * @var array<string, array<string, mixed>>
     */
    protected const TEST_SETTINGS_MAP = [
        'theme:storefront:colors:yves_main_color' => [
            'type' => 'string',
            'default_value' => '#ffffff',
            'secret' => false,
            'storefront' => true,
            'scopes' => ['global', 'store'],
            'constraints' => [],
            'sanitize_xss' => [],
        ],
        'theme:storefront:colors:yves_bg_color' => [
            'type' => 'string',
            'default_value' => '#f0f0f0',
            'secret' => false,
            'storefront' => true,
            'scopes' => ['global', 'store'],
            'constraints' => [],
            'sanitize_xss' => [],
        ],
        'theme:storefront:typography:body_font' => [
            'type' => 'string',
            'default_value' => 'Arial',
            'secret' => false,
            'storefront' => true,
            'scopes' => ['global'],
            'constraints' => [],
            'sanitize_xss' => [],
        ],
    ];

    protected ConfigurationClientTester $tester;

    protected function _before(): void
    {
        $this->clearStaticCaches();
        $this->mockStorageReader(static::TEST_SETTINGS_MAP, []);
    }

    public function testGetConfigurationValuesReturnsDefaultsWhenStorageIsEmpty(): void
    {
        // Arrange
        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::PREFIX_COLORS);

        // Act
        $result = $this->tester->getClient()->getConfigurationValues($requestTransfer);

        // Assert
        $this->assertArrayHasKey('yves_main_color', $result);
        $this->assertArrayHasKey('yves_bg_color', $result);
        $this->assertSame('#ffffff', $result['yves_main_color']);
        $this->assertSame('#f0f0f0', $result['yves_bg_color']);
    }

    public function testGetConfigurationValuesReturnsStoredValuesOverDefaults(): void
    {
        // Arrange
        $this->mockStorageReader(static::TEST_SETTINGS_MAP, [
            'theme:storefront:colors:yves_main_color' => '#000000',
        ]);

        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::PREFIX_COLORS);

        // Act
        $result = $this->tester->getClient()->getConfigurationValues($requestTransfer);

        // Assert
        $this->assertSame('#000000', $result['yves_main_color']);
        $this->assertSame('#f0f0f0', $result['yves_bg_color']);
    }

    public function testGetConfigurationValuesReturnsOnlyKeysMatchingPrefix(): void
    {
        // Arrange
        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::PREFIX_COLORS);

        // Act
        $result = $this->tester->getClient()->getConfigurationValues($requestTransfer);

        // Assert — typography key must not appear in colors result
        $this->assertArrayNotHasKey('body_font', $result);
        $this->assertCount(2, $result);
    }

    public function testGetConfigurationValuesReturnsEmptyArrayForUnmatchedPrefix(): void
    {
        // Arrange
        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::PREFIX_UNKNOWN);

        // Act
        $result = $this->tester->getClient()->getConfigurationValues($requestTransfer);

        // Assert
        $this->assertSame([], $result);
    }

    public function testGetConfigurationValuesResolvesScopePerKey(): void
    {
        // Arrange
        $this->mockStorageReader(
            static::TEST_SETTINGS_MAP,
            globalStorageData: ['theme:storefront:colors:yves_bg_color' => '#global'],
            storeStorageData: ['theme:storefront:colors:yves_main_color' => '#store'],
        );

        $requestTransfer = (new ConfigurationValueRequestTransfer())
            ->setKey(static::PREFIX_COLORS)
            ->addScope(
                (new ConfigurationScopeTransfer())
                    ->setKey('store')
                    ->setIdentifier('DE'),
            );

        // Act
        $result = $this->tester->getClient()->getConfigurationValues($requestTransfer);

        // Assert
        $this->assertSame('#store', $result['yves_main_color']);
        $this->assertSame('#global', $result['yves_bg_color']);
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
    ): ConfigurationStorageReaderInterface {
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
