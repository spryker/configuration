<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Sync;

use Generated\Shared\Transfer\ConfigurationSyncResponseTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Shared\Configuration\Schema\SchemaParserInterface;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaSettingsMapperInterface;
use Spryker\Zed\Configuration\Business\Search\ConfigurationUsageScannerInterface;
use Spryker\Zed\Configuration\Business\Validator\ConfigurationSchemaKeyValidatorInterface;
use Spryker\Zed\Configuration\ConfigurationConfig;

class ConfigurationSchemaSync implements ConfigurationSchemaSyncInterface
{
    public function __construct(
        protected ConfigurationSchemaLoaderInterface $schemaLoader,
        protected ConfigurationSchemaMergerInterface $schemaMerger,
        protected SchemaParserInterface $schemaParser,
        protected ConfigurationSchemaSettingsMapperInterface $settingsMapper,
        protected ConfigurationConfig $config,
        protected ConfigurationUsageScannerInterface $usageScanner,
        protected UtilEncodingServiceInterface $utilEncodingService,
        protected ConfigurationSchemaKeyValidatorInterface $schemaKeyValidator,
    ) {
    }

    public function sync(): ConfigurationSyncResponseTransfer
    {
        $configurationSyncResponseTransfer = (new ConfigurationSyncResponseTransfer())
            ->setIsSuccess(false)
            ->setProcessedCount(0)
            ->setErrorMessages([]);

        $mergedSchema = $this->schemaMerger->merge(
            $this->schemaLoader->loadCoreSchemas(),
            $this->schemaLoader->loadProjectSchemas(),
        );

        if ($mergedSchema === []) {
            return $configurationSyncResponseTransfer
                ->setIsSuccess(true)
                ->addErrorMessage('No configuration schemas found');
        }

        if (!$this->schemaParser->validate($mergedSchema)) {
            $configurationSyncResponseTransfer->addErrorMessage('Schema validation failed:');

            foreach ($this->schemaParser->getValidationErrors() as $error) {
                $configurationSyncResponseTransfer->addErrorMessage(sprintf('  - %s', $error));
            }

            return $configurationSyncResponseTransfer;
        }

        $availableScopes = $this->config->getAvailableScopes();
        $mergedSchema = $this->filterInvalidScopes($mergedSchema, $availableScopes);

        $keyErrors = $this->schemaKeyValidator->validate($mergedSchema);

        if ($keyErrors !== []) {
            foreach ($keyErrors as $keyError) {
                $configurationSyncResponseTransfer->addErrorMessage($keyError);
            }

            return $configurationSyncResponseTransfer;
        }

        $overridesByKey = $this->usageScanner->scan();
        $mergedSchema = $this->injectOverrides($mergedSchema, $overridesByKey);

        $this->writeSchemaToFile($mergedSchema);
        $this->writeSettingsMapToFile($mergedSchema, $overridesByKey);

        $processedCount = count($this->settingsMapper->mapSchemaToSettingTransfers($mergedSchema));

        $configurationSyncResponseTransfer
            ->setIsSuccess(true)
            ->setProcessedCount($processedCount);

        $this->addOverrideWarnings($configurationSyncResponseTransfer, $overridesByKey);

        return $configurationSyncResponseTransfer;
    }

