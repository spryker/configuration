<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Console;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationErrorTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use RuntimeException;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory;
use Spryker\Zed\Configuration\Communication\Reader\EnvironmentConfigurationValueReader;
use Spryker\Zed\Configuration\ConfigurationConfig;
use SprykerTest\Zed\Configuration\ConfigurationCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Communication
 * @group Console
 * @group ImportEnvironmentConfigurationConsoleTest
 * Add your own group annotations below this line
 */
class ImportEnvironmentConfigurationConsoleTest extends Unit
{
    protected ConfigurationCommunicationTester $tester;

    protected const string ENV_VAR_PREFIX = 'configuration:';

    protected const string SCOPE_STORE = 'store';

    protected const string SCOPE_LOCALE = 'locale';

    protected const string SETTING_KEY = 'integrations:google_analytics:tracking:measurement_id';

    protected const string SECRET_SETTING_KEY = 'integrations:google_analytics:connection:service_account_credentials_json';

    protected const string SECRET_VALUE = 'a-service-account-key-that-must-never-be-printed';

    public function testImportsANonSecretValueWithItsScopeIdentifier(): void
    {
        // Arrange
        $savedTransfers = [];
        $console = $this->createConsole($this->createCapturingFacadeMock($savedTransfers));
        $variableName = static::ENV_VAR_PREFIX . static::SCOPE_STORE . ':DE:' . static::SETTING_KEY;
        putenv(sprintf('%s=%s', $variableName, 'G-R9NCW72D85'));

        // Act
        $console->importEnvironmentVariableForTest($variableName, []);
        putenv($variableName);

        // Assert
        $this->assertCount(1, $savedTransfers);
        $this->assertSame(static::SCOPE_STORE, $savedTransfers[0]->getScope());
        $this->assertSame('DE', $savedTransfers[0]->getScopeIdentifier());
        $this->assertSame(static::SETTING_KEY, $savedTransfers[0]->getSettingKey());
        $this->assertSame('G-R9NCW72D85', $savedTransfers[0]->getValue());
    }

    public function testImportsASecretSettingThroughTheSameCommand(): void
    {
        // Arrange
        $savedTransfers = [];
        $console = $this->createConsole($this->createCapturingFacadeMock($savedTransfers));
        $variableName = static::ENV_VAR_PREFIX . ConfigurationConstants::SCOPE_GLOBAL . ':' . static::SECRET_SETTING_KEY;
        putenv(sprintf('%s=%s', $variableName, static::SECRET_VALUE));

        // Act
        $console->importEnvironmentVariableForTest($variableName, [static::SECRET_SETTING_KEY]);
        putenv($variableName);

        // Assert
        $this->assertCount(1, $savedTransfers);
        $this->assertSame(static::SECRET_SETTING_KEY, $savedTransfers[0]->getSettingKey());
        $this->assertSame(static::SECRET_VALUE, $savedTransfers[0]->getValue());
        $this->assertStringNotContainsString(static::SECRET_VALUE, $console->getOutputForTest());
    }

    public function testRefusesAnUnparsableNameAndSaysWhy(): void
    {
        // Arrange
        $savedTransfers = [];
        $console = $this->createConsole($this->createCapturingFacadeMock($savedTransfers));
        $variableName = static::ENV_VAR_PREFIX . static::SETTING_KEY;
        putenv(sprintf('%s=%s', $variableName, 'G-R9NCW72D85'));

        // Act
        $console->importEnvironmentVariableForTest($variableName, []);
        putenv($variableName);

        // Assert
        $this->assertSame([], $savedTransfers);
        $this->assertStringContainsString('does not resolve', $console->getOutputForTest());
    }

    public function testReportsAVariableThatIsNotSetHere(): void
    {
        // Arrange
        $savedTransfers = [];
        $console = $this->createConsole($this->createCapturingFacadeMock($savedTransfers));

        // Act
        $console->importEnvironmentVariableForTest(
            static::ENV_VAR_PREFIX . ConfigurationConstants::SCOPE_GLOBAL . ':catalog:absent',
            [],
        );

        // Assert
        $this->assertSame([], $savedTransfers);
        $this->assertStringContainsString('not set in this environment', $console->getOutputForTest());
    }

    public function testRefusesANameWithoutThePrefix(): void
    {
        // Arrange
        $savedTransfers = [];
        $console = $this->createConsole($this->createCapturingFacadeMock($savedTransfers));

        // Act
        $console->importEnvironmentVariableForTest('SPRYKER_UNRELATED_VARIABLE', []);

        // Assert
        $this->assertSame([], $savedTransfers);
        $this->assertStringContainsString('must start with', $console->getOutputForTest());
    }

