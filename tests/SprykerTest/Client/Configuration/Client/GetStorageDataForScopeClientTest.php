<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Configuration\Client;

use Codeception\Test\Unit;
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
 * @group GetStorageDataForScopeClientTest
 * Add your own group annotations below this line
 */
class GetStorageDataForScopeClientTest extends Unit
{
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
    protected const STORE_IDENTIFIER_DE = 'DE';

    protected ConfigurationClientTester $tester;

    protected function _before(): void
    {
        $this->clearStaticCaches();
        $this->tester->mockFacadeReaderPathAsUnavailable($this->createMock(ConfigurationReaderInterface::class));
    }

    public function testGetStorageDataForScopeReturnsDataForGlobalScope(): void
    {
        // Arrange
        $expectedData = [
            'theme:storefront:colors:yves_main_color' => '#ffffff',
            'theme:storefront:colors:yves_bg_color' => '#f0f0f0',
        ];

        $this->mockStorageReader(globalStorageData: $expectedData);

        // Act
        $result = $this->tester->getClient()->getStorageDataForScope(static::SCOPE_GLOBAL);

        // Assert
        $this->assertSame($expectedData, $result);
    }

    public function testGetStorageDataForScopeReturnsDataForScopeWithIdentifier(): void
    {
        // Arrange
        $expectedData = [
            'theme:storefront:colors:yves_main_color' => '#000000',
        ];

        $this->mockStorageReader(storeStorageData: $expectedData);

        // Act
        $result = $this->tester->getClient()->getStorageDataForScope(static::SCOPE_STORE, static::STORE_IDENTIFIER_DE);

        // Assert
        $this->assertSame($expectedData, $result);
    }

    public function testGetStorageDataForScopeReturnsEmptyArrayWhenStorageHasNoData(): void
    {
        // Arrange
        $this->mockStorageReader(globalStorageData: []);

        // Act
        $result = $this->tester->getClient()->getStorageDataForScope(static::SCOPE_GLOBAL);

        // Assert
        $this->assertSame([], $result);
    }

    public function testGetStorageDataForScopeReturnsEmptyArrayWhenStorageReturnsNull(): void
    {
        // Arrange
        $this->mockStorageReaderWithNullStorage();

        // Act
        $result = $this->tester->getClient()->getStorageDataForScope(static::SCOPE_GLOBAL);

        // Assert
        $this->assertSame([], $result);
    }

    public function testGetStorageDataForScopeUsesInMemoryCacheForRepeatedCalls(): void
    {
        // Arrange
        $storageClientMock = $this->createMock(StorageClientInterface::class);
        $storageClientMock->expects($this->once())
            ->method('get')
            ->willReturn(['key' => 'value']);

        $this->mockStorageReaderWithStorageClient($storageClientMock);

        // Act
        $this->tester->getClient()->getStorageDataForScope(static::SCOPE_GLOBAL);
        $result = $this->tester->getClient()->getStorageDataForScope(static::SCOPE_GLOBAL);

        // Assert
        $this->assertSame(['key' => 'value'], $result);
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
     * @param array<string, string> $globalStorageData
     * @param array<string, string> $storeStorageData
     */
    protected function mockStorageReader(
        array $globalStorageData = [],
        array $storeStorageData = [],
    ): void {
        $storageClientMock = $this->createMock(StorageClientInterface::class);
        $storageClientMock->method('get')->willReturnCallback(
            function (string $key) use ($globalStorageData, $storeStorageData): array {
                return str_contains($key, 'store') ? $storeStorageData : $globalStorageData;
            },
        );

        $this->tester->mockFactoryMethod(
            'createConfigurationStorageReader',
            $this->createStorageReaderWithStorageClient($storageClientMock),
        );
    }

    protected function mockStorageReaderWithNullStorage(): void
    {
        $storageClientMock = $this->createMock(StorageClientInterface::class);
        $storageClientMock->method('get')->willReturn(null);

        $this->tester->mockFactoryMethod(
            'createConfigurationStorageReader',
            $this->createStorageReaderWithStorageClient($storageClientMock),
        );
    }

    protected function mockStorageReaderWithStorageClient(StorageClientInterface $storageClientMock): void
    {
        $this->tester->mockFactoryMethod(
            'createConfigurationStorageReader',
            $this->createStorageReaderWithStorageClient($storageClientMock),
        );
    }

    protected function createStorageReaderWithStorageClient(StorageClientInterface $storageClientMock): ConfigurationReaderInterface
    {
        $sharedConfigMock = $this->createMock(SprykerConfigurationConfig::class);
        $sharedConfigMock->method('getScopeHierarchy')->willReturn([
            'global' => null,
            'store' => 'global',
        ]);

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
            $this->createMock(ConfigurationSchemaReaderInterface::class),
            $this->createMock(ConfigurationValueEncryptorInterface::class),
            $storageClientMock,
            $syncServiceMock,
            [],
        );
    }
}
