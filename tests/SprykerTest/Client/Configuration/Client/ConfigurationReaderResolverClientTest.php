<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Configuration\Client;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use ReflectionProperty;
use Spryker\Client\Configuration\Dependency\Facade\ConfigurationFacadeBridge;
use Spryker\Client\Configuration\Reader\ConfigurationReaderInterface;
use Spryker\Client\Configuration\Reader\ConfigurationReaderResolver;
use Spryker\Shared\Configuration\Reader\AbstractConfigurationValueResolver;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use SprykerTest\Client\Configuration\ConfigurationClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Configuration
 * @group Client
 * @group ConfigurationReaderResolverClientTest
 * Add your own group annotations below this line
 */
class ConfigurationReaderResolverClientTest extends Unit
{
    protected const string SETTING_KEY = 'catalog:general:display:items_per_page';

    protected const string STORED_VALUE = '24';

    protected const string FACADE_VALUE = '48';

    protected ConfigurationClientTester $tester;

    protected function _before(): void
    {
        $this->clearStaticCaches();
        $this->tester->mockFacadeReaderPathAsUnavailable($this->createMock(ConfigurationReaderInterface::class));
    }

    public function testResolverDelegatesToFacadeReaderWhenServiceIsProvided(): void
    {
        // Arrange
        $facadeReaderMock = $this->createMock(ConfigurationReaderInterface::class);
        $facadeReaderMock->expects($this->once())
            ->method('getConfigurationValue')
            ->willReturn(static::FACADE_VALUE);

        $storageReaderMock = $this->createMock(ConfigurationReaderInterface::class);
        $storageReaderMock->expects($this->never())
            ->method('getConfigurationValue');

        $resolver = new ConfigurationReaderResolver($storageReaderMock, $facadeReaderMock, true);
        $this->tester->mockFactoryMethod('createConfigurationStorageReader', $resolver);

        $requestTransfer = (new ConfigurationValueRequestTransfer())->setKey(static::SETTING_KEY);

        // Act
        $result = $this->tester->getClient()->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertSame(static::FACADE_VALUE, $result);
    }

    public function testResolverDelegatesToStorageReaderWhenServiceIsNotProvided(): void
    {
        // Arrange
        $storageReaderMock = $this->createMock(ConfigurationReaderInterface::class);
        $storageReaderMock->expects($this->once())
            ->method('getConfigurationValue')
            ->willReturn(static::STORED_VALUE);

        $facadeReaderMock = $this->createMock(ConfigurationReaderInterface::class);
        $facadeReaderMock->expects($this->never())
            ->method('getConfigurationValue');

        $resolver = new ConfigurationReaderResolver($storageReaderMock, $facadeReaderMock, false);
        $this->tester->mockFactoryMethod('createConfigurationStorageReader', $resolver);

        $requestTransfer = (new ConfigurationValueRequestTransfer())->setKey(static::SETTING_KEY);

        // Act
        $result = $this->tester->getClient()->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertSame(static::STORED_VALUE, $result);
    }

    public function testGetConfigurationValuesDelegatesToFacadeReaderWhenServiceIsProvided(): void
    {
        // Arrange
        $expected = [static::SETTING_KEY => static::FACADE_VALUE];

        $facadeReaderMock = $this->createMock(ConfigurationReaderInterface::class);
        $facadeReaderMock->expects($this->once())
            ->method('getConfigurationValues')
            ->willReturn($expected);

        $storageReaderMock = $this->createMock(ConfigurationReaderInterface::class);

        $resolver = new ConfigurationReaderResolver($storageReaderMock, $facadeReaderMock, true);
        $this->tester->mockFactoryMethod('createConfigurationStorageReader', $resolver);

        $requestTransfer = (new ConfigurationValueRequestTransfer())->setKey('catalog');

        // Act
        $result = $this->tester->getClient()->getConfigurationValues($requestTransfer);

        // Assert
        $this->assertSame($expected, $result);
    }

    public function testGetStorageDataForScopeAlwaysUsesStorageReader(): void
    {
        // Arrange
        $storageReaderMock = $this->createMock(ConfigurationReaderInterface::class);
        $storageReaderMock->expects($this->once())
            ->method('getStorageDataForScope')
            ->with('global', null)
            ->willReturn(['key' => 'value']);

        $facadeReaderMock = $this->createMock(ConfigurationReaderInterface::class);

        $resolver = new ConfigurationReaderResolver($storageReaderMock, $facadeReaderMock, false);
        $this->tester->mockFactoryMethod('createConfigurationStorageReader', $resolver);

        // Act
        $result = $this->tester->getClient()->getStorageDataForScope('global');

        // Assert
        $this->assertSame(['key' => 'value'], $result);
    }

    public function testConfigurationFacadeReaderDelegatesToFacade(): void
    {
        // Arrange
        $facadeMock = $this->createMock(ConfigurationFacadeInterface::class);
        $facadeMock->expects($this->once())
            ->method('getConfigurationValue')
            ->willReturn(static::FACADE_VALUE);

        $reader = new ConfigurationFacadeBridge($facadeMock);

        $requestTransfer = (new ConfigurationValueRequestTransfer())->setKey(static::SETTING_KEY);

        // Act
        $result = $reader->getConfigurationValue($requestTransfer);

        // Assert
        $this->assertSame(static::FACADE_VALUE, $result);
    }

    public function testConfigurationFacadeReaderGetStorageDataForScopeReturnsEmptyArray(): void
    {
        // Arrange
        $facadeMock = $this->createMock(ConfigurationFacadeInterface::class);
        $reader = new ConfigurationFacadeBridge($facadeMock);

        // Act
        $result = $reader->getStorageDataForScope('global');

        // Assert
        $this->assertSame([], $result);
    }

    protected function clearStaticCaches(): void
    {
        $settingCache = new ReflectionProperty(AbstractConfigurationValueResolver::class, 'settingCache');
        $settingCache->setValue(null, null);

        $resolvedValueCache = new ReflectionProperty(AbstractConfigurationValueResolver::class, 'resolvedValueCache');
        $resolvedValueCache->setValue(null, []);
    }
}
