<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Builder;

use Generated\Shared\Transfer\ConfigurationSettingTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;

class ConfigurationNavigationBuilder implements ConfigurationNavigationBuilderInterface
{
    protected const string NODE_KEY_KEY = 'key';

    protected const string NODE_KEY_NAME = 'name';

    protected const string NODE_KEY_DESCRIPTION = 'description';

    protected const string NODE_KEY_ORDER = 'order';

    protected const string NODE_KEY_STATUS = 'status';

    protected const string NODE_KEY_ICON = 'icon';

    protected const string NODE_KEY_TABS = 'tabs';

    protected const string NODE_KEY_FEATURE_KEY = 'featureKey';

    public function __construct(
        protected ConfigurationFacadeInterface $configurationFacade,
        protected ConfigurationNavigationSchemaMetadataExtractorInterface $schemaMetadataExtractor,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function buildNavigationTree(string $scope = ConfigurationConstants::SCOPE_GLOBAL): array
    {
        $allSettings = $this->configurationFacade->getAllConfigurationSettings();
        $schemaMetadata = $this->schemaMetadataExtractor->extractSchemaMetadata();

        $disabledFeatures = $this->schemaMetadataExtractor->extractDisabledFeatureKeys($schemaMetadata);
        $disabledTabs = $this->schemaMetadataExtractor->extractDisabledTabKeys($schemaMetadata);

        $tree = [];

        foreach ($allSettings as $setting) {
            if (!$this->isSettingAvailableForScope($setting, $scope)) {
                continue;
            }

            $featureKey = $setting->getFeatureKeyOrFail();
            $tabKey = $setting->getTabKeyOrFail();

            if (isset($disabledFeatures[$featureKey]) || isset($disabledTabs[$featureKey][$tabKey])) {
                continue;
            }

            $this->addFeatureNode($tree, $schemaMetadata, $featureKey);
            $this->addTabNode($tree, $schemaMetadata, $featureKey, $tabKey);
        }

        foreach ($tree as &$feature) {
            usort($feature[static::NODE_KEY_TABS], static fn (array $a, array $b) => $a[static::NODE_KEY_ORDER] <=> $b[static::NODE_KEY_ORDER]);
        }

        usort($tree, static fn (array $a, array $b) => $a[static::NODE_KEY_ORDER] <=> $b[static::NODE_KEY_ORDER]);

        return $tree;
    }

    /**
     * @param array<string, array<string, mixed>> $tree
     * @param array<mixed> $schemaMetadata
     */
    protected function addFeatureNode(array &$tree, array $schemaMetadata, string $featureKey): void
    {
        if (isset($tree[$featureKey])) {
            return;
        }

        $featureMetadata = $schemaMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES][$featureKey] ?? [];

        $tree[$featureKey] = [
            static::NODE_KEY_KEY => $featureKey,
            static::NODE_KEY_NAME => $featureMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_NAME] ?? $this->formatKey($featureKey),
            static::NODE_KEY_DESCRIPTION => $featureMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_DESCRIPTION] ?? null,
            static::NODE_KEY_ORDER => $featureMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_ORDER] ?? 0,
            static::NODE_KEY_TABS => [],
            static::NODE_KEY_STATUS => $featureMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] ?? null,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $tree
     * @param array<mixed> $schemaMetadata
     */
    protected function addTabNode(array &$tree, array $schemaMetadata, string $featureKey, string $tabKey): void
    {
        if (isset($tree[$featureKey][static::NODE_KEY_TABS][$tabKey])) {
            return;
        }

        $tabMetadata = $schemaMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_TABS][$featureKey][$tabKey] ?? [];

        $tree[$featureKey][static::NODE_KEY_TABS][$tabKey] = [
            static::NODE_KEY_KEY => $tabKey,
            static::NODE_KEY_NAME => $tabMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_NAME] ?? $this->formatKey($tabKey),
            static::NODE_KEY_DESCRIPTION => $tabMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_DESCRIPTION] ?? null,
            static::NODE_KEY_ICON => $tabMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_ICON] ?? null,
            static::NODE_KEY_ORDER => $tabMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_ORDER] ?? 0,
            static::NODE_KEY_FEATURE_KEY => $featureKey,
            static::NODE_KEY_STATUS => $tabMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] ?? null,
        ];
    }

    protected function formatKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
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
