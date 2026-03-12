<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Schema;

use Spryker\Shared\Configuration\ConfigurationConstants;
use Symfony\Component\Yaml\Yaml;

class SchemaParser implements SchemaParserInterface
{
    /**
     * @var array<string>
     */
    protected array $validationErrors = [];

    /**
     * @param string $yamlContent
     *
     * @return array<mixed>
     */
    public function parse(string $yamlContent): array
    {
        return Yaml::parse($yamlContent) ?? [];
    }

    /**
     * @param array<mixed> $parsedYaml
     *
     * @return array<mixed>
     */
    public function normalize(array $parsedYaml): array
    {
        if (!isset($parsedYaml[ConfigurationConstants::SCHEMA_KEY_FEATURES]) || !is_array($parsedYaml[ConfigurationConstants::SCHEMA_KEY_FEATURES])) {
            return $parsedYaml;
        }

        foreach ($parsedYaml[ConfigurationConstants::SCHEMA_KEY_FEATURES] as &$feature) {
            $feature = $this->normalizeFeature($feature);
        }

        return $parsedYaml;
    }

    /**
     * @param array<mixed> $schema
     *
     * @return bool
     */
    public function validate(array $schema): bool
    {
        $this->validationErrors = [];

        if (!isset($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES]) || !is_array($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES])) {
            $this->validationErrors[] = 'Root schema must contain "features" array';

            return false;
        }

        foreach ($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES] as $featureIndex => $feature) {
            if (!$this->validateFeature($feature, $featureIndex)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * @param array<mixed> $feature
     *
     * @return array<mixed>
     */
    protected function normalizeFeature(array $feature): array
    {
        $feature[ConfigurationConstants::SCHEMA_KEY_ENABLED] = $feature[ConfigurationConstants::SCHEMA_KEY_ENABLED] ?? true;
        $feature[ConfigurationConstants::SCHEMA_KEY_TABS] = $feature[ConfigurationConstants::SCHEMA_KEY_TABS] ?? [];

        foreach ($feature[ConfigurationConstants::SCHEMA_KEY_TABS] as &$tab) {
            $tab = $this->normalizeTab($tab);
        }

        return $feature;
    }

    /**
     * @param array<mixed> $tab
     *
     * @return array<mixed>
     */
    protected function normalizeTab(array $tab): array
    {
        $tab[ConfigurationConstants::SCHEMA_KEY_ENABLED] = $tab[ConfigurationConstants::SCHEMA_KEY_ENABLED] ?? true;
        $tab[ConfigurationConstants::SCHEMA_KEY_GROUPS] = $tab[ConfigurationConstants::SCHEMA_KEY_GROUPS] ?? [];

        foreach ($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS] as &$group) {
            $group = $this->normalizeGroup($group);
        }

        return $tab;
    }

    /**
     * @param array<mixed> $group
     *
     * @return array<mixed>
     */
    protected function normalizeGroup(array $group): array
    {
        $group[ConfigurationConstants::SCHEMA_KEY_ENABLED] = $group[ConfigurationConstants::SCHEMA_KEY_ENABLED] ?? true;
        $group[ConfigurationConstants::SCHEMA_KEY_SCOPES] = $group[ConfigurationConstants::SCHEMA_KEY_SCOPES] ?? [ConfigurationConstants::SCOPE_GLOBAL];
        $group[ConfigurationConstants::SCHEMA_KEY_SETTINGS] = $group[ConfigurationConstants::SCHEMA_KEY_SETTINGS] ?? [];

        foreach ($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS] as &$setting) {
            $setting = $this->normalizeSetting($setting);
        }

        return $group;
    }

    /**
     * @param array<mixed> $setting
     *
     * @return array<mixed>
     */
    protected function normalizeSetting(array $setting): array
    {
        $setting[ConfigurationConstants::SCHEMA_KEY_ENABLED] = $setting[ConfigurationConstants::SCHEMA_KEY_ENABLED] ?? true;
        $setting[ConfigurationConstants::SCHEMA_KEY_SECRET] = $setting[ConfigurationConstants::SCHEMA_KEY_SECRET] ?? false;
        $setting[ConfigurationConstants::SCHEMA_KEY_STOREFRONT] = $setting[ConfigurationConstants::SCHEMA_KEY_STOREFRONT] ?? false;
        $setting[ConfigurationConstants::SCHEMA_KEY_SCOPES] = $setting[ConfigurationConstants::SCHEMA_KEY_SCOPES] ?? [ConfigurationConstants::SCOPE_GLOBAL];
        $setting[ConfigurationConstants::SCHEMA_KEY_OPTIONS] = $setting[ConfigurationConstants::SCHEMA_KEY_OPTIONS] ?? [];
        $setting[ConfigurationConstants::SCHEMA_KEY_CONSTRAINTS] = $setting[ConfigurationConstants::SCHEMA_KEY_CONSTRAINTS] ?? [];
        $setting[ConfigurationConstants::SCHEMA_KEY_DEPENDENCIES] = $setting[ConfigurationConstants::SCHEMA_KEY_DEPENDENCIES] ?? [];

        return $setting;
    }

    /**
     * @param array<mixed> $feature
     * @param int $featureIndex
     *
     * @return bool
     */
    protected function validateFeature(array $feature, int $featureIndex): bool
    {
        $featureKey = $feature[ConfigurationConstants::SCHEMA_KEY_KEY] ?? sprintf('Feature #%d', $featureIndex);

        if (!isset($feature[ConfigurationConstants::SCHEMA_KEY_KEY])) {
            $this->validationErrors[] = sprintf('Feature #%d is missing required "key" field', $featureIndex);

            return false;
        }

        if (!isset($feature[ConfigurationConstants::SCHEMA_KEY_NAME])) {
            $this->validationErrors[] = sprintf('Feature "%s" is missing required "name" field', $featureKey);

            return false;
        }

        if (!isset($feature[ConfigurationConstants::SCHEMA_KEY_TABS]) || !is_array($feature[ConfigurationConstants::SCHEMA_KEY_TABS])) {
            $this->validationErrors[] = sprintf('Feature "%s" must contain "tabs" array', $featureKey);

            return false;
        }

        foreach ($feature[ConfigurationConstants::SCHEMA_KEY_TABS] as $tabIndex => $tab) {
            if (!$this->validateTab($tab, $tabIndex, $featureKey)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<mixed> $tab
     * @param int $tabIndex
     * @param string $featureKey
     *
     * @return bool
     */
    protected function validateTab(array $tab, int $tabIndex, string $featureKey): bool
    {
        $tabKey = $tab[ConfigurationConstants::SCHEMA_KEY_KEY] ?? sprintf('Tab #%d', $tabIndex);

        if (!isset($tab[ConfigurationConstants::SCHEMA_KEY_KEY])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab #%d is missing required "key" field', $featureKey, $tabIndex);

            return false;
        }

        if (!isset($tab[ConfigurationConstants::SCHEMA_KEY_NAME])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab "%s" is missing required "name" field', $featureKey, $tabKey);

            return false;
        }

        if (!isset($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS]) || !is_array($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab "%s" must contain "groups" array', $featureKey, $tabKey);

            return false;
        }

        foreach ($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS] as $groupIndex => $group) {
            if (!$this->validateGroup($group, $groupIndex, $featureKey, $tabKey)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<mixed> $group
     * @param int $groupIndex
     * @param string $featureKey
     * @param string $tabKey
     *
     * @return bool
     */
    protected function validateGroup(array $group, int $groupIndex, string $featureKey, string $tabKey): bool
    {
        $groupKey = $group[ConfigurationConstants::SCHEMA_KEY_KEY] ?? sprintf('Group #%d', $groupIndex);

        if (!isset($group[ConfigurationConstants::SCHEMA_KEY_KEY])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab "%s" -> Group #%d is missing required "key" field', $featureKey, $tabKey, $groupIndex);

            return false;
        }

        if (!isset($group[ConfigurationConstants::SCHEMA_KEY_NAME])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab "%s" -> Group "%s" is missing required "name" field', $featureKey, $tabKey, $groupKey);

            return false;
        }

        if (!isset($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS]) || !is_array($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab "%s" -> Group "%s" must contain "settings" array', $featureKey, $tabKey, $groupKey);

            return false;
        }

        foreach ($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS] as $settingIndex => $setting) {
            if (!$this->validateSetting($setting, $settingIndex, $featureKey, $tabKey, $groupKey)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<mixed> $setting
     * @param int $settingIndex
     * @param string $featureKey
     * @param string $tabKey
     * @param string $groupKey
     *
     * @return bool
     */
    protected function validateSetting(array $setting, int $settingIndex, string $featureKey, string $tabKey, string $groupKey): bool
    {
        $settingKey = $setting[ConfigurationConstants::SCHEMA_KEY_KEY] ?? sprintf('Setting #%d', $settingIndex);
        $path = sprintf('Feature "%s" -> Tab "%s" -> Group "%s" -> Setting', $featureKey, $tabKey, $groupKey);

        if (!isset($setting[ConfigurationConstants::SCHEMA_KEY_KEY])) {
            $this->validationErrors[] = sprintf('%s #%d is missing required "key" field', $path, $settingIndex);

            return false;
        }

        if (!isset($setting[ConfigurationConstants::SCHEMA_KEY_NAME])) {
            $this->validationErrors[] = sprintf('%s "%s" is missing required "name" field', $path, $settingKey);

            return false;
        }

        if (!isset($setting[ConfigurationConstants::SCHEMA_KEY_TYPE])) {
            $this->validationErrors[] = sprintf('%s "%s" is missing required "type" field', $path, $settingKey);

            return false;
        }

        $allowedTypes = [
            ConfigurationConstants::VALUE_TYPE_STRING,
            ConfigurationConstants::VALUE_TYPE_INTEGER,
            ConfigurationConstants::VALUE_TYPE_FLOAT,
            ConfigurationConstants::VALUE_TYPE_BOOLEAN,
            ConfigurationConstants::VALUE_TYPE_COLOR,
            ConfigurationConstants::VALUE_TYPE_JSON,
            ConfigurationConstants::VALUE_TYPE_TEXT,
            ConfigurationConstants::VALUE_TYPE_SELECT,
            ConfigurationConstants::VALUE_TYPE_MULTISELECT,
            ConfigurationConstants::VALUE_TYPE_RADIO,
        ];

        if (!in_array($setting[ConfigurationConstants::SCHEMA_KEY_TYPE], $allowedTypes, true)) {
            $this->validationErrors[] = sprintf(
                '%s "%s" has invalid type "%s". Allowed types: %s',
                $path,
                $settingKey,
                $setting[ConfigurationConstants::SCHEMA_KEY_TYPE],
                implode(', ', $allowedTypes),
            );

            return false;
        }

        return true;
    }
}
