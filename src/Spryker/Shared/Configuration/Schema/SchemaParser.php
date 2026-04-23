<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Schema;

use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Symfony\Component\Yaml\Yaml;

class SchemaParser implements SchemaParserInterface
{
    protected const array ALLOWED_STATUSES = [
        ConfigurationSchemaConstants::STATUS_BETA,
        ConfigurationSchemaConstants::STATUS_EARLY_ACCESS,
    ];

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
        if (!isset($parsedYaml[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES]) || !is_array($parsedYaml[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES])) {
            return $parsedYaml;
        }

        foreach ($parsedYaml[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] as &$feature) {
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

        if (!isset($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES]) || !is_array($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES])) {
            $this->validationErrors[] = 'Root schema must contain "features" array';

            return false;
        }

        foreach ($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] as $featureIndex => $feature) {
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
        $feature[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] = $feature[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true;
        $feature[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] = $feature[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] ?? null;

        if ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] !== null && !in_array($feature[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS], static::ALLOWED_STATUSES, true)) {
            $feature[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] = null;
        }

        $feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] = $feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] ?? [];

        foreach ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] as &$tab) {
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
        $tab[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] = $tab[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true;
        $tab[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] = $tab[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] ?? null;

        if ($tab[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] !== null && !in_array($tab[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS], static::ALLOWED_STATUSES, true)) {
            $tab[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] = null;
        }

        $tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] = $tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] ?? [];

        foreach ($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] as &$group) {
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
        $group[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] = $group[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true;
        $group[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] = $group[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] ?? null;

        if ($group[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] !== null && !in_array($group[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS], static::ALLOWED_STATUSES, true)) {
            $group[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] = null;
        }

        $group[ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES] = $group[ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES] ?? [ConfigurationConstants::SCOPE_GLOBAL];
        $group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] = $group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] ?? [];

        foreach ($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] as &$setting) {
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
        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true;
        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] ?? null;

        if ($setting[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] !== null && !in_array($setting[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS], static::ALLOWED_STATUSES, true)) {
            $setting[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] = null;
        }

        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_OVERRIDES] = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_OVERRIDES] ?? [];

        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_SECRET] = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_SECRET] ?? false;
        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_STOREFRONT] = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_STOREFRONT] ?? false;
        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES] = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES] ?? [ConfigurationConstants::SCOPE_GLOBAL];
        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_OPTIONS] = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_OPTIONS] ?? [];
        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_CONSTRAINTS] = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_CONSTRAINTS] ?? [];
        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_DEPENDENCIES] = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_DEPENDENCIES] ?? [];
        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_SANITIZE_XSS] = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_SANITIZE_XSS] ?? [];

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
        $featureKey = $feature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? sprintf('Feature #%d', $featureIndex);

        if (!isset($feature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY])) {
            $this->validationErrors[] = sprintf('Feature #%d is missing required "key" field', $featureIndex);

            return false;
        }

        if (!isset($feature[ConfigurationSchemaConstants::SCHEMA_KEY_NAME])) {
            $this->validationErrors[] = sprintf('Feature "%s" is missing required "name" field', $featureKey);

            return false;
        }

        if (!isset($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS]) || !is_array($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS])) {
            $this->validationErrors[] = sprintf('Feature "%s" must contain "tabs" array', $featureKey);

            return false;
        }

        if (isset($feature[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS]) && !in_array($feature[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS], static::ALLOWED_STATUSES, true)) {
            $this->validationErrors[] = sprintf(
                'Feature "%s" has invalid status "%s". Allowed: %s',
                $featureKey,
                $feature[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS],
                implode(', ', static::ALLOWED_STATUSES),
            );
        }

        foreach ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] as $tabIndex => $tab) {
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
        $tabKey = $tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? sprintf('Tab #%d', $tabIndex);

        if (!isset($tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab #%d is missing required "key" field', $featureKey, $tabIndex);

            return false;
        }

        if (!isset($tab[ConfigurationSchemaConstants::SCHEMA_KEY_NAME])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab "%s" is missing required "name" field', $featureKey, $tabKey);

            return false;
        }

        if (!isset($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS]) || !is_array($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab "%s" must contain "groups" array', $featureKey, $tabKey);

            return false;
        }

        if (isset($tab[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS]) && !in_array($tab[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS], static::ALLOWED_STATUSES, true)) {
            $this->validationErrors[] = sprintf(
                'Feature "%s" -> Tab "%s" has invalid status "%s". Allowed: %s',
                $featureKey,
                $tabKey,
                $tab[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS],
                implode(', ', static::ALLOWED_STATUSES),
            );
        }

        foreach ($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] as $groupIndex => $group) {
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
        $groupKey = $group[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? sprintf('Group #%d', $groupIndex);

        if (!isset($group[ConfigurationSchemaConstants::SCHEMA_KEY_KEY])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab "%s" -> Group #%d is missing required "key" field', $featureKey, $tabKey, $groupIndex);

            return false;
        }

        if (!isset($group[ConfigurationSchemaConstants::SCHEMA_KEY_NAME])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab "%s" -> Group "%s" is missing required "name" field', $featureKey, $tabKey, $groupKey);

            return false;
        }

        if (!isset($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS]) || !is_array($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS])) {
            $this->validationErrors[] = sprintf('Feature "%s" -> Tab "%s" -> Group "%s" must contain "settings" array', $featureKey, $tabKey, $groupKey);

            return false;
        }

        if (isset($group[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS]) && !in_array($group[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS], static::ALLOWED_STATUSES, true)) {
            $this->validationErrors[] = sprintf(
                'Feature "%s" -> Tab "%s" -> Group "%s" has invalid status "%s". Allowed: %s',
                $featureKey,
                $tabKey,
                $groupKey,
                $group[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS],
                implode(', ', static::ALLOWED_STATUSES),
            );
        }

        foreach ($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] as $settingIndex => $setting) {
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
        $settingKey = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? sprintf('Setting #%d', $settingIndex);
        $path = sprintf('Feature "%s" -> Tab "%s" -> Group "%s" -> Setting', $featureKey, $tabKey, $groupKey);

        if (!isset($setting[ConfigurationSchemaConstants::SCHEMA_KEY_KEY])) {
            $this->validationErrors[] = sprintf('%s #%d is missing required "key" field', $path, $settingIndex);

            return false;
        }

        if (!isset($setting[ConfigurationSchemaConstants::SCHEMA_KEY_NAME])) {
            $this->validationErrors[] = sprintf('%s "%s" is missing required "name" field', $path, $settingKey);

            return false;
        }

        if (!isset($setting[ConfigurationSchemaConstants::SCHEMA_KEY_TYPE])) {
            $this->validationErrors[] = sprintf('%s "%s" is missing required "type" field', $path, $settingKey);

            return false;
        }

        $allowedTypes = [
            ConfigurationSchemaConstants::VALUE_TYPE_STRING,
            ConfigurationSchemaConstants::VALUE_TYPE_INTEGER,
            ConfigurationSchemaConstants::VALUE_TYPE_FLOAT,
            ConfigurationSchemaConstants::VALUE_TYPE_BOOLEAN,
            ConfigurationSchemaConstants::VALUE_TYPE_COLOR,
            ConfigurationSchemaConstants::VALUE_TYPE_FILE,
            ConfigurationSchemaConstants::VALUE_TYPE_JSON,
            ConfigurationSchemaConstants::VALUE_TYPE_TEXT,
            ConfigurationSchemaConstants::VALUE_TYPE_SELECT,
            ConfigurationSchemaConstants::VALUE_TYPE_MULTISELECT,
            ConfigurationSchemaConstants::VALUE_TYPE_RADIO,
        ];

        if (!in_array($setting[ConfigurationSchemaConstants::SCHEMA_KEY_TYPE], $allowedTypes, true)) {
            $this->validationErrors[] = sprintf(
                '%s "%s" has invalid type "%s". Allowed types: %s',
                $path,
                $settingKey,
                $setting[ConfigurationSchemaConstants::SCHEMA_KEY_TYPE],
                implode(', ', $allowedTypes),
            );

            return false;
        }

        if (isset($setting[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS]) && !in_array($setting[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS], static::ALLOWED_STATUSES, true)) {
            $this->validationErrors[] = sprintf(
                '%s "%s" has invalid status "%s". Allowed: %s',
                $path,
                $settingKey,
                $setting[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS],
                implode(', ', static::ALLOWED_STATUSES),
            );
        }

        return true;
    }
}
