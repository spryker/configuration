<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Validator;

use Spryker\Shared\Configuration\ConfigurationSchemaConstants;

class ConfigurationSchemaKeyValidator implements ConfigurationSchemaKeyValidatorInterface
{
    /**
     * @param array<mixed> $schema
     *
     * @return array<string>
     */
    public function validate(array $schema): array
    {
        $seenKeys = [];
        $errors = [];

        foreach ($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] ?? [] as $feature) {
            if ($this->isDisabled($feature)) {
                continue;
            }

            $this->collectFeatureKeyErrors($feature, $seenKeys, $errors);
        }

        return $errors;
    }

    /**
     * @param array<mixed> $feature
     * @param array<string, bool> $seenKeys
     * @param array<string> $errors
     */
    protected function collectFeatureKeyErrors(array $feature, array &$seenKeys, array &$errors): void
    {
        $featureKey = $feature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY];

        foreach ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] ?? [] as $tab) {
            if ($this->isDisabled($tab)) {
                continue;
            }

            $this->collectTabKeyErrors($featureKey, $tab, $seenKeys, $errors);
        }
    }

    /**
     * @param array<mixed> $tab
     * @param array<string, bool> $seenKeys
     * @param array<string> $errors
     */
    protected function collectTabKeyErrors(string $featureKey, array $tab, array &$seenKeys, array &$errors): void
    {
        $tabKey = $tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY];

        foreach ($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] ?? [] as $group) {
            if ($this->isDisabled($group)) {
                continue;
            }

            $this->collectGroupKeyErrors($featureKey, $tabKey, $group, $seenKeys, $errors);
        }
    }

    /**
     * @param array<mixed> $group
     * @param array<string, bool> $seenKeys
     * @param array<string> $errors
     */
    protected function collectGroupKeyErrors(string $featureKey, string $tabKey, array $group, array &$seenKeys, array &$errors): void
    {
        $groupKey = $group[ConfigurationSchemaConstants::SCHEMA_KEY_KEY];

        foreach ($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] ?? [] as $setting) {
            if ($this->isDisabled($setting)) {
                continue;
            }

            $compoundKey = sprintf('%s:%s:%s:%s', $featureKey, $tabKey, $groupKey, $setting[ConfigurationSchemaConstants::SCHEMA_KEY_KEY]);
            $effectiveKey = (string)($setting[ConfigurationSchemaConstants::SCHEMA_KEY_STATIC_KEY] ?? $compoundKey);

            if (isset($seenKeys[$effectiveKey])) {
                $errors[] = sprintf('Duplicate configuration key "%s"', $effectiveKey);

                continue;
            }

            $seenKeys[$effectiveKey] = true;
        }
    }

    /**
     * @param array<mixed> $item
     */
    protected function isDisabled(array $item): bool
    {
        return ($item[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true) === false;
    }
}
