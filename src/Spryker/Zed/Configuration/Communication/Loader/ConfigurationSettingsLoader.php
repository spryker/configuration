<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Loader;

use Generated\Shared\Transfer\ConfigurationSettingTransfer;
use Generated\Shared\Transfer\ConfigurationSettingValuesCriteriaTransfer;
use Spryker\Client\Configuration\ConfigurationClientInterface;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use Spryker\Zed\Configuration\Communication\Resolver\ConfigurationDataObjectResolverInterface;

class ConfigurationSettingsLoader implements ConfigurationSettingsLoaderInterface
{
    protected const string NODE_KEY_KEY = 'key';

    protected const string NODE_KEY_NAME = 'name';

    protected const string NODE_KEY_DESCRIPTION = 'description';

    protected const string NODE_KEY_ORDER = 'order';

    protected const string NODE_KEY_STATUS = 'status';

    protected const string NODE_KEY_SETTINGS = 'settings';

    protected const string NODE_KEY_FEATURE_KEY = 'feature_key';

    protected const string NODE_KEY_TAB_KEY = 'tab_key';

    protected const string NODE_KEY_HELP_TEXT = 'help_text';

    protected const string NODE_KEY_PLACEHOLDER = 'placeholder';

    protected const string NODE_KEY_NOTE = 'note';

    protected const string NODE_KEY_TEMPLATE = 'template';

    protected const string NODE_KEY_TYPE = 'type';

    protected const string NODE_KEY_DEFAULT_VALUE = 'default_value';

    protected const string NODE_KEY_CURRENT_VALUE = 'current_value';

    protected const string NODE_KEY_CURRENT_VALUE_PARSED = 'current_value_parsed';

    protected const string NODE_KEY_INHERITED_VALUE = 'inherited_value';

    protected const string NODE_KEY_INHERITED_VALUE_PARSED = 'inherited_value_parsed';

    protected const string NODE_KEY_HAS_CUSTOM_VALUE = 'has_custom_value';

    protected const string NODE_KEY_OPTIONS = 'options';

    protected const string NODE_KEY_CONSTRAINTS = 'constraints';

    protected const string NODE_KEY_DEPENDENCIES = 'dependencies';

    protected const string NODE_KEY_FILE_UPLOAD = 'file_upload';

    protected const string NODE_KEY_IS_SECRET = 'is_secret';

    protected const string NODE_KEY_IS_STOREFRONT = 'is_storefront';

    protected const string NODE_KEY_PENDING_SYNC = 'pending_sync';

    protected const string NODE_KEY_IS_OVERRIDDEN = 'is_overridden';

    protected const string NODE_KEY_OVERRIDE_INFO = 'override_info';