    /**
     * @param array<mixed> $schema
     */
    protected function writeSchemaToFile(array $schema): void
    {
        $filePath = $this->config->getMergedSchemaFilePath();
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, sprintf("<?php\n\nreturn %s;\n", var_export($schema, true)));
    }

    /**
     * @param array<mixed> $schema
     * @param array<string, array<int, array<string, string>>> $overridesByKey
     */
    protected function writeSettingsMapToFile(array $schema, array $overridesByKey = []): void
    {
        $filePath = $this->config->getSettingsMapFilePath();
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $settingsMap = $this->buildSettingsMap($schema, $overridesByKey);

        file_put_contents($filePath, sprintf("<?php\n\nreturn %s;\n", var_export($settingsMap, true)));
    }

    /**
     * @param array<mixed> $schema
     * @param array<string, array<int, array<string, string>>> $overridesByKey
     *
     * @return array<string, array<string, mixed>>
     */
    protected function buildSettingsMap(array $schema, array $overridesByKey = []): array
    {
        $map = [];

        if (!isset($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES]) || !is_array($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES])) {
            return $map;
        }

        foreach ($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] as $feature) {
            if ($this->isDisabled($feature)) {
                continue;
            }

            $this->buildSettingsMapForFeature($feature, $map, $overridesByKey);
        }

        return $map;
    }

    /**
     * @param array<mixed> $feature
     * @param array<string, array<string, mixed>> $map
     * @param array<string, array<int, array<string, string>>> $overridesByKey
     */
    protected function buildSettingsMapForFeature(array $feature, array &$map, array $overridesByKey): void
    {
        if (!isset($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS]) || !is_array($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS])) {
            return;
        }

        $featureKey = $feature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY];

        foreach ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] as $tab) {
            if ($this->isDisabled($tab)) {
                continue;
            }

            $this->buildSettingsMapForTab($featureKey, $tab, $map, $overridesByKey);
        }
    }

    /**
     * @param array<mixed> $tab
     * @param array<string, array<string, mixed>> $map
     * @param array<string, array<int, array<string, string>>> $overridesByKey
     */
    protected function buildSettingsMapForTab(string $featureKey, array $tab, array &$map, array $overridesByKey): void
    {
        if (!isset($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS]) || !is_array($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS])) {
            return;
        }

        $tabKey = $tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY];

        foreach ($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] as $group) {
            if ($this->isDisabled($group)) {
                continue;
            }

            $this->buildSettingsMapForGroup($featureKey, $tabKey, $group, $map, $overridesByKey);
        }
    }

    /**
     * @param array<mixed> $group
     * @param array<string, array<string, mixed>> $map
     * @param array<string, array<int, array<string, string>>> $overridesByKey
     */
    protected function buildSettingsMapForGroup(string $featureKey, string $tabKey, array $group, array &$map, array $overridesByKey): void
    {
        if (!isset($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS]) || !is_array($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS])) {
            return;
        }

        $groupKey = $group[ConfigurationSchemaConstants::SCHEMA_KEY_KEY];

        foreach ($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] as $setting) {
            if ($this->isDisabled($setting)) {
                continue;
            }

            $compoundKey = sprintf('%s:%s:%s:%s', $featureKey, $tabKey, $groupKey, $setting[ConfigurationSchemaConstants::SCHEMA_KEY_KEY]);
            $effectiveKey = (string)($setting[ConfigurationSchemaConstants::SCHEMA_KEY_STATIC_KEY] ?? $compoundKey);
            $defaultValue = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_DEFAULT_VALUE] ?? null;

            if ($defaultValue !== null && !is_string($defaultValue)) {
                $defaultValue = (string)$this->utilEncodingService->encodeJson($defaultValue);
            }

            $map[$effectiveKey] = [
                ConfigurationSchemaConstants::SCHEMA_KEY_TYPE => $setting[ConfigurationSchemaConstants::SCHEMA_KEY_TYPE],
                ConfigurationSchemaConstants::SCHEMA_KEY_DEFAULT_VALUE => $defaultValue,
                ConfigurationSchemaConstants::SCHEMA_KEY_SECRET => $setting[ConfigurationSchemaConstants::SCHEMA_KEY_SECRET] ?? false,
                ConfigurationSchemaConstants::SCHEMA_KEY_STOREFRONT => $setting[ConfigurationSchemaConstants::SCHEMA_KEY_STOREFRONT] ?? false,
                ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES => $setting[ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES] ?? [],
                ConfigurationSchemaConstants::SCHEMA_KEY_CONSTRAINTS => $setting[ConfigurationSchemaConstants::SCHEMA_KEY_CONSTRAINTS] ?? [],
                ConfigurationSchemaConstants::SCHEMA_KEY_SANITIZE_XSS => $setting[ConfigurationSchemaConstants::SCHEMA_KEY_SANITIZE_XSS] ?? [],
                ConfigurationSchemaConstants::SCHEMA_KEY_DEPENDENCIES => $setting[ConfigurationSchemaConstants::SCHEMA_KEY_DEPENDENCIES] ?? [],
                ConfigurationSchemaConstants::SCHEMA_KEY_OVERRIDES => $overridesByKey[$compoundKey] ?? [],
            ];
        }
    }

    /**
     * @param array<mixed> $schema
     * @param array<string, array<int, array<string, string>>> $overridesByKey
     *
     * @return array<mixed>
     */
    protected function injectOverrides(array $schema, array $overridesByKey): array
    {
        if ($overridesByKey === [] || !isset($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES])) {
            return $schema;
        }

        foreach ($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] as &$feature) {
            $this->injectOverridesIntoFeature($feature, $overridesByKey);
        }

        unset($feature);

        return $schema;
    }

    /**
     * @param array<mixed> $feature
     * @param array<string, array<int, array<string, string>>> $overridesByKey
     */
    protected function injectOverridesIntoFeature(array &$feature, array $overridesByKey): void
    {
        if (!isset($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS])) {
            return;
        }

        foreach ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] as &$tab) {
            $this->injectOverridesIntoTab($feature, $tab, $overridesByKey);
        }

        unset($tab);
    }

    /**
     * @param array<mixed> $feature
     * @param array<mixed> $tab
     * @param array<string, array<int, array<string, string>>> $overridesByKey
     */
    protected function injectOverridesIntoTab(array $feature, array &$tab, array $overridesByKey): void
    {
        if (!isset($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS])) {
            return;
        }

        foreach ($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] as &$group) {
            if (!isset($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS])) {
                continue;
            }

            foreach ($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] as &$setting) {
                $compoundKey = sprintf(
                    '%s:%s:%s:%s',
                    $feature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY],
                    $tab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY],
                    $group[ConfigurationSchemaConstants::SCHEMA_KEY_KEY],
                    $setting[ConfigurationSchemaConstants::SCHEMA_KEY_KEY],
                );

                $setting[ConfigurationSchemaConstants::SCHEMA_KEY_OVERRIDES] = $overridesByKey[$compoundKey] ?? [];
            }

            unset($setting);
        }

        unset($group);
    }

    /**
     * @param array<string, array<int, array<string, string>>> $overridesByKey
     */
    protected function addOverrideWarnings(
        ConfigurationSyncResponseTransfer $responseTransfer,
        array $overridesByKey,
    ): void {
        foreach ($overridesByKey as $configKey => $records) {
            foreach ($records as $record) {
                $responseTransfer->addWarningMessage(
                    sprintf(
                        'Configuration bypass: %s — %s::%s overridden by %s::%s (does not use getModuleConfig)',
                        $configKey,
                        $record[ConfigurationSchemaConstants::OVERRIDE_KEY_CORE_CLASS],
                        $record[ConfigurationSchemaConstants::OVERRIDE_KEY_CORE_METHOD],
                        $record[ConfigurationSchemaConstants::OVERRIDE_KEY_PROJECT_CLASS],
                        $record[ConfigurationSchemaConstants::OVERRIDE_KEY_PROJECT_METHOD],
                    ),
                );
            }
        }
    }

    /**
     * @param array<mixed> $schema
     * @param array<string> $availableScopes
     *
     * @return array<mixed>
     */
    protected function filterInvalidScopes(array $schema, array $availableScopes): array
    {
        if (!isset($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES])) {
            return $schema;
        }

        foreach ($schema[ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES] as &$feature) {
            $this->filterInvalidScopesForFeature($feature, $availableScopes);
        }

        unset($feature);

        return $schema;
    }

    /**
     * @param array<mixed> $feature
     * @param array<string> $availableScopes
     */
    protected function filterInvalidScopesForFeature(array &$feature, array $availableScopes): void
    {
        if (!isset($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS])) {
            return;
        }

        foreach ($feature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS] as &$tab) {
            $this->filterInvalidScopesForTab($tab, $availableScopes);
        }

        unset($tab);
    }

    /**
     * @param array<mixed> $tab
     * @param array<string> $availableScopes
     */
    protected function filterInvalidScopesForTab(array &$tab, array $availableScopes): void
    {
        if (!isset($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS])) {
            return;
        }

        foreach ($tab[ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS] as &$group) {
            $groupScopes = array_values(array_intersect($group[ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES] ?? [], $availableScopes));
            $group[ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES] = $groupScopes;

            if (!isset($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS])) {
                continue;
            }

            foreach ($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] as &$setting) {
                $setting[ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES] = array_values(
                    array_intersect($setting[ConfigurationSchemaConstants::SCHEMA_KEY_SCOPES] ?? [], $availableScopes, $groupScopes),
                );
            }

            unset($setting);
        }

        unset($group);
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function isDisabled(array $item): bool
    {
        return ($item[ConfigurationSchemaConstants::SCHEMA_KEY_ENABLED] ?? true) === false;
    }
}
