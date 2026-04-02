<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Schema;

use Generated\Shared\Transfer\ConfigurationSettingTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;

class ConfigurationSchemaSettingsMapper implements ConfigurationSchemaSettingsMapperInterface
{
    /**
     * @param array<mixed> $schema
     *
     * @return array<\Generated\Shared\Transfer\ConfigurationSettingTransfer>
     */
    public function mapSchemaToSettingTransfers(array $schema): array
    {
        $transfers = [];

        if (!isset($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES]) || !is_array($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES])) {
            return $transfers;
        }

        foreach ($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES] as $feature) {
            if (!isset($feature[ConfigurationConstants::SCHEMA_KEY_TABS]) || !is_array($feature[ConfigurationConstants::SCHEMA_KEY_TABS])) {
                continue;
            }

            foreach ($feature[ConfigurationConstants::SCHEMA_KEY_TABS] as $tab) {
                if (!isset($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS]) || !is_array($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS])) {
                    continue;
                }

                foreach ($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS] as $group) {
                    if (!isset($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS]) || !is_array($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS])) {
                        continue;
                    }

                    foreach ($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS] as $setting) {
                        $transfers[] = $this->mapSettingToTransfer($setting, $feature, $tab, $group);
                    }
                }
            }
        }

        return $transfers;
    }

    /**
     * @param array<mixed> $setting
     * @param array<mixed> $feature
     * @param array<mixed> $tab
     * @param array<mixed> $group
     *
     * @return \Generated\Shared\Transfer\ConfigurationSettingTransfer
     */
    protected function mapSettingToTransfer(array $setting, array $feature, array $tab, array $group): ConfigurationSettingTransfer
    {
        $defaultValue = $setting[ConfigurationConstants::SCHEMA_KEY_DEFAULT_VALUE] ?? null;

        if ($defaultValue !== null && !is_string($defaultValue)) {
            $defaultValue = (string)json_encode($defaultValue);
        }

        $compoundKey = sprintf(
            '%s:%s:%s:%s',
            $feature[ConfigurationConstants::SCHEMA_KEY_KEY],
            $tab[ConfigurationConstants::SCHEMA_KEY_KEY],
            $group[ConfigurationConstants::SCHEMA_KEY_KEY],
            $setting[ConfigurationConstants::SCHEMA_KEY_KEY],
        );

        return (new ConfigurationSettingTransfer())
            ->setKey($compoundKey)
            ->setName($setting[ConfigurationConstants::SCHEMA_KEY_NAME])
            ->setDescription($setting[ConfigurationConstants::SCHEMA_KEY_DESCRIPTION] ?? null)
            ->setHelpText($setting[ConfigurationConstants::SCHEMA_KEY_HELP_TEXT] ?? null)
            ->setPlaceholder($setting[ConfigurationConstants::SCHEMA_KEY_PLACEHOLDER] ?? null)
            ->setNote($setting[ConfigurationConstants::SCHEMA_KEY_NOTE] ?? null)
            ->setTemplate($setting[ConfigurationConstants::SCHEMA_KEY_TEMPLATE] ?? null)
            ->setFeatureKey($feature[ConfigurationConstants::SCHEMA_KEY_KEY])
            ->setTabKey($tab[ConfigurationConstants::SCHEMA_KEY_KEY])
            ->setTabName($tab[ConfigurationConstants::SCHEMA_KEY_NAME] ?? null)
            ->setTabIcon($tab[ConfigurationConstants::SCHEMA_KEY_ICON] ?? null)
            ->setGroupKey($group[ConfigurationConstants::SCHEMA_KEY_KEY])
            ->setType($setting[ConfigurationConstants::SCHEMA_KEY_TYPE])
            ->setDefaultValue($defaultValue)
            ->setOptions($setting[ConfigurationConstants::SCHEMA_KEY_OPTIONS] ?? [])
            ->setConstraints($setting[ConfigurationConstants::SCHEMA_KEY_CONSTRAINTS] ?? [])
            ->setDependencies($setting[ConfigurationConstants::SCHEMA_KEY_DEPENDENCIES] ?? [])
            ->setScopes($setting[ConfigurationConstants::SCHEMA_KEY_SCOPES] ?? [])
            ->setFileUpload($setting[ConfigurationConstants::SCHEMA_KEY_FILE_UPLOAD] ?? [])
            ->setIsSecret($setting[ConfigurationConstants::SCHEMA_KEY_SECRET] ?? false)
            ->setIsStorefront($setting[ConfigurationConstants::SCHEMA_KEY_STOREFRONT] ?? false)
            ->setIsEnabled($setting[ConfigurationConstants::SCHEMA_KEY_ENABLED] ?? true)
            ->setOrder($setting[ConfigurationConstants::SCHEMA_KEY_ORDER] ?? 0)
            ->setSanitizeXss($setting[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS] ?? []);
    }
}
