<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Schema;

use Spryker\Shared\Configuration\ConfigurationConstants;

class SchemaMerger implements SchemaMergerInterface
{
    /**
     * @param array<mixed> $coreSchema
     * @param array<mixed> $projectSchema
     *
     * @return array<mixed>
     */
    public function merge(array $coreSchema, array $projectSchema): array
    {
        if (!isset($coreSchema[ConfigurationConstants::SCHEMA_KEY_FEATURES])) {
            return $projectSchema;
        }

        if (!isset($projectSchema[ConfigurationConstants::SCHEMA_KEY_FEATURES])) {
            return $coreSchema;
        }

        $mergedSchema = $coreSchema;
        $mergedSchema[ConfigurationConstants::SCHEMA_KEY_FEATURES] = $this->mergeFeatures(
            $coreSchema[ConfigurationConstants::SCHEMA_KEY_FEATURES],
            $projectSchema[ConfigurationConstants::SCHEMA_KEY_FEATURES],
        );

        return $mergedSchema;
    }

    /**
     * @param array<mixed> $coreFeatures
     * @param array<mixed> $projectFeatures
     *
     * @return array<mixed>
     */
    protected function mergeFeatures(array $coreFeatures, array $projectFeatures): array
    {
        $featuresByKey = [];

        foreach ($coreFeatures as $feature) {
            $featuresByKey[$feature[ConfigurationConstants::SCHEMA_KEY_KEY]] = $feature;
        }

        foreach ($projectFeatures as $projectFeature) {
            $key = $projectFeature[ConfigurationConstants::SCHEMA_KEY_KEY];

            if (isset($featuresByKey[$key])) {
                $featuresByKey[$key] = $this->mergeFeature($featuresByKey[$key], $projectFeature);

                continue;
            }

            $featuresByKey[$key] = $projectFeature;
        }

        return array_values($featuresByKey);
    }

    /**
     * @param array<mixed> $coreFeature
     * @param array<mixed> $projectFeature
     *
     * @return array<mixed>
     */
    protected function mergeFeature(array $coreFeature, array $projectFeature): array
    {
        $mergedFeature = array_merge($coreFeature, $projectFeature);

        if (isset($coreFeature[ConfigurationConstants::SCHEMA_KEY_TABS]) && isset($projectFeature[ConfigurationConstants::SCHEMA_KEY_TABS])) {
            $mergedFeature[ConfigurationConstants::SCHEMA_KEY_TABS] = $this->mergeTabs($coreFeature[ConfigurationConstants::SCHEMA_KEY_TABS], $projectFeature[ConfigurationConstants::SCHEMA_KEY_TABS]);
        }

        return $mergedFeature;
    }

    /**
     * @param array<mixed> $coreTabs
     * @param array<mixed> $projectTabs
     *
     * @return array<mixed>
     */
    protected function mergeTabs(array $coreTabs, array $projectTabs): array
    {
        $tabsByKey = [];

        foreach ($coreTabs as $tab) {
            $tabsByKey[$tab[ConfigurationConstants::SCHEMA_KEY_KEY]] = $tab;
        }

        foreach ($projectTabs as $projectTab) {
            $key = $projectTab[ConfigurationConstants::SCHEMA_KEY_KEY];

            if (isset($tabsByKey[$key])) {
                $tabsByKey[$key] = $this->mergeTab($tabsByKey[$key], $projectTab);

                continue;
            }

            $tabsByKey[$key] = $projectTab;
        }

        return array_values($tabsByKey);
    }

    /**
     * @param array<mixed> $coreTab
     * @param array<mixed> $projectTab
     *
     * @return array<mixed>
     */
    protected function mergeTab(array $coreTab, array $projectTab): array
    {
        $mergedTab = array_merge($coreTab, $projectTab);

        if (isset($coreTab[ConfigurationConstants::SCHEMA_KEY_GROUPS]) && isset($projectTab[ConfigurationConstants::SCHEMA_KEY_GROUPS])) {
            $mergedTab[ConfigurationConstants::SCHEMA_KEY_GROUPS] = $this->mergeGroups($coreTab[ConfigurationConstants::SCHEMA_KEY_GROUPS], $projectTab[ConfigurationConstants::SCHEMA_KEY_GROUPS]);
        }

        return $mergedTab;
    }

    /**
     * @param array<mixed> $coreGroups
     * @param array<mixed> $projectGroups
     *
     * @return array<mixed>
     */
    protected function mergeGroups(array $coreGroups, array $projectGroups): array
    {
        $groupsByKey = [];

        foreach ($coreGroups as $group) {
            $groupsByKey[$group[ConfigurationConstants::SCHEMA_KEY_KEY]] = $group;
        }

        foreach ($projectGroups as $projectGroup) {
            $key = $projectGroup[ConfigurationConstants::SCHEMA_KEY_KEY];

            if (isset($groupsByKey[$key])) {
                $groupsByKey[$key] = $this->mergeGroup($groupsByKey[$key], $projectGroup);

                continue;
            }

            $groupsByKey[$key] = $projectGroup;
        }

        return array_values($groupsByKey);
    }

    /**
     * @param array<mixed> $coreGroup
     * @param array<mixed> $projectGroup
     *
     * @return array<mixed>
     */
    protected function mergeGroup(array $coreGroup, array $projectGroup): array
    {
        $mergedGroup = array_merge($coreGroup, $projectGroup);

        if (isset($coreGroup[ConfigurationConstants::SCHEMA_KEY_SETTINGS]) && isset($projectGroup[ConfigurationConstants::SCHEMA_KEY_SETTINGS])) {
            $mergedGroup[ConfigurationConstants::SCHEMA_KEY_SETTINGS] = $this->mergeSettings($coreGroup[ConfigurationConstants::SCHEMA_KEY_SETTINGS], $projectGroup[ConfigurationConstants::SCHEMA_KEY_SETTINGS]);
        }

        return $mergedGroup;
    }

    /**
     * @param array<mixed> $coreSettings
     * @param array<mixed> $projectSettings
     *
     * @return array<mixed>
     */
    protected function mergeSettings(array $coreSettings, array $projectSettings): array
    {
        $settingsByKey = [];

        foreach ($coreSettings as $setting) {
            $settingsByKey[$setting[ConfigurationConstants::SCHEMA_KEY_KEY]] = $setting;
        }

        foreach ($projectSettings as $projectSetting) {
            $settingsByKey[$projectSetting[ConfigurationConstants::SCHEMA_KEY_KEY]] = $projectSetting;
        }

        return array_values($settingsByKey);
    }
}
