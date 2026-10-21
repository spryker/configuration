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

    protected const string SCOPE_STORE = 'store';

    protected const string SETTING_KEY = 'integrations:google_analytics:tracking:measurement_id';

    protected const string SECRET_SETTING_KEY = 'integrations:google_analytics:connection:service_account_credentials_json';

    protected const string VARIABLE_NAME = 'GA_MEASUREMENT_ID';

    protected const string SECRET_VARIABLE_NAME = 'GA_SERVICE_ACCOUNT_JSON';

    protected const string SECRET_VALUE = 'a-service-account-key-that-must-never-be-printed';

    public function testImportsANonSecretValueAtTheGivenScope(): void
    {
        // Arrange
        $savedTransfers = [];
        $console = $this->createConsole($this->createCapturingFacadeMock($savedTransfers));
        putenv(sprintf('%s=%s', static::VARIABLE_NAME, 'G-R9NCW72D85'));

        // Act
        $failedMappingCount = $console->importMappingsForTest(
            [static::SETTING_KEY . '=' . static::VARIABLE_NAME],
            static::SCOPE_STORE,
            'DE',
            $this->createSettingsMap(),
            [],
        );
        putenv(static::VARIABLE_NAME);

        // Assert
        $this->assertSame(0, $failedMappingCount);
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
        putenv(sprintf('%s=%s', static::SECRET_VARIABLE_NAME, static::SECRET_VALUE));

        // Act
        $failedMappingCount = $console->importMappingsForTest(
            [static::SECRET_SETTING_KEY . '=' . static::SECRET_VARIABLE_NAME],
            ConfigurationConstants::SCOPE_GLOBAL,
            null,
            $this->createSettingsMap(),
            [static::SECRET_SETTING_KEY],
        );
        putenv(static::SECRET_VARIABLE_NAME);

        // Assert
        $this->assertSame(0, $failedMappingCount);
        $this->assertCount(1, $savedTransfers);
        $this->assertSame(static::SECRET_SETTING_KEY, $savedTransfers[0]->getSettingKey());
        $this->assertSame(static::SECRET_VALUE, $savedTransfers[0]->getValue());
        $this->assertStringNotContainsString(static::SECRET_VALUE, $console->getOutputForTest());
    }

    public function testRefusesAMappingWithoutASeparatorAndSaysWhy(): void
    {
        // Arrange
        $savedTransfers = [];
        $console = $this->createConsole($this->createCapturingFacadeMock($savedTransfers));

        // Act
        $failedMappingCount = $console->importMappingsForTest(
            [static::SETTING_KEY],
            ConfigurationConstants::SCOPE_GLOBAL,
            null,
            $this->createSettingsMap(),
            [],
        );

        // Assert
        $this->assertSame(1, $failedMappingCount);
        $this->assertSame([], $savedTransfers);
        $this->assertStringContainsString('expected "<setting key>=<VARIABLE>"', $console->getOutputForTest());
    }

    /**
     * The mapping reads like an assignment, so passing the value instead of the variable name is an easy mistake.
     * It must be refused, and the value must not be echoed into the deployment log.
     */
    public function testRefusesAValuePassedInsteadOfAVariableNameWithoutPrintingIt(): void
    {
        // Arrange
        $savedTransfers = [];
        $console = $this->createConsole($this->createCapturingFacadeMock($savedTransfers));

        // Act
        $failedMappingCount = $console->importMappingsForTest(
            [static::SECRET_SETTING_KEY . '={"private_key":"' . static::SECRET_VALUE . '"}'],
            ConfigurationConstants::SCOPE_GLOBAL,
            null,
            $this->createSettingsMap(),
            [static::SECRET_SETTING_KEY],
        );

        // Assert
        $output = $console->getOutputForTest();
        $this->assertSame(1, $failedMappingCount);
        $this->assertSame([], $savedTransfers);
        $this->assertStringNotContainsString(static::SECRET_VALUE, $output);
        $this->assertStringContainsString('never the value', $output);
    }

    /**
     * A value made only of letters, digits and underscores passes the variable name check, is looked up as a
     * variable and not found. It must still never be printed.
     *
     * @dataProvider getSecretFlags
     */
    public function testNeverPrintsAValueThatLooksLikeAVariableName(bool $isSecret): void
    {
        // Arrange
        $savedTransfers = [];
        $console = $this->createConsole($this->createCapturingFacadeMock($savedTransfers));
        $valueThatLooksLikeAName = 'sk_live_51HxYzSecretTokenThatMustNeverBePrinted';
        $settingKey = $isSecret ? static::SECRET_SETTING_KEY : static::SETTING_KEY;

        // Act
        $failedMappingCount = $console->importMappingsForTest(
            [$settingKey . '=' . $valueThatLooksLikeAName],
            ConfigurationConstants::SCOPE_GLOBAL,
            null,
            $this->createSettingsMap(),
            $isSecret ? [static::SECRET_SETTING_KEY] : [],
        );

        // Assert
        $this->assertSame(0, $failedMappingCount);
        $this->assertStringNotContainsString($valueThatLooksLikeAName, $console->getOutputForTest());
    }

    /**
     * @return array<string, array<int, bool>>
     */
    public function getSecretFlags(): array
    {
        return [
            'secret setting' => [true],
            'non-secret setting' => [false],
        ];
    }

    public function testRefusesASettingKeyTheSchemaDoesNotDefine(): void
    {
        // Arrange
        $savedTransfers = [];
        $console = $this->createConsole($this->createCapturingFacadeMock($savedTransfers));
        putenv(sprintf('%s=%s', static::VARIABLE_NAME, 'G-R9NCW72D85'));

        // Act
        $failedMappingCount = $console->importMappingsForTest(
            ['catalog:unknown:group:setting=' . static::VARIABLE_NAME],
            ConfigurationConstants::SCOPE_GLOBAL,
            null,
            $this->createSettingsMap(),
            [],
        );
        putenv(static::VARIABLE_NAME);

        // Assert
        $this->assertSame(1, $failedMappingCount);
        $this->assertSame([], $savedTransfers);
        $this->assertStringContainsString('the schema defines no such setting', $console->getOutputForTest());
    }

    public function testSkipsAVariableThatIsNotSetWithoutFailing(): void
    {
        // Arrange
        $savedTransfers = [];
        $console = $this->createConsole($this->createCapturingFacadeMock($savedTransfers));

        // Act
        $failedMappingCount = $console->importMappingsForTest(
            [static::SETTING_KEY . '=CONFIGURATION_TEST_ABSENT'],
            ConfigurationConstants::SCOPE_GLOBAL,
            null,
            $this->createSettingsMap(),
            [],
        );

        // Assert
        $this->assertSame(0, $failedMappingCount);
        $this->assertSame([], $savedTransfers);
        $this->assertStringContainsString('is not set in this environment', $console->getOutputForTest());
    }

    /**
     * @dataProvider getScopeOptions
     */
    public function testFindScopeErrorValidatesTheScopeOptions(string $scope, ?string $scopeIdentifier, ?string $expectedErrorPart): void
    {
        // Arrange
        $console = $this->createConsole();

        // Act
        $scopeError = $console->findScopeErrorForTest($scope, $scopeIdentifier);

        // Assert
        if ($expectedErrorPart === null) {
            $this->assertNull($scopeError);

            return;
        }

        $this->assertStringContainsString($expectedErrorPart, (string)$scopeError);
    }

    /**
     * @return array<string, array<int, string|null>>
     */
    public function getScopeOptions(): array
    {
        return [
            'global scope without identifier' => [ConfigurationConstants::SCOPE_GLOBAL, null, null],
            'store scope with identifier' => [static::SCOPE_STORE, 'DE', null],
            'unknown scope' => ['website', 'DE', 'is not defined'],
            'global scope with identifier' => [ConfigurationConstants::SCOPE_GLOBAL, 'DE', 'takes no scope identifier'],
            'store scope without identifier' => [static::SCOPE_STORE, null, 'needs a scope identifier'],
        ];
    }

    public function testAFailedSaveWithoutAStatedReasonIsReported(): void
    {
        // Arrange
        $configurationFacadeMock = $this->createMock(ConfigurationFacade::class);
        $configurationFacadeMock->method('saveConfigurationValues')
            ->willReturn((new ConfigurationValueCollectionResponseTransfer())->setIsSuccess(false));
        $console = $this->createConsole($configurationFacadeMock);
        $configurationValueTransfer = $this->createScopedValueTransfer(ConfigurationConstants::SCOPE_GLOBAL, null)
            ->setSettingKey(static::SETTING_KEY)
            ->setValue('G-R9NCW72D85');

        // Act
        $isSaved = $console->saveConfigurationValueForTest($configurationValueTransfer, false);

        // Assert
        $this->assertFalse($isSaved);
        $this->assertStringContainsString('without a stated reason', $console->getOutputForTest());
    }

    /**
     * @dataProvider getFailingFacades
     */
    public function testAFailedSecretImportNeverPrintsTheValue(callable $facadeFactory): void
    {
        // Arrange
        $console = $this->createConsole($facadeFactory($this));

        // Act
        $isSaved = $console->saveConfigurationValueForTest($this->createSecretValueTransfer(), true);

        // Assert
        $output = $console->getOutputForTest();
        $this->assertFalse($isSaved);
        $this->assertStringNotContainsString(static::SECRET_VALUE, $output);
        $this->assertStringContainsString(static::SECRET_SETTING_KEY, $output);
        $this->assertStringContainsString('Details are not printed', $output);
    }

    /**
     * @dataProvider getFailingFacades
     */
    public function testAFailedNonSecretImportNeverPrintsTheValueEither(callable $facadeFactory): void
    {
        // Arrange
        $console = $this->createConsole($facadeFactory($this));

        // Act
        $isSaved = $console->saveConfigurationValueForTest($this->createSecretValueTransfer(), false);

        // Assert
        $output = $console->getOutputForTest();
        $this->assertFalse($isSaved);
        $this->assertStringNotContainsString(static::SECRET_VALUE, $output);
        $this->assertStringContainsString(static::SECRET_SETTING_KEY, $output);
        $this->assertStringContainsString('Details are not printed', $output);
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

    protected function createScopedValueTransfer(string $scope, ?string $scopeIdentifier): ConfigurationValueTransfer
    {
        return (new ConfigurationValueTransfer())
            ->setScope($scope)
            ->setScopeIdentifier($scopeIdentifier);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function createSettingsMap(): array
    {
        return [
            static::SETTING_KEY => [],
            static::SECRET_SETTING_KEY => [],
        ];
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
        ]);

        $configurationCommunicationFactoryMock = $this->createMock(ConfigurationCommunicationFactory::class);
        $configurationCommunicationFactoryMock->method('createEnvironmentConfigurationValueReader')
            ->willReturn(new EnvironmentConfigurationValueReader($configurationConfigMock));

        return new ImportEnvironmentConfigurationConsoleStub($configurationCommunicationFactoryMock, $configurationFacade);
    }
}
