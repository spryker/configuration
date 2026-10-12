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
    protected const string ENV_VAR_PREFIX = 'configuration:';

    protected const string SCOPE_STORE = 'store';

    protected const string SCOPE_LOCALE = 'locale';

    protected const string SETTING_KEY = 'integrations:google_analytics:tracking:measurement_id';

    /**
     * @dataProvider getVariableNames
     *
     * @param array<string, string|null>|null $expected
     */
    public function testFindConfigurationValueResolvesScopeIdentifierAndSettingKey(string $variableName, ?array $expected): void
    {
        // Arrange
        $environmentConfigurationValueReader = $this->createReader();

        // Act
        $configurationValueTransfer = $environmentConfigurationValueReader->variableNameToConfigurationValueTransfer($variableName);

        // Assert
        if ($expected === null) {
            $this->assertNull($configurationValueTransfer, sprintf('Expected "%s" not to resolve.', $variableName));

            return;
        }

        $this->assertNotNull($configurationValueTransfer, sprintf('Expected "%s" to resolve.', $variableName));
        $this->assertSame($expected['scope'], $configurationValueTransfer->getScope());
        $this->assertSame($expected['scopeIdentifier'], $configurationValueTransfer->getScopeIdentifier());
        $this->assertSame($expected['settingKey'], $configurationValueTransfer->getSettingKey());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function getVariableNames(): array
    {
        return [
            'global scope is named like any other' => [
                static::ENV_VAR_PREFIX . ConfigurationConstants::SCOPE_GLOBAL . ':' . static::SETTING_KEY,
                ['scope' => ConfigurationConstants::SCOPE_GLOBAL, 'scopeIdentifier' => null, 'settingKey' => static::SETTING_KEY],
            ],
            'store scope keeps its identifier' => [
                static::ENV_VAR_PREFIX . static::SCOPE_STORE . ':DE:' . static::SETTING_KEY,
                ['scope' => static::SCOPE_STORE, 'scopeIdentifier' => 'DE', 'settingKey' => static::SETTING_KEY],
            ],
            'locale scope keeps its identifier' => [
                static::ENV_VAR_PREFIX . static::SCOPE_LOCALE . ':de_DE:theme:storefront:colors:main',
                ['scope' => static::SCOPE_LOCALE, 'scopeIdentifier' => 'de_DE', 'settingKey' => 'theme:storefront:colors:main'],
            ],
            'a name with no scope does not resolve' => [
                static::ENV_VAR_PREFIX . static::SETTING_KEY,
                null,
            ],
            'scoped name without a setting key does not resolve' => [
                static::ENV_VAR_PREFIX . static::SCOPE_STORE . ':DE',
                null,
            ],
            'prefix alone does not resolve' => [
                static::ENV_VAR_PREFIX,
                null,
            ],
            'a name outside the namespace does not resolve' => [
                'SPRYKER_UNRELATED_VARIABLE',
                null,
            ],
        ];
    }

    public function testFindEnvironmentValueReturnsTheValueSetInThisProcess(): void
    {
        // Arrange
        $environmentConfigurationValueReader = $this->createReader();
        $variableName = static::ENV_VAR_PREFIX . ConfigurationConstants::SCOPE_GLOBAL . ':' . static::SETTING_KEY;
        putenv(sprintf('%s=%s', $variableName, 'G-R9NCW72D85'));

        // Act
        $value = $environmentConfigurationValueReader->findEnvironmentValue($variableName);
        putenv($variableName);

        // Assert
        $this->assertSame('G-R9NCW72D85', $value);
    }

    public function testFindEnvironmentValueTreatsAnEmptyValueAsNotSet(): void
    {
        // Arrange
        $environmentConfigurationValueReader = $this->createReader();
        $variableName = static::ENV_VAR_PREFIX . 'integrations:empty:secret_key';
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
        $value = $environmentConfigurationValueReader->findEnvironmentValue(static::ENV_VAR_PREFIX . 'catalog:absent');

        // Assert
        $this->assertNull($value);
    }

    public function testGetEnvironmentVariablePrefixIsTheConfigurationNamespace(): void
    {
        // Arrange
        $environmentConfigurationValueReader = $this->createReader();

        // Act
        $prefix = $environmentConfigurationValueReader->getEnvironmentVariablePrefix();

        // Assert
        $this->assertSame(static::ENV_VAR_PREFIX, $prefix);
    }

    protected function createReader(): EnvironmentConfigurationValueReader
    {
        $configurationConfigMock = $this->createMock(ConfigurationConfig::class);
        $configurationConfigMock->method('getAvailableScopes')->willReturn([
            ConfigurationConstants::SCOPE_GLOBAL,
            static::SCOPE_STORE,
            static::SCOPE_LOCALE,
        ]);

        return new EnvironmentConfigurationValueReader($configurationConfigMock);
    }
}
