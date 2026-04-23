<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Plugin\Application;

use Codeception\Test\Unit;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Zed\Configuration\Communication\Plugin\Application\ConfigurationApplicationPlugin;
use SprykerTest\Zed\Configuration\ConfigurationCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Communication
 * @group Plugin
 * @group Application
 * @group ConfigurationApplicationPluginTest
 * Add your own group annotations below this line
 */
class ConfigurationApplicationPluginTest extends Unit
{
    protected ConfigurationCommunicationTester $tester;

    public function testProvideShouldRegisterConfigurationServiceInContainer(): void
    {
        // Arrange
        $plugin = new ConfigurationApplicationPlugin();
        $containerMock = $this->createMock(ContainerInterface::class);

        // Expect
        $containerMock->expects($this->once())
            ->method('set')
            ->with('configuration', $this->isCallable());

        // Act
        $plugin->provide($containerMock);
    }

    public function testProvideReturnsContainerInstance(): void
    {
        // Arrange
        $plugin = new ConfigurationApplicationPlugin();
        $containerMock = $this->createMock(ContainerInterface::class);

        // Act
        $result = $plugin->provide($containerMock);

        // Assert
        $this->assertSame($containerMock, $result);
    }

    public function testConfigurationServiceClosureIsRegistered(): void
    {
        // Arrange
        $plugin = new ConfigurationApplicationPlugin();

        $capturedClosure = null;
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())
            ->method('set')
            ->with('configuration', $this->isCallable())
            ->willReturnCallback(function (string $key, callable $factory) use (&$capturedClosure): void {
                $capturedClosure = $factory;
            });

        // Act
        $plugin->provide($containerMock);

        // Assert
        $this->assertIsCallable($capturedClosure);
    }
}
