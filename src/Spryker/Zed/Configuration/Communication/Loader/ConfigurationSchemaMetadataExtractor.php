<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Loader;

use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;

class ConfigurationSchemaMetadataExtractor implements ConfigurationSchemaMetadataExtractorInterface
{
    protected const string NODE_KEY_NAME = 'name';

    protected const string NODE_KEY_DESCRIPTION = 'description';

    protected const string NODE_KEY_ORDER = 'order';

    protected const string NODE_KEY_STATUS = 'status';

    public function __construct(
        protected ConfigurationFacadeInterface $configurationFacade,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function extractGroupMetadata(string $featureKey, string $tabKey): array
    {
        $tab = $this->findTabInSchema($featureKey, $tabKey);

        if ($tab === null) {
            return [];
        }

        $groupMetadata = [];

        foreach ($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] ?? [] as $group) {
            $groupKey = $group[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? null;

            if ($groupKey === null || ($group[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true) === false) {
                continue;
            }

            $groupMetadata[$groupKey] = [
                static::NODE_KEY_NAME => $group[ConfigurationSchemaConstants::SCHEMA_KEY_NAME] ?? $this->formatKey($groupKey),
                static::NODE_KEY_DESCRIPTION => $group[ConfigurationSchemaConstants::SCHEMA_KEY_DESCRIPTION] ?? null,
                static::NODE_KEY_ORDER => $group[ConfigurationSchemaConstants::SCHEMA_KEY_ORDER] ?? 0,
                static::NODE_KEY_STATUS => $group[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] ?? null,
            ];
        }

        return $groupMetadata;
    }

    /**
     * {@inheritDoc}
     */
    public function extractSettingOverrides(string $featureKey, string $tabKey): array
    {
        $tab = $this->findTabInSchema($featureKey, $tabKey);

        if ($tab === null) {
            return [];
        }

        /** @var array<string, array<int, array<string, string>>> $overrides */
        $overrides = [];

        foreach ($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] ?? [] as $group) {
            $this->extractGroupOverrides($featureKey, $tabKey, $group, $overrides);
        }

        return $overrides;
    }

    protected function formatKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * @param array<mixed> $group
     * @param array<string, array<int, array<string, string>>> $overrides
     */
    protected function extractGroupOverrides(string $featureKey, string $tabKey, array $group, array &$overrides): void
    {
        $groupKey = $group[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? null;

        if ($groupKey === null) {
            return;
        }

        foreach ($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] ?? [] as $setting) {
            $compoundKey = sprintf('%s:%s:%s:%s', $featureKey, $tabKey, $groupKey, $setting[ConfigurationSchemaConstants::SCHEMA_KEY_KEY]);

            /** @var array<int, array<string, string>> $settingOverrides */
            $settingOverrides = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_OVERRIDES] ?? [];
            $overrides[$compoundKey] = $settingOverrides;
        }
    }

    /**
     * @return array<mixed>|null
     */
    protected function findTabInSchema(string $featureKey, string $tabKey): ?array
    {
        $schema = $this->configurationFacade->getMergedConfigurationSchema();

        foreach ($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] ?? [] as $feature) {
            if (($feature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? null) !== $featureKey) {
                continue;
            }

            foreach ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] ?? [] as $tab) {
                if (($tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? null) === $tabKey) {
                    return $tab;
                }
            }
        }

        return null;
    }
}
