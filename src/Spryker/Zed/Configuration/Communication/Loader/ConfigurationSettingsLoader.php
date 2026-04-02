<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Loader;

use Generated\Shared\Transfer\ConfigurationSettingValuesCriteriaTransfer;
use Spryker\Client\Configuration\ConfigurationClientInterface;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;

class ConfigurationSettingsLoader implements ConfigurationSettingsLoaderInterface
{
    /**
     * @var array<mixed>|null
     */
    protected ?array $mergedSchema = null;

    public function __construct(
        protected ConfigurationFacadeInterface $configurationFacade,
        protected ConfigurationClientInterface $configurationClient,
    ) {
    }

    public function loadSettingsForTab(string $tabKey, string $scope, ?string $scopeIdentifier = null): array
    {
        $allSettings = $this->configurationFacade->getAllConfigurationSettings();

        $tabSettings = array_filter(
            $allSettings,
            fn ($setting) => $setting->getTabKey() === $tabKey && $this->isSettingAvailableForScope($setting, $scope),
        );

        $keys = array_map(static fn ($setting) => $setting->getKey(), $tabSettings);

        $criteria = (new ConfigurationSettingValuesCriteriaTransfer())
            ->setSettingKeys(array_values($keys))
            ->setScope($scope)
            ->setScopeIdentifier($scopeIdentifier);

        $valuesTransfer = $this->configurationFacade->getConfigurationSettingValues($criteria);

        $directValues = $valuesTransfer->getDirectValues();
        $inheritedValues = $valuesTransfer->getInheritedValues();

        $storageData = $this->configurationClient->getStorageDataForScope($scope, $scopeIdentifier);

        $groupMetadata = $this->extractGroupMetadata($tabKey);
        $groups = [];

        foreach ($tabSettings as $setting) {
            $key = $setting->getKeyOrFail();
            $groupKey = $setting->getGroupKeyOrFail();

            if (!isset($groups[$groupKey])) {
                $metadata = $groupMetadata[$groupKey] ?? [];

                $groups[$groupKey] = [
                    'key' => $groupKey,
                    'name' => $metadata['name'] ?? $this->formatKey($groupKey),
                    'description' => $metadata['description'] ?? null,
                    'order' => $metadata['order'] ?? 0,
                    'settings' => [],
                ];
            }

            $hasCustomValue = isset($directValues[$key]);
            $currentValue = $directValues[$key] ?? null;

            // Fall back to the schema default when no DB value exists in any scope level.
            $inheritedValue = $hasCustomValue ? null : ($inheritedValues[$key] ?? $setting->getDefaultValue());

            $currentValueFormatted = $currentValue;
            $inheritedValueFormatted = $inheritedValue;

            if ($setting->getType() === ConfigurationConstants::VALUE_TYPE_MULTISELECT) {
                $currentValueFormatted = $this->parseJsonValue($currentValue);
                $inheritedValueFormatted = $this->parseJsonValue($inheritedValue);
            }

            $pendingSync = $this->isPendingSync($setting, $currentValue, $storageData);

            $groups[$groupKey]['settings'][] = [
                'key' => $key,
                'feature_key' => $setting->getFeatureKey(),
                'tab_key' => $setting->getTabKey(),
                'name' => $setting->getName(),
                'description' => $setting->getDescription(),
                'help_text' => $setting->getHelpText(),
                'placeholder' => $setting->getPlaceholder(),
                'note' => $setting->getNote(),
                'template' => $setting->getTemplate(),
                'type' => $setting->getType(),
                'default_value' => $setting->getDefaultValue(),
                'current_value' => $currentValue,
                'current_value_parsed' => $currentValueFormatted,
                'inherited_value' => $inheritedValue,
                'inherited_value_parsed' => $inheritedValueFormatted,
                'has_custom_value' => $hasCustomValue,
                'options' => count($setting->getOptions()) ? $setting->getOptions() : [],
                'constraints' => count($setting->getConstraints()) ? $setting->getConstraints() : [],
                'dependencies' => count($setting->getDependencies()) ? $setting->getDependencies() : [],
                'file_upload' => $setting->getFileUpload() ?: [],
                'is_secret' => $setting->getIsSecret() ?? false,
                'is_storefront' => $setting->getIsStorefront() ?? false,
                'pending_sync' => $pendingSync,
                'order' => $setting->getOrder() ?? 0,
            ];
        }

        foreach ($groups as &$group) {
            usort($group['settings'], static fn (array $a, array $b) => $a['order'] <=> $b['order']);
        }

        usort($groups, static fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return $groups;
    }

    protected function formatKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * @param string|null $value
     *
     * @return array<string>
     */
    protected function parseJsonValue(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * @param string $tabKey
     *
     * @return array<string, array<string, mixed>>
     */
    protected function extractGroupMetadata(string $tabKey): array
    {
        $schema = $this->getMergedSchema();
        $groupMetadata = [];

        if (!isset($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES]) || !is_array($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES])) {
            return $groupMetadata;
        }

        foreach ($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES] as $feature) {
            if (!isset($feature[ConfigurationConstants::SCHEMA_KEY_TABS]) || !is_array($feature[ConfigurationConstants::SCHEMA_KEY_TABS])) {
                continue;
            }

            foreach ($feature[ConfigurationConstants::SCHEMA_KEY_TABS] as $tab) {
                if ($tab[ConfigurationConstants::SCHEMA_KEY_KEY] !== $tabKey || !isset($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS]) || !is_array($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS])) {
                    continue;
                }

                foreach ($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS] as $group) {
                    $groupKey = $group[ConfigurationConstants::SCHEMA_KEY_KEY] ?? null;

                    if ($groupKey === null) {
                        continue;
                    }

                    $groupMetadata[$groupKey] = [
                        'name' => $group[ConfigurationConstants::SCHEMA_KEY_NAME] ?? $this->formatKey($groupKey),
                        'description' => $group[ConfigurationConstants::SCHEMA_KEY_DESCRIPTION] ?? null,
                        'order' => $group[ConfigurationConstants::SCHEMA_KEY_ORDER] ?? 0,
                    ];
                }
            }
        }

        return $groupMetadata;
    }

    /**
     * @return array<mixed>
     */
    protected function getMergedSchema(): array
    {
        if ($this->mergedSchema === null) {
            $this->mergedSchema = $this->configurationFacade->getMergedConfigurationSchema();
        }

        return $this->mergedSchema;
    }

    /**
     * Determines whether a storefront-enabled setting has a DB value that differs from
     * what is currently in Redis, meaning P&S has not yet propagated the change.
     *
     * @param \Generated\Shared\Transfer\ConfigurationSettingTransfer $setting
     * @param string|null $currentDbValue
     * @param array<string, string> $storageData
     *
     * @return bool
     */
    protected function isPendingSync($setting, ?string $currentDbValue, array $storageData): bool
    {
        if (!$setting->getIsStorefront() || $setting->getIsSecret()) {
            return false;
        }

        if ($currentDbValue === null) {
            // No DB value at this scope — storage should not have it either.
            return isset($storageData[$setting->getKey()]);
        }

        $storageValue = $storageData[$setting->getKey()] ?? null;

        return $storageValue !== $currentDbValue;
    }

    /**
     * @param \Generated\Shared\Transfer\ConfigurationSettingTransfer $setting
     * @param string $scope
     *
     * @return bool
     */
    protected function isSettingAvailableForScope($setting, string $scope): bool
    {
        $allowedScopes = $setting->getScopes();

        if (count($allowedScopes) === 0) {
            return true;
        }

        return in_array($scope, $allowedScopes, true);
    }
}