    /**
     * @dataProvider getFailingFacades
     */
    public function testAFailedSecretImportNeverPrintsTheValue(callable $facadeFactory): void
    {
        // Arrange
        $console = $this->createConsole($facadeFactory($this));

        // Act
        $console->saveConfigurationValueForTest($this->createSecretValueTransfer(), true);

        // Assert
        $output = $console->getOutputForTest();
        $this->assertStringNotContainsString(static::SECRET_VALUE, $output);
        $this->assertStringContainsString(static::SECRET_SETTING_KEY, $output);
        $this->assertStringContainsString('suppressed', $output);
    }

    /**
     * @dataProvider getFailingFacades
     */
    public function testAFailedNonSecretImportReportsTheDetailVerbatim(callable $facadeFactory): void
    {
        // Arrange
        $console = $this->createConsole($facadeFactory($this));

        // Act
        $console->saveConfigurationValueForTest($this->createSecretValueTransfer(), false);

        // Assert
        $output = $console->getOutputForTest();
        $this->assertStringContainsString(static::SECRET_VALUE, $output);
        $this->assertStringNotContainsString('suppressed', $output);
        $this->assertStringContainsString('still exits successfully', $output);
    }

    /**
     * @return array<string, array<int, callable>>
     */
    public function getFailingFacades(): array
    {
        return [
            'the facade throws, and the exception message could quote the value' => [
                static function (self $test): ConfigurationFacade {
                    $facadeMock = $test->createMock(ConfigurationFacade::class);
                    $facadeMock->method('saveConfigurationValues')
                        ->willThrowException(new RuntimeException('PDO error near ' . static::SECRET_VALUE));

                    return $facadeMock;
                },
            ],
            'validation fails, and the violation message could interpolate the value' => [
                static function (self $test): ConfigurationFacade {
                    $facadeMock = $test->createMock(ConfigurationFacade::class);
                    $facadeMock->method('saveConfigurationValues')->willReturn(
                        (new ConfigurationValueCollectionResponseTransfer())
                            ->setIsSuccess(false)
                            ->addError(
                                (new ConfigurationErrorTransfer())
                                    ->setSettingKey(static::SECRET_SETTING_KEY)
                                    ->setMessage('The value ' . static::SECRET_VALUE . ' is not valid JSON.'),
                            ),
                    );

                    return $facadeMock;
                },
            ],
        ];
    }

    protected function createSecretValueTransfer(): ConfigurationValueTransfer
    {
        return (new ConfigurationValueTransfer())
            ->setSettingKey(static::SECRET_SETTING_KEY)
            ->setScope(ConfigurationConstants::SCOPE_GLOBAL)
            ->setValue(static::SECRET_VALUE);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ConfigurationValueTransfer> $savedTransfers
     */
    protected function createCapturingFacadeMock(array &$savedTransfers): ConfigurationFacade
    {
        $configurationFacadeMock = $this->createMock(ConfigurationFacade::class);
        $configurationFacadeMock->method('saveConfigurationValues')->willReturnCallback(
            static function (ConfigurationValueCollectionRequestTransfer $requestTransfer) use (&$savedTransfers): ConfigurationValueCollectionResponseTransfer {
                foreach ($requestTransfer->getConfigurationValues() as $configurationValueTransfer) {
                    $savedTransfers[] = $configurationValueTransfer;
                }

                return (new ConfigurationValueCollectionResponseTransfer())
                    ->setIsSuccess(true)
                    ->setSavedCount(count($savedTransfers));
            },
        );

        return $configurationFacadeMock;
    }

    protected function createConsole(?ConfigurationFacade $configurationFacade = null): ImportEnvironmentConfigurationConsoleStub
    {
        $configurationConfigMock = $this->createMock(ConfigurationConfig::class);
        $configurationConfigMock->method('getAvailableScopes')->willReturn([
            ConfigurationConstants::SCOPE_GLOBAL,
            static::SCOPE_STORE,
            static::SCOPE_LOCALE,
        ]);

        $configurationCommunicationFactoryMock = $this->createMock(ConfigurationCommunicationFactory::class);
        $configurationCommunicationFactoryMock->method('createEnvironmentConfigurationValueReader')
            ->willReturn(new EnvironmentConfigurationValueReader($configurationConfigMock));

        return new ImportEnvironmentConfigurationConsoleStub($configurationCommunicationFactoryMock, $configurationFacade);
    }
}