    public function __construct(
        protected ConfigurationFacadeInterface $configurationFacade,
        protected ConfigurationClientInterface $configurationClient,
        protected ConfigurationDataObjectResolverInterface $dataObjectResolver,
        protected UtilEncodingServiceInterface $utilEncodingService,
        protected ConfigurationSchemaMetadataExtractorInterface $schemaMetadataExtractor,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function loadSettingsForTab(string $featureKey, string $tabKey, string $scope, ?string $scopeIdentifier = null): array
    {
        $allSettings = $this->configurationFacade->getAllConfigurationSettings();

        $tabSettings = array_filter(
            $allSettings,
            fn ($setting) => $setting->getFeatureKey() === $featureKey
                && $setting->getTabKey() === $tabKey
                && $this->isSettingAvailableForScope($setting, $scope),
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

        $groupMetadata = $this->schemaMetadataExtractor->extractGroupMetadata($featureKey, $tabKey);
        $settingOverrides = $this->schemaMetadataExtractor->extractSettingOverrides($featureKey, $tabKey);
        $groups = [];

        foreach ($tabSettings as $setting) {
            $key = $setting->getKeyOrFail();
            $groupKey = $setting->getGroupKeyOrFail();

            if (!isset($groupMetadata[$groupKey])) {
                continue;
            }

            if (!isset($groups[$groupKey])) {
                $metadata = $groupMetadata[$groupKey];

                $groups[$groupKey] = [
                    static::NODE_KEY_KEY => $groupKey,
                    static::NODE_KEY_NAME => $metadata[static::NODE_KEY_NAME] ?? $this->formatKey($groupKey),
                    static::NODE_KEY_DESCRIPTION => $metadata[static::NODE_KEY_DESCRIPTION] ?? null,
                    static::NODE_KEY_ORDER => $metadata[static::NODE_KEY_ORDER] ?? 0,
                    static::NODE_KEY_SETTINGS => [],
                    static::NODE_KEY_STATUS => $metadata[static::NODE_KEY_STATUS] ?? null,
                ];
            }

            $hasCustomValue = isset($directValues[$key]);
            $currentValue = $directValues[$key] ?? null;

            // Fall back to the schema default when no DB value exists in any scope level.
            $inheritedValue = $hasCustomValue ? null : ($inheritedValues[$key] ?? $setting->getDefaultValue());

            $currentValueFormatted = $currentValue;
            $inheritedValueFormatted = $inheritedValue;

            if ($setting->getType() === ConfigurationSchemaConstants::VALUE_TYPE_MULTISELECT) {
                $currentValueFormatted = $this->parseJsonValue($currentValue);
                $inheritedValueFormatted = $this->parseJsonValue($inheritedValue);
            }

            $pendingSync = $this->isPendingSync($setting, $currentValue, $storageData);

            $settingData = [
                static::NODE_KEY_KEY => $key,
                static::NODE_KEY_FEATURE_KEY => $setting->getFeatureKey(),
                static::NODE_KEY_TAB_KEY => $setting->getTabKey(),
                static::NODE_KEY_NAME => $setting->getName(),
                static::NODE_KEY_DESCRIPTION => $setting->getDescription(),
                static::NODE_KEY_HELP_TEXT => $setting->getHelpText(),
                static::NODE_KEY_PLACEHOLDER => $setting->getPlaceholder(),
                static::NODE_KEY_NOTE => $setting->getNote(),
                static::NODE_KEY_TEMPLATE => $setting->getTemplate(),
                static::NODE_KEY_TYPE => $setting->getType(),
                static::NODE_KEY_DEFAULT_VALUE => $setting->getDefaultValue(),
                static::NODE_KEY_CURRENT_VALUE => $currentValue,
                static::NODE_KEY_CURRENT_VALUE_PARSED => $currentValueFormatted,
                static::NODE_KEY_INHERITED_VALUE => $inheritedValue,
                static::NODE_KEY_INHERITED_VALUE_PARSED => $inheritedValueFormatted,
                static::NODE_KEY_HAS_CUSTOM_VALUE => $hasCustomValue,
                static::NODE_KEY_OPTIONS => $setting->getOptions() ?: [],
                static::NODE_KEY_CONSTRAINTS => $setting->getConstraints() ?: [],
                static::NODE_KEY_DEPENDENCIES => $setting->getDependencies() ?: [],
                static::NODE_KEY_FILE_UPLOAD => $setting->getFileUpload() ?: [],
                ConfigurationSchemaConstants::SCHEMA_KEY_DATA_OBJECT => $setting->getDataObject(),
                static::NODE_KEY_IS_SECRET => $setting->getIsSecret() ?? false,
                static::NODE_KEY_IS_STOREFRONT => $setting->getIsStorefront() ?? false,
                static::NODE_KEY_PENDING_SYNC => $pendingSync,
                static::NODE_KEY_ORDER => $setting->getOrder() ?? 0,
                static::NODE_KEY_STATUS => $setting->getStatus(),
                static::NODE_KEY_IS_OVERRIDDEN => ($settingOverrides[$key] ?? []) !== [],
                static::NODE_KEY_OVERRIDE_INFO => $settingOverrides[$key] ?? [],
            ];

            $groups[$groupKey][static::NODE_KEY_SETTINGS][] = $this->dataObjectResolver->resolve($settingData);
        }

        foreach ($groups as &$group) {
            usort($group[static::NODE_KEY_SETTINGS], static fn (array $a, array $b) => $a[static::NODE_KEY_ORDER] <=> $b[static::NODE_KEY_ORDER]);
        }

        usort($groups, static fn (array $a, array $b) => $a[static::NODE_KEY_ORDER] <=> $b[static::NODE_KEY_ORDER]);

        return $groups;
    }

    protected function formatKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * @return array<string>
     */
    protected function parseJsonValue(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = $this->utilEncodingService->decodeJson($value, true);

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Determines whether a storefront-enabled setting has a DB value that differs from
     * what is currently in Redis, meaning P&S has not yet propagated the change.
     *
     * @param array<string, string> $storageData
     */
    protected function isPendingSync(ConfigurationSettingTransfer $setting, ?string $currentDbValue, array $storageData): bool
    {
        if (!$setting->getIsStorefront() || $setting->getIsSecret()) {
            return false;
        }

        if ($currentDbValue === null) {
            return isset($storageData[$setting->getKey()]);
        }

        $storageValue = $storageData[$setting->getKey()] ?? null;

        return $storageValue !== $currentDbValue;
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
