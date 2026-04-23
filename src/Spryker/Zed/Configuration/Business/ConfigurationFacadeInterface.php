<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business;

use Generated\Shared\Transfer\ConfigurationFileUploadCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationFileUploadCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationSettingValueCollectionTransfer;
use Generated\Shared\Transfer\ConfigurationSettingValuesCriteriaTransfer;
use Generated\Shared\Transfer\ConfigurationSyncResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Generated\Shared\Transfer\DataImporterConfigurationTransfer;
use Generated\Shared\Transfer\DataImporterReportTransfer;

interface ConfigurationFacadeInterface
{
    /**
     * Specification:
     * - Requires `ConfigurationValueRequestTransfer.key` to be set.
     * - Executes `ConfigurationValueRequestExpanderPluginInterface` plugin stack to enrich scope context.
     * - Looks up the setting definition from the merged configuration schema.
     * - Resolves the value by walking the scope hierarchy from most specific to global.
     * - Decrypts the value when the setting is marked as secret.
     * - Casts the raw value to the native PHP type defined by the setting type (string, integer, float, boolean, json).
     * - Returns the schema-defined default value when no stored value exists at any scope level.
     * - Returns `null` when the setting key is not found in the schema.
     *
     * @api
     */
    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed;

    /**
     * Specification:
     * - Requires `ConfigurationValueRequestTransfer.key` to be set (used as prefix).
     * - Executes `ConfigurationValueRequestExpanderPluginInterface` plugin stack once to enrich scope context.
     * - Looks up all setting definitions from the merged configuration schema whose keys share the prefix.
     * - Resolves each value by walking the scope hierarchy from most specific to global.
     * - Casts each raw value to the native PHP type defined by the setting schema.
     * - Returns schema-defined defaults when no stored value exists at any scope level.
     * - Returns an empty array when no settings match the given prefix.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ConfigurationValueRequestTransfer $configurationValueRequestTransfer
     *
     * @return array<string, mixed>
     */
    public function getConfigurationValues(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): array;

    /**
     * Specification:
     * - Discovers YAML configuration schema files from core module paths defined in `ConfigurationConfig::getCoreConfigSchemaPattens()`.
     * - Discovers YAML configuration schema files from project paths defined in `ConfigurationConfig::getProjectConfigSchemaPattens()`.
     * - Parses and validates each YAML file against the JSON Schema.
     * - Merges all schemas, with project-level settings overriding core at the setting key level.
     * - Writes the merged schema to the file path defined in `ConfigurationConfig::getMergedSchemaFilePath()`.
     * - Returns `ConfigurationSyncResponseTransfer` with `isSuccess`, `processedCount`, and `errorMessages`.
     *
     * @api
     */
    public function syncConfigurationSchemas(): ConfigurationSyncResponseTransfer;

    /**
     * Specification:
     * - Reads the merged configuration schema from the cached file.
     * - Maps all setting definitions to `ConfigurationSettingTransfer` objects.
     * - Each transfer includes the compound key (`feature:tab:group:setting`), type, default value, constraints, and scopes.
     * - Returns an array of `ConfigurationSettingTransfer` ordered by feature, tab, group, and setting order.
     *
     * @api
     *
     * @return array<\Generated\Shared\Transfer\ConfigurationSettingTransfer>
     */
    public function getAllConfigurationSettings(): array;

    /**
     * Specification:
     * - Executes `ConfigurationValuePreSavePluginInterface` plugin stack before validation and persistence.
     * - Validates each `ConfigurationValueTransfer` against schema-defined constraints (required, min, max, email, url, regex, choice, length, range).
     * - Encrypts values for settings marked as `secret` before persistence.
     * - Persists valid values to `spy_configuration_value`, skips invalid ones.
     * - Processes `ConfigurationValueDeletionTransfer` entries to delete scope-specific overrides (revert to default).
     * - Invalidates the in-memory cache for each saved or deleted key.
     * - Executes `ConfigurationValuePostSavePluginInterface` plugin stack after persistence.
     * - Returns `ConfigurationValueCollectionResponseTransfer` with `isSuccess`, `savedCount`, and per-key `ConfigurationErrorTransfer` errors.
     *
     * @api
     */
    public function saveConfigurationValues(
        ConfigurationValueCollectionRequestTransfer $requestTransfer,
    ): ConfigurationValueCollectionResponseTransfer;

