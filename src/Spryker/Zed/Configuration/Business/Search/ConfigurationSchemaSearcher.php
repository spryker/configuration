<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Search;

use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;
use Spryker\Zed\Translator\Business\TranslatorFacadeInterface;

class ConfigurationSchemaSearcher implements ConfigurationSchemaSearcherInterface
{
    public function __construct(
        protected ConfigurationSchemaProviderInterface $schemaProvider,
        protected TranslatorFacadeInterface $translatorFacade,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $term, string $scope): array
    {
        $term = mb_strtolower(trim($term));

        if ($term === '') {
            return [];
        }

        $schema = $this->schemaProvider->getMergedSchema();
        $matches = [];

        foreach ($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] ?? [] as $feature) {
            $featureKey = $feature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? null;

            if ($featureKey === null || !$this->isEnabled($feature)) {
                continue;
            }

            $featureMatches = $this->containsTerm($feature, $term);
            $matchingTabs = $this->searchTabs($feature, $term, $scope);

            if ($featureMatches || $matchingTabs !== []) {
                $matches[$featureKey] = $matchingTabs !== [] ? $matchingTabs : $this->getAllTabKeys($feature);
            }
        }

        return $matches;
    }

    /**
     * @param array<string, mixed> $feature
     *
     * @return array<string>
     */
    protected function searchTabs(array $feature, string $term, string $scope): array
    {
        $matchingTabs = [];

        foreach ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] ?? [] as $tab) {
            $tabKey = $tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? null;

            if ($tabKey === null || !$this->isEnabled($tab)) {
                continue;
            }

            $tabMatches = $this->containsTerm($tab, $term);
            $hasMatchingDescendants = $this->hasMatchingGroupsOrSettings($tab, $term, $scope);

            if ($tabMatches || $hasMatchingDescendants) {
                $matchingTabs[] = $tabKey;
            }
        }

        return $matchingTabs;
    }

    /**
     * @param array<string, mixed> $tab
     */
    protected function hasMatchingGroupsOrSettings(array $tab, string $term, string $scope): bool
    {
        foreach ($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] ?? [] as $group) {
            if (!$this->isEnabled($group)) {
                continue;
            }

            if ($this->containsTerm($group, $term) || $this->hasMatchingSettings($group, $term, $scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $group
     */
    protected function hasMatchingSettings(array $group, string $term, string $scope): bool
    {
        foreach ($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] ?? [] as $setting) {
            if (!$this->isEnabled($setting) || !$this->isSettingAvailableForScope($setting, $scope)) {
                continue;
            }

            if ($this->containsTerm($setting, $term) || $this->keyContainsTerm($setting, $term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function containsTerm(array $item, string $term): bool
    {
        $name = $item[ConfigurationSchemaConstants::SCHEMA_KEY_NAME] ?? '';
        $description = $item[ConfigurationSchemaConstants::SCHEMA_KEY_DESCRIPTION] ?? '';

        $translatedName = mb_strtolower($this->translatorFacade->trans($name));
        $translatedDescription = $description !== ''
            ? mb_strtolower($this->translatorFacade->trans($description))
            : '';

        return str_contains($translatedName, $term) || str_contains($translatedDescription, $term);
    }

    /**
     * @param array<string, mixed> $setting
     */
    protected function keyContainsTerm(array $setting, string $term): bool
    {
        $key = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? '';

        return str_contains(mb_strtolower($key), $term);
    }

    /**
     * @param array<string, mixed> $setting
     */
    protected function isSettingAvailableForScope(array $setting, string $scope): bool
    {
        $scopes = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES] ?? [];

        if ($scopes === []) {
            return true;
        }

        return in_array($scope, $scopes, true);
    }

    /**
     * @param array<string, mixed> $feature
     *
     * @return array<string>
     */
    protected function getAllTabKeys(array $feature): array
    {
        $tabKeys = [];

        foreach ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] ?? [] as $tab) {
            $tabKey = $tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? null;

            if ($tabKey !== null && $this->isEnabled($tab)) {
                $tabKeys[] = $tabKey;
            }
        }

        return $tabKeys;
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function isEnabled(array $item): bool
    {
        return ($item[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true) !== false;
    }
}
