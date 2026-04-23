<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Schema;

use Generated\Shared\Transfer\ConfigurationSettingTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;

class ConfigurationSchemaSettingsMapper implements ConfigurationSchemaSettingsMapperInterface
{
    public function __construct(
        protected UtilEncodingServiceInterface $utilEncodingService,
    ) {
    }

    /**
     * @param array<mixed> $schema
     *
     * @return array<\Generated\Shared\Transfer\ConfigurationSettingTransfer>
     */
    public function mapSchemaToSettingTransfers(array $schema): array
    {
        $transfers = [];

        if (!isset($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES]) || !is_array($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES])) {
            return $transfers;
        }

        foreach ($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] as $feature) {
            if (!$this->isEnabled($feature)) {
                continue;
            }

            $this->mapFeatureSettings($feature, $transfers);
        }

        return $transfers;
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function isEnabled(array $item): bool
    {
        return ($item[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true) !== false;
    }

    /**
     * @param array<mixed> $feature
     * @param array<\Generated\Shared\Transfer\ConfigurationSettingTransfer> $transfers
     */
    protected function mapFeatureSettings(array $feature, array &$transfers): void
    {
        if (!isset($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS]) || !is_array($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS])) {
            return;
        }

        foreach ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] as $tab) {
            if (!$this->isEnabled($tab)) {
                continue;
            }

            $this->mapTabSettings($feature, $tab, $transfers);
        }
    }

    /**
     * @param array<mixed> $feature
     * @param array<mixed> $tab
     * @param array<\Generated\Shared\Transfer\ConfigurationSettingTransfer> $transfers
     */
    protected function mapTabSettings(array $feature, array $tab, array &$transfers): void
    {
        if (!isset($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS]) || !is_array($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS])) {
            return;
        }

        foreach ($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] as $group) {
            if (!$this->isEnabled($group)) {
                continue;
            }

            $this->mapGroupSettings($feature, $tab, $group, $transfers);
        }
    }

    /**
     * @param array<mixed> $feature
     * @param array<mixed> $tab
     * @param array<mixed> $group
     * @param array<\Generated\Shared\Transfer\ConfigurationSettingTransfer> $transfers
     */
    protected function mapGroupSettings(array $feature, array $tab, array $group, array &$transfers): void
    {
        if (!isset($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS]) || !is_array($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS])) {
            return;
        }

        foreach ($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] as $setting) {
            if (!$this->isEnabled($setting)) {
                continue;
            }

            $transfers[] = $this->mapSettingToTransfer($setting, $feature, $tab, $group);
        }
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
        $defaultValue = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_DEFAULT_VALUE] ?? null;

        if ($defaultValue !== null && !is_string($defaultValue)) {
            $defaultValue = (string)$this->utilEncodingService->encodeJson($defaultValue);
        }

        $compoundKey = sprintf(
            '%s:%s:%s:%s',
            $feature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY],
            $tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY],
            $group[ConfigurationSchemaConstants::SCHEMA_KEY_KEY],
            $setting[ConfigurationSchemaConstants::SCHEMA_KEY_KEY],
        );

        return (new ConfigurationSettingTransfer())
            ->setKey($compoundKey)
            ->setName($setting[ConfigurationSchemaConstants::SCHEMA_KEY_NAME])
            ->setDescription($setting[ConfigurationSchemaConstants::SCHEMA_KEY_DESCRIPTION] ?? null)
            ->setHelpText($setting[ConfigurationSchemaConstants::SCHEMA_KEY_HELP_TEXT] ?? null)
            ->setPlaceholder($setting[ConfigurationSchemaConstants::SCHEMA_KEY_PLACEHOLDER] ?? null)
            ->setNote($setting[ConfigurationSchemaConstants::SCHEMA_KEY_NOTE] ?? null)
            ->setTemplate($setting[ConfigurationSchemaConstants::SCHEMA_KEY_TEMPLATE] ?? null)
            ->setFeatureKey($feature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY])
            ->setTabKey($tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY])
            ->setTabName($tab[ConfigurationSchemaConstants::SCHEMA_KEY_NAME] ?? null)
            ->setTabIcon($tab[ConfigurationSchemaConstants::SCHEMA_KEY_ICON] ?? null)
            ->setGroupKey($group[ConfigurationSchemaConstants::SCHEMA_KEY_KEY])
            ->setType($setting[ConfigurationSchemaConstants::SCHEMA_KEY_TYPE])
            ->setDefaultValue($defaultValue)
            ->setOptions($setting[ConfigurationSchemaConstants::SCHEMA_KEY_OPTIONS] ?? [])
            ->setConstraints($setting[ConfigurationSchemaConstants::SCHEMA_KEY_CONSTRAINTS] ?? [])
            ->setDependencies($setting[ConfigurationSchemaConstants::SCHEMA_KEY_DEPENDENCIES] ?? [])
            ->setScopes($setting[ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES] ?? [])
            ->setFileUpload($setting[ConfigurationSchemaConstants::SCHEMA_KEY_FILE_UPLOAD] ?? [])
            ->setIsSecret($setting[ConfigurationSchemaConstants::SCHEMA_KEY_SECRET] ?? false)
            ->setIsStorefront($setting[ConfigurationSchemaConstants::SCHEMA_KEY_STOREFRONT] ?? false)
            ->setIsEnabled($setting[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true)
            ->setOrder($setting[ConfigurationSchemaConstants::SCHEMA_KEY_ORDER] ?? 0)
            ->setStatus($setting[ConfigurationSchemaConstants::SCHEMA_KEY_STATUS] ?? null)
            ->setSanitizeXss($setting[ConfigurationSchemaConstants::SCHEMA_KEY_SANITIZE_XSS] ?? [])
            ->setDataObject($setting[ConfigurationSchemaConstants::SCHEMA_KEY_DATA_OBJECT] ?? null);
    }
}
