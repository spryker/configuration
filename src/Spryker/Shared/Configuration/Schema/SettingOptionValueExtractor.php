<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Schema;

use Spryker\Shared\Configuration\ConfigurationSchemaConstants;

class SettingOptionValueExtractor implements SettingOptionValueExtractorInterface
{
    protected const array SINGLE_CHOICE_SETTING_TYPES = [
        ConfigurationSchemaConstants::VALUE_TYPE_SELECT,
        ConfigurationSchemaConstants::VALUE_TYPE_RADIO,
    ];

    protected const string OPTION_KEY_VALUE = 'value';

    /**
     * @param array<mixed> $setting
     *
     * @return list<string>
     */
    public function extractAllowedValues(array $setting): array
    {
        if (!in_array($setting[ConfigurationSchemaConstants::SCHEMA_KEY_TYPE] ?? null, static::SINGLE_CHOICE_SETTING_TYPES, true)) {
            return [];
        }

        // A data object plugin can replace the options at render time, so the schema options are not the full list.
        if (!empty($setting[ConfigurationSchemaConstants::SCHEMA_KEY_DATA_OBJECT])) {
            return [];
        }

        $allowedValues = [];

        foreach ($setting[ConfigurationSchemaConstants::SCHEMA_KEY_OPTIONS] ?? [] as $option) {
            $optionValue = is_array($option) ? ($option[static::OPTION_KEY_VALUE] ?? null) : $option;

            if ($optionValue === null) {
                continue;
            }

            $allowedValues[] = $this->normalizeOptionValue($optionValue);
        }

        return $allowedValues;
    }

    public function normalizeOptionValue(mixed $value): string
    {
        if (is_scalar($value)) {
            return (string)$value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
