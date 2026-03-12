<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Builder;

use Generated\Shared\Transfer\ConfigurationSettingTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;

class ConfigurationNavigationBuilder implements ConfigurationNavigationBuilderInterface
{
    /**
     * @var array<mixed>|null
     */
    protected ?array $mergedSchema = null;

    public function __construct(
        protected ConfigurationFacadeInterface $configurationFacade,
    ) {
    }

    public function buildNavigationTree(string $scope = ConfigurationConstants::SCOPE_GLOBAL): array
    {
        $allSettings = $this->configurationFacade->getAllConfigurationSettings();
        $schemaMetadata = $this->extractSchemaMetadata();

        $tree = [];

        foreach ($allSettings as $setting) {
            if (!$this->isSettingAvailableForScope($setting, $scope)) {
                continue;
            }

            $featureKey = $setting->getFeatureKeyOrFail();
            $tabKey = $setting->getTabKeyOrFail();

            if (!isset($tree[$featureKey])) {
                $featureMetadata = $schemaMetadata[ConfigurationConstants::SCHEMA_KEY_FEATURES][$featureKey] ?? [];

                $tree[$featureKey] = [
                    'key' => $featureKey,
                    'name' => $featureMetadata[ConfigurationConstants::SCHEMA_KEY_NAME] ?? $this->formatKey($featureKey),
                    'order' => $featureMetadata[ConfigurationConstants::SCHEMA_KEY_ORDER] ?? 0,
                    'tabs' => [],
                ];
            }

            if (!isset($tree[$featureKey]['tabs'][$tabKey])) {
                $tabMetadata = $schemaMetadata[ConfigurationConstants::SCHEMA_KEY_TABS][$featureKey][$tabKey] ?? [];

                $tree[$featureKey]['tabs'][$tabKey] = [
                    'key' => $tabKey,
                    'name' => $tabMetadata[ConfigurationConstants::SCHEMA_KEY_NAME] ?? $this->formatKey($tabKey),
                    'icon' => $tabMetadata[ConfigurationConstants::SCHEMA_KEY_ICON] ?? null,
                    'order' => $tabMetadata[ConfigurationConstants::SCHEMA_KEY_ORDER] ?? 0,
                    'featureKey' => $featureKey,
                ];
            }
        }

        foreach ($tree as &$feature) {
            usort($feature['tabs'], static fn (array $a, array $b) => $a['order'] <=> $b['order']);
        }

        usort($tree, static fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return $tree;
    }

    protected function formatKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * @return array<mixed>
     */
    protected function extractSchemaMetadata(): array
    {
        $schema = $this->getMergedSchema();
        $metadata = [
            ConfigurationConstants::SCHEMA_KEY_FEATURES => [],
            ConfigurationConstants::SCHEMA_KEY_TABS => [],
        ];

        if (!isset($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES]) || !is_array($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES])) {
            return $metadata;
        }

        foreach ($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES] as $feature) {
            $featureKey = $feature[ConfigurationConstants::SCHEMA_KEY_KEY] ?? null;

            if ($featureKey === null) {
                continue;
            }

            $metadata[ConfigurationConstants::SCHEMA_KEY_FEATURES][$featureKey] = [
                ConfigurationConstants::SCHEMA_KEY_NAME => $feature[ConfigurationConstants::SCHEMA_KEY_NAME] ?? $this->formatKey($featureKey),
                ConfigurationConstants::SCHEMA_KEY_DESCRIPTION => $feature[ConfigurationConstants::SCHEMA_KEY_DESCRIPTION] ?? null,
                ConfigurationConstants::SCHEMA_KEY_ORDER => $feature[ConfigurationConstants::SCHEMA_KEY_ORDER] ?? 0,
            ];

            if (!isset($feature[ConfigurationConstants::SCHEMA_KEY_TABS]) || !is_array($feature[ConfigurationConstants::SCHEMA_KEY_TABS])) {
                continue;
            }

            foreach ($feature[ConfigurationConstants::SCHEMA_KEY_TABS] as $tab) {
                $tabKey = $tab[ConfigurationConstants::SCHEMA_KEY_KEY] ?? null;

                if ($tabKey === null) {
                    continue;
                }

                if (!isset($metadata[ConfigurationConstants::SCHEMA_KEY_TABS][$featureKey])) {
                    $metadata[ConfigurationConstants::SCHEMA_KEY_TABS][$featureKey] = [];
                }

                $metadata[ConfigurationConstants::SCHEMA_KEY_TABS][$featureKey][$tabKey] = [
                    ConfigurationConstants::SCHEMA_KEY_NAME => $tab[ConfigurationConstants::SCHEMA_KEY_NAME] ?? $this->formatKey($tabKey),
                    ConfigurationConstants::SCHEMA_KEY_DESCRIPTION => $tab[ConfigurationConstants::SCHEMA_KEY_DESCRIPTION] ?? null,
                    ConfigurationConstants::SCHEMA_KEY_ICON => $tab[ConfigurationConstants::SCHEMA_KEY_ICON] ?? null,
                    ConfigurationConstants::SCHEMA_KEY_ORDER => $tab[ConfigurationConstants::SCHEMA_KEY_ORDER] ?? 0,
                ];
            }
        }

        return $metadata;
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

    protected function isSettingAvailableForScope(ConfigurationSettingTransfer $setting, string $scope): bool
    {
        $allowedScopes = $setting->getScopes();

        if (count($allowedScopes) === 0) {
            return true;
        }

        return in_array($scope, $allowedScopes, true);
    }
}