    /**
     * Specification:
     * - Reads the merged schema from the cached file at `ConfigurationConfig::getMergedSchemaFilePath()`.
     * - Falls back to loading and merging all YAML schemas when the cached file does not exist.
     * - Returns the merged schema as a nested associative array with features, tabs, groups, and settings.
     *
     * @api
     *
     * @return array<mixed>
     */
    public function getMergedConfigurationSchema(): array;

    /**
     * Specification:
     * - Requires `ConfigurationSettingValuesCriteriaTransfer.settingKeys` to be set.
     * - Fetches saved values for all setting keys at the exact (`scope`, `scopeIdentifier`) pair in a single bulk query.
     * - Resolves inherited values by walking up the scope hierarchy defined in `ConfigurationConfig::getScopeHierarchy()`.
     * - Performs one bulk query per scope level during inheritance resolution.
     * - Returns `ConfigurationSettingValueCollectionTransfer` with `directValues` and `inheritedValues` maps, both keyed by setting key.
     *
     * @api
     */
    public function getConfigurationSettingValues(ConfigurationSettingValuesCriteriaTransfer $criteria): ConfigurationSettingValueCollectionTransfer;

    /**
     * Specification:
     * - Iterates `ConfigurationScopeIdentifierProviderPluginInterface` plugin stack.
     * - Returns identifiers from the first plugin whose `getScope()` matches the given scope.
     * - Returns an empty array when no plugin handles the given scope.
     *
     * @api
     *
     * @return array<string>
     */
    public function getScopeIdentifiers(string $scope): array;

    /**
     * Specification:
     * - Reads configuration values from CSV data source defined in `ConfigurationConfig::getConfigurationValueDataImporterConfiguration()`.
     * - Skips rows where the setting is marked as secret in the schema, logging a warning.
     * - Validates each row's setting_key exists in the merged configuration schema.
     * - Validates each row's scope is in `ConfigurationConfig::getAvailableScopes()`.
     * - Delegates persistence to `ConfigurationValueWriter::saveConfigurationValues()` which handles validation, audit logging, P&S events, and cache invalidation.
     * - Returns `DataImporterReportTransfer` with import statistics.
     *
     * @api
     */
    public function importConfigurationValues(
        ?DataImporterConfigurationTransfer $dataImporterConfigurationTransfer = null,
    ): DataImporterReportTransfer;

    /**
     * Specification:
     * - Searches the merged configuration schema for features, tabs, groups, and settings matching the given term.
     * - Translates names and descriptions to the current Backoffice locale before matching.
     * - Filters settings by scope availability before matching.
     * - Matches against translated name and description at feature, tab, and group levels.
     * - Matches against translated name, description, and raw key at setting level.
     * - A tab is included if it or any of its scope-available descendant groups/settings match.
     * - A feature is included if it or any of its descendant tabs match.
     * - Returns an associative array keyed by feature key, with values being arrays of matching tab keys.
     *
     * @api
     *
     * @return array<string, array<string>>
     */
    public function searchConfigurationSchema(string $term, string $scope): array;

    /**
     * Specification:
     * - Persists configuration file uploads.
     * - Requires `ConfigurationFileUploadTransfer.fileManagerData` to be set for each item.
     * - Saves each file upload individually and resolves its public URL.
     * - Adds an `ErrorTransfer` to the response for each file that cannot be saved.
     * - Stops processing after the first error if `ConfigurationFileUploadCollectionRequestTransfer.isTransactional` is set to `true`.
     * - Returns a `ConfigurationFileUploadCollectionResponseTransfer` containing the updated file transfers and any errors that occurred during processing.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ConfigurationFileUploadCollectionRequestTransfer $configurationFileUploadCollectionRequestTransfer
     *
     * @return \Generated\Shared\Transfer\ConfigurationFileUploadCollectionResponseTransfer
     */
    public function createConfigurationFileUploadCollection(
        ConfigurationFileUploadCollectionRequestTransfer $configurationFileUploadCollectionRequestTransfer,
    ): ConfigurationFileUploadCollectionResponseTransfer;
}
