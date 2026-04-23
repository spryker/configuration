<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Builder;

use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;

class ConfigurationNavigationSchemaMetadataExtractor implements ConfigurationNavigationSchemaMetadataExtractorInterface
{
    public function __construct(
        protected ConfigurationFacadeInterface $configurationFacade,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function extractSchemaMetadata(): array
    {
        $schema = $this->configurationFacade->getMergedConfigurationSchema();
        $metadata = [
            ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES => [],
            ConfigurationSchemaConstants::SCHEMA_KEY_TABS => [],
        ];

        if (!isset($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES]) || !is_array($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES])) {
            return $metadata;
        }

        foreach ($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] as $feature) {
            $featureKey = $feature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? null;

            if ($featureKey === null) {
                continue;
            }

            $metadata[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES][$featureKey] = [
                ConfigurationSchemaConstants::SCHEMA_KEY_NAME => $feature[ConfigurationSchemaConstants::SCHEMA_KEY_NAME] ?? $this->formatKey($featureKey),
                ConfigurationSchemaConstants::SCHEMA_KEY_DESCRIPTION => $feature[ConfigurationSchemaConstants::SCHEMA_KEY_DESCRIPTION] ?? null,
                ConfigurationSchemaConstants::SCHEMA_KEY_ORDER => $feature[ConfigurationSchemaConstants::SCHEMA_KEY_ORDER] ?? 0,
                ConfigurationSchemaConstants::SCHEMA_KEY_STATUS => $feature[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] ?? null,
                ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED => $feature[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true,
            ];

            if (!isset($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS]) || !is_array($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS])) {
                continue;
            }

            $this->addTabMetadata($feature, $featureKey, $metadata);
        }

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function extractDisabledFeatureKeys(array $schemaMetadata): array
    {
        $disabled = [];

        foreach ($schemaMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] ?? [] as $key => $metadata) {
            if (($metadata[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true) === false) {
                $disabled[$key] = true;
            }
        }

        return $disabled;
    }

    /**
     * {@inheritDoc}
     */
    public function extractDisabledTabKeys(array $schemaMetadata): array
    {
        $disabled = [];

        foreach ($schemaMetadata[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] ?? [] as $featureKey => $tabs) {
            foreach ($tabs as $tabKey => $metadata) {
                if (($metadata[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true) === false) {
                    $disabled[$featureKey][$tabKey] = true;
                }
            }
        }

        return $disabled;
    }

    protected function formatKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * @param array<mixed> $feature
     * @param array<mixed> $metadata
     */
    protected function addTabMetadata(array $feature, string $featureKey, array &$metadata): void
    {
        foreach ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] as $tab) {
            $tabKey = $tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? null;

            if ($tabKey === null) {
                continue;
            }

            if (!isset($metadata[ConfigurationSchemaConstants::SCHEMA_KEY_TABS][$featureKey])) {
                $metadata[ConfigurationSchemaConstants::SCHEMA_KEY_TABS][$featureKey] = [];
            }

            $metadata[ConfigurationSchemaConstants::SCHEMA_KEY_TABS][$featureKey][$tabKey] = [
                ConfigurationSchemaConstants::SCHEMA_KEY_NAME => $tab[ConfigurationSchemaConstants::SCHEMA_KEY_NAME] ?? $this->formatKey($tabKey),
                ConfigurationSchemaConstants::SCHEMA_KEY_DESCRIPTION => $tab[ConfigurationSchemaConstants::SCHEMA_KEY_DESCRIPTION] ?? null,
                ConfigurationSchemaConstants::SCHEMA_KEY_ICON => $tab[ConfigurationSchemaConstants::SCHEMA_KEY_ICON] ?? null,
                ConfigurationSchemaConstants::SCHEMA_KEY_ORDER => $tab[ConfigurationSchemaConstants::SCHEMA_KEY_ORDER] ?? 0,
                ConfigurationSchemaConstants::SCHEMA_KEY_STATUS => $tab[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] ?? null,
                ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED => $tab[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true,
            ];
        }
    }
}
