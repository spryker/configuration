<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business;

use Generated\Shared\Transfer\ConfigurationSettingValueCollectionTransfer;
use Generated\Shared\Transfer\ConfigurationSettingValuesCriteriaTransfer;
use Generated\Shared\Transfer\ConfigurationSyncResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;

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
     *
     * @param \Generated\Shared\Transfer\ConfigurationValueRequestTransfer $configurationValueRequestTransfer
     *
     * @return mixed
     */
    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed;

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
     *
     * @return \Generated\Shared\Transfer\ConfigurationSyncResponseTransfer
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
     *
     * @param \Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer $requestTransfer
     *
     * @return \Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer
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
     *
     * @param \Generated\Shared\Transfer\ConfigurationSettingValuesCriteriaTransfer $criteria
     *
     * @return \Generated\Shared\Transfer\ConfigurationSettingValueCollectionTransfer
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
     * @param string $scope
     *
     * @return array<string>
     */
    public function getScopeIdentifiers(string $scope): array;
}
