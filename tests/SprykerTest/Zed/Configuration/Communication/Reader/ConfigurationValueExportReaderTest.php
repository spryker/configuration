<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Reader;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use Spryker\Zed\Configuration\Communication\Reader\ConfigurationValueExportReader;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Communication
 * @group Reader
 * @group ConfigurationValueExportReaderTest
 * Add your own group annotations below this line
 */
class ConfigurationValueExportReaderTest extends Unit
{
    protected const string SCOPE_STORE = 'store';

    protected const string SETTING_KEY_MEASUREMENT_ID = 'integrations:google_analytics:tracking:measurement_id';

    protected const string SETTING_KEY_SECRET = 'integrations:google_analytics:connection:service_account_credentials_json';

    public function testGetExportableConfigurationValuesSkipsSecretSettings(): void
    {
        // Arrange
        $configurationValueExportReader = $this->createReader([
            $this->createConfigurationValue(static::SETTING_KEY_MEASUREMENT_ID, 'G-R9NCW72D85'),
            $this->createConfigurationValue(static::SETTING_KEY_SECRET, 'a-service-account-key'),
        ]);

        // Act
        $configurationValueTransfers = $configurationValueExportReader->getExportableConfigurationValues([
            static::SETTING_KEY_MEASUREMENT_ID,
            static::SETTING_KEY_SECRET,
        ], [static::SETTING_KEY_SECRET]);

        // Assert
        $settingKeys = $this->getSettingKeys($configurationValueTransfers);
        $this->assertNotContains(static::SETTING_KEY_SECRET, $settingKeys);
        $this->assertContains(static::SETTING_KEY_MEASUREMENT_ID, $settingKeys);
    }

    public function testGetExportableConfigurationValuesTakesOnlyTheNamedSettings(): void
    {
        // Arrange
        $configurationValueExportReader = $this->createReader([
            $this->createConfigurationValue(static::SETTING_KEY_MEASUREMENT_ID, 'G-R9NCW72D85'),
            $this->createConfigurationValue('theme:storefront:colors:main', '#ff0000'),
        ]);

        // Act
        $configurationValueTransfers = $configurationValueExportReader->getExportableConfigurationValues([
            static::SETTING_KEY_MEASUREMENT_ID,
        ], [static::SETTING_KEY_SECRET]);

        // Assert
        $this->assertSame(
            [static::SETTING_KEY_MEASUREMENT_ID, static::SETTING_KEY_MEASUREMENT_ID],
            $this->getSettingKeys($configurationValueTransfers),
        );
    }

    public function testGetExportableConfigurationValuesRecordsTheScopeItWasAskedFor(): void
    {
        // Arrange
        $configurationValueExportReader = $this->createReader([
            $this->createConfigurationValue(static::SETTING_KEY_MEASUREMENT_ID, 'G-R9NCW72D85'),
        ]);

        // Act
        $configurationValueTransfers = $configurationValueExportReader->getExportableConfigurationValues([
            static::SETTING_KEY_MEASUREMENT_ID,
        ], [static::SETTING_KEY_SECRET]);

        // Assert
        $scopes = [];

        foreach ($configurationValueTransfers as $configurationValueTransfer) {
            $scopes[] = $configurationValueTransfer->getScope() . ':' . ($configurationValueTransfer->getScopeIdentifier() ?? 'null');
        }

        $this->assertSame([ConfigurationConstants::SCOPE_GLOBAL . ':null', static::SCOPE_STORE . ':DE'], $scopes);
    }

    public function testGetScopesWithoutIdentifierProviderNamesAScopeNoPluginHandles(): void
    {
        // Arrange
        $configurationValueExportReader = $this->createReader([], []);

        // Act
        $scopes = $configurationValueExportReader->getScopesWithoutIdentifier();

        // Assert
        $this->assertSame([static::SCOPE_STORE], $scopes);
    }

    public function testGetScopesWithoutIdentifierProviderIgnoresGlobalScope(): void
    {
        // Arrange
        $configurationValueExportReader = $this->createReader([], ['DE']);

        // Act
        $scopes = $configurationValueExportReader->getScopesWithoutIdentifier();

        // Assert
        $this->assertSame([], $scopes);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ConfigurationValueTransfer> $configurationValueTransfers
     *
     * @return array<string|null>
     */
    protected function getSettingKeys(array $configurationValueTransfers): array
    {
        $settingKeys = [];

        foreach ($configurationValueTransfers as $configurationValueTransfer) {
            $settingKeys[] = $configurationValueTransfer->getSettingKey();
        }

        return $settingKeys;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ConfigurationValueTransfer> $configurationValueTransfers
     * @param array<string> $scopeIdentifiers
     */
    protected function createReader(array $configurationValueTransfers, array $scopeIdentifiers = ['DE']): ConfigurationValueExportReader
    {
        $configurationConfigMock = $this->createMock(ConfigurationConfig::class);
        $configurationConfigMock->method('getAvailableScopes')->willReturn([
            ConfigurationConstants::SCOPE_GLOBAL,
            static::SCOPE_STORE,
        ]);

        $configurationFacadeMock = $this->createMock(ConfigurationFacadeInterface::class);
        $configurationFacadeMock->method('getScopeIdentifiers')->willReturn($scopeIdentifiers);

        // Mirrors the real repository: a fresh transfer per call, already carrying the row's scope.
        $configurationRepositoryMock = $this->createMock(ConfigurationRepositoryInterface::class);
        $configurationRepositoryMock->method('findAllConfigurationValuesByScope')->willReturnCallback(
            static function (string $scope, ?string $scopeIdentifier = null) use ($configurationValueTransfers): array {
                $scopedTransfers = [];

                foreach ($configurationValueTransfers as $configurationValueTransfer) {
                    $scopedTransfers[] = (new ConfigurationValueTransfer())
                        ->setSettingKey($configurationValueTransfer->getSettingKey())
                        ->setValue($configurationValueTransfer->getValue())
                        ->setScope($scope)
                        ->setScopeIdentifier($scopeIdentifier);
                }

                return $scopedTransfers;
            },
        );

        return new ConfigurationValueExportReader(
            $configurationFacadeMock,
            $configurationRepositoryMock,
            $configurationConfigMock,
        );
    }

    protected function createConfigurationValue(string $settingKey, string $value): ConfigurationValueTransfer
    {
        return (new ConfigurationValueTransfer())
            ->setSettingKey($settingKey)
            ->setValue($value);
    }
}
