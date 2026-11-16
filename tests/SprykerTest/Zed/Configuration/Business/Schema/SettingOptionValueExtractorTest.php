<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business\Schema;

use Codeception\Test\Unit;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Shared\Configuration\Schema\SettingOptionValueExtractor;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Schema
 * @group SettingOptionValueExtractorTest
 * Add your own group annotations below this line
 */
class SettingOptionValueExtractorTest extends Unit
{
    /**
     * @dataProvider provideSingleChoiceSettingTypes
     */
    public function testGivenASingleChoiceSettingWithOptionsWhenAllowedValuesAreExtractedThenTheValueOfEachOptionIsReturned(string $settingType): void
    {
        // Arrange
        $setting = [
            ConfigurationSchemaConstants::SCHEMA_KEY_TYPE => $settingType,
            ConfigurationSchemaConstants::SCHEMA_KEY_OPTIONS => [
                ['value' => 'model-small', 'label' => 'Small model'],
                ['value' => 'model-large', 'label' => 'Large model'],
            ],
        ];

        // Act
        $allowedValues = (new SettingOptionValueExtractor())->extractAllowedValues($setting);

        // Assert
        $this->assertSame(['model-small', 'model-large'], $allowedValues);
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideSingleChoiceSettingTypes(): array
    {
        return [
            'select' => [ConfigurationSchemaConstants::VALUE_TYPE_SELECT],
            'radio' => [ConfigurationSchemaConstants::VALUE_TYPE_RADIO],
        ];
    }

    public function testGivenOptionsThatAreAPlainListWhenAllowedValuesAreExtractedThenEachListItemIsReturned(): void
    {
        // Arrange
        $setting = [
            ConfigurationSchemaConstants::SCHEMA_KEY_TYPE => ConfigurationSchemaConstants::VALUE_TYPE_SELECT,
            ConfigurationSchemaConstants::SCHEMA_KEY_OPTIONS => ['name_asc', 'name_desc'],
        ];

        // Act
        $allowedValues = (new SettingOptionValueExtractor())->extractAllowedValues($setting);

        // Assert
        $this->assertSame(['name_asc', 'name_desc'], $allowedValues);
    }

    public function testGivenNonStringOptionValuesWhenAllowedValuesAreExtractedThenTheStringThatTheBackOfficePostsIsReturned(): void
    {
        // Arrange
        $setting = [
            ConfigurationSchemaConstants::SCHEMA_KEY_TYPE => ConfigurationSchemaConstants::VALUE_TYPE_RADIO,
            ConfigurationSchemaConstants::SCHEMA_KEY_OPTIONS => [
                ['value' => true, 'label' => 'On'],
                ['value' => false, 'label' => 'Off'],
                ['value' => 10, 'label' => 'Ten'],
                ['value' => 1.5, 'label' => 'One and a half'],
            ],
        ];

        // Act
        $allowedValues = (new SettingOptionValueExtractor())->extractAllowedValues($setting);

        // Assert
        $this->assertSame(['1', '', '10', '1.5'], $allowedValues);
    }

    /**
     * @dataProvider provideSettingsWithoutAllowedValues
     *
     * @param array<string, mixed> $setting
     */
    public function testGivenASettingWithoutAllowedValuesWhenAllowedValuesAreExtractedThenNoValuesAreReturned(array $setting): void
    {
        // Act
        $allowedValues = (new SettingOptionValueExtractor())->extractAllowedValues($setting);

        // Assert
        $this->assertSame([], $allowedValues);
    }

    /**
     * @return array<string, array<array<string, mixed>>>
     */
    public function provideSettingsWithoutAllowedValues(): array
    {
        return [
            'multiselect setting' => [[
                ConfigurationSchemaConstants::SCHEMA_KEY_TYPE => ConfigurationSchemaConstants::VALUE_TYPE_MULTISELECT,
                ConfigurationSchemaConstants::SCHEMA_KEY_OPTIONS => [['value' => 'a', 'label' => 'A']],
            ]],
            'string setting' => [[
                ConfigurationSchemaConstants::SCHEMA_KEY_TYPE => ConfigurationSchemaConstants::VALUE_TYPE_STRING,
            ]],
            'select setting without options' => [[
                ConfigurationSchemaConstants::SCHEMA_KEY_TYPE => ConfigurationSchemaConstants::VALUE_TYPE_SELECT,
                ConfigurationSchemaConstants::SCHEMA_KEY_OPTIONS => [],
            ]],
            'select setting with a data object plugin' => [[
                ConfigurationSchemaConstants::SCHEMA_KEY_TYPE => ConfigurationSchemaConstants::VALUE_TYPE_SELECT,
                ConfigurationSchemaConstants::SCHEMA_KEY_OPTIONS => [['value' => 'fallback', 'label' => 'Fallback']],
                ConfigurationSchemaConstants::SCHEMA_KEY_DATA_OBJECT => '\Pyz\Zed\Configuration\Plugin\MerchantListDataProviderPlugin',
            ]],
            'option without value' => [[
                ConfigurationSchemaConstants::SCHEMA_KEY_TYPE => ConfigurationSchemaConstants::VALUE_TYPE_SELECT,
                ConfigurationSchemaConstants::SCHEMA_KEY_OPTIONS => [['label' => 'No value']],
            ]],
        ];
    }
}
