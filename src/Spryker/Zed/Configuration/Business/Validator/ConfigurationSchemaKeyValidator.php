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

        return array_merge($errors, $this->collectLinkTargetErrors($schema));
    }

    /**
     * @param array<mixed> $schema
     *
     * @return array<string>
     */
    protected function collectLinkTargetErrors(array $schema): array
    {
        $enabledTabs = $this->getEnabledTabs($schema);
        $errors = [];

        foreach ($this->getSettingLinkTargets($enabledTabs) as $settingPath => $targetTabPath) {
            if (isset($enabledTabs[$targetTabPath])) {
                continue;
            }

            $errors[] = sprintf('Setting "%s" links to "%s", which is not an enabled tab', $settingPath, $targetTabPath);
        }

        return $errors;
    }

    /**
     * @param array<mixed> $schema
     *
     * @return array<string, array<mixed>> Keys are `feature:tab`.
     */
    protected function getEnabledTabs(array $schema): array
    {
        $enabledTabs = [];

        foreach ($this->filterEnabled($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] ?? []) as $feature) {
            foreach ($this->filterEnabled($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] ?? []) as $tab) {
                $enabledTabs[sprintf('%s:%s', $feature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY], $tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY])] = $tab;
            }
        }

        return $enabledTabs;
    }

    /**
     * @param array<string, array<mixed>> $enabledTabs
     *
     * @return array<string, string> Keys are the setting paths, values the `feature:tab` the setting links to.
     */
    protected function getSettingLinkTargets(array $enabledTabs): array
    {
        $linkTargets = [];

        foreach ($enabledTabs as $tabPath => $tab) {
            foreach ($this->filterEnabled($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] ?? []) as $group) {
                $linkTargets += $this->getGroupLinkTargets(sprintf('%s:%s', $tabPath, $group[ConfigurationSchemaConstants::SCHEMA_KEY_KEY]), $group);
            }
        }

        return $linkTargets;
    }

    /**
     * @param array<mixed> $group
     *
     * @return array<string, string>
     */
    protected function getGroupLinkTargets(string $groupPath, array $group): array
    {
        $linkTargets = [];

        foreach ($this->filterEnabled($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] ?? []) as $setting) {
            if (!isset($setting[ConfigurationSchemaConstants::SCHEMA_KEY_LINK])) {
                continue;
            }

            $link = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_LINK];
            $linkTargets[sprintf('%s:%s', $groupPath, $setting[ConfigurationSchemaConstants::SCHEMA_KEY_KEY])] = sprintf(
                '%s:%s',
                $link[ConfigurationSchemaConstants::SCHEMA_KEY_LINK_FEATURE],
                $link[ConfigurationSchemaConstants::SCHEMA_KEY_LINK_TAB],
            );
        }

        return $linkTargets;
    }

    /**
     * @param array<mixed> $items
     *
     * @return array<mixed>
     */
    protected function filterEnabled(array $items): array
    {
        return array_filter($items, fn (array $item): bool => !$this->isDisabled($item));
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
