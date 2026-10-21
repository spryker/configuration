<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Reader;

use Codeception\Test\Unit;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Communication\Reader\EnvironmentConfigurationValueReader;
use Spryker\Zed\Configuration\ConfigurationConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Communication
 * @group Reader
 * @group EnvironmentConfigurationValueReaderTest
 * Add your own group annotations below this line
 */
class EnvironmentConfigurationValueReaderTest extends Unit
{
    protected const string SCOPE_STORE = 'store';

    protected const string SETTING_KEY = 'integrations:google_analytics:tracking:measurement_id';

    protected const string VARIABLE_NAME = 'GA_MEASUREMENT_ID';

    /**
     * @dataProvider getMappings
     *
     * @param array<string, string>|null $expectedMapping
     */
    public function testFindMappingSplitsSettingKeyAndVariableName(string $mapping, ?array $expectedMapping): void
    {
        // Arrange
        $environmentConfigurationValueReader = $this->createReader();

        // Act
        $mappingParts = $environmentConfigurationValueReader->findMapping($mapping);

        // Assert
        $this->assertSame($expectedMapping, $mappingParts);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function getMappings(): array
    {
        $expectedMapping = [
            EnvironmentConfigurationValueReader::MAPPING_KEY_SETTING_KEY => static::SETTING_KEY,
            EnvironmentConfigurationValueReader::MAPPING_KEY_VARIABLE_NAME => static::VARIABLE_NAME,
        ];

        return [
            'setting key and variable name' => [
                static::SETTING_KEY . '=' . static::VARIABLE_NAME,
                $expectedMapping,
            ],
            'whitespace around the parts is ignored' => [
                ' ' . static::SETTING_KEY . ' = ' . static::VARIABLE_NAME . ' ',
                $expectedMapping,
            ],
            'a mapping without a separator does not resolve' => [
                static::SETTING_KEY,
                null,
            ],
            'a mapping without a variable name does not resolve' => [
                static::SETTING_KEY . '=',
                null,
            ],
            'a mapping without a setting key does not resolve' => [
                '=' . static::VARIABLE_NAME,
                null,
            ],
            'a value instead of a variable name does not resolve' => [
                static::SETTING_KEY . '=G-R9NCW72D85',
                null,
            ],
            'a JSON value instead of a variable name does not resolve' => [
                static::SETTING_KEY . '={"type":"service_account"}',
                null,
            ],
            'a variable name starting with a digit does not resolve' => [
                static::SETTING_KEY . '=1GA_MEASUREMENT_ID',
                null,
            ],
        ];
    }

    /**
     * @dataProvider getScopes
     */
    public function testIsAvailableScopeAcceptsOnlyConfiguredScopes(string $scope, bool $isExpectedAvailable): void
    {
        // Arrange
        $environmentConfigurationValueReader = $this->createReader();

        // Act
        $isAvailable = $environmentConfigurationValueReader->isAvailableScope($scope);

        // Assert
        $this->assertSame($isExpectedAvailable, $isAvailable);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function getScopes(): array
    {
        return [
            'global scope' => [ConfigurationConstants::SCOPE_GLOBAL, true],
            'configured store scope' => [static::SCOPE_STORE, true],
            'unknown scope' => ['website', false],
            'scope names are case-sensitive' => ['STORE', false],
        ];
    }

    public function testFindEnvironmentValueReturnsTheValueSetInThisProcess(): void
    {
        // Arrange
        $environmentConfigurationValueReader = $this->createReader();
        putenv(sprintf('%s=%s', static::VARIABLE_NAME, 'G-R9NCW72D85'));

        // Act
        $value = $environmentConfigurationValueReader->findEnvironmentValue(static::VARIABLE_NAME);
        putenv(static::VARIABLE_NAME);

        // Assert
        $this->assertSame('G-R9NCW72D85', $value);
    }

    public function testFindEnvironmentValueTreatsAnEmptyValueAsNotSet(): void
    {
        // Arrange
        $environmentConfigurationValueReader = $this->createReader();
        $variableName = 'CONFIGURATION_TEST_EMPTY_SECRET_KEY';
        putenv(sprintf('%s=', $variableName));

        // Act
        $value = $environmentConfigurationValueReader->findEnvironmentValue($variableName);
        putenv($variableName);

        // Assert
        $this->assertNull($value);
    }

    public function testFindEnvironmentValueReturnsNullForAVariableThatIsNotSet(): void
    {
        // Arrange
        $environmentConfigurationValueReader = $this->createReader();

        // Act
        $value = $environmentConfigurationValueReader->findEnvironmentValue('CONFIGURATION_TEST_ABSENT');

        // Assert
        $this->assertNull($value);
    }

    protected function createReader(): EnvironmentConfigurationValueReader
    {
        $configurationConfigMock = $this->createMock(ConfigurationConfig::class);
        $configurationConfigMock->method('getAvailableScopes')->willReturn([
            ConfigurationConstants::SCOPE_GLOBAL,
            static::SCOPE_STORE,
        ]);

        return new EnvironmentConfigurationValueReader($configurationConfigMock);
    }
}
