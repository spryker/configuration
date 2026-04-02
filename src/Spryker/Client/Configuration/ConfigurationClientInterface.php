<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Configuration;

use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;

interface ConfigurationClientInterface
{
    /**
     * Specification:
     * - Requires `ConfigurationValueRequestTransfer.key` to be set.
     * - Executes `ConfigurationValueRequestExpanderPluginInterface` plugin stack to enrich scope context.
     * - Looks up the setting definition from the merged configuration schema cached file.
     * - Reads the raw value from key-value storage (Redis) using the synchronization key pattern.
     * - Resolves the value by walking the scope hierarchy from most specific to global.
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
     * - Requires `ConfigurationValueRequestTransfer.key` to be set (used as prefix).
     * - Executes `ConfigurationValueRequestExpanderPluginInterface` plugin stack once to enrich scope context.
     * - Looks up all setting definitions from the merged configuration schema whose keys share the prefix.
     * - Resolves each value by walking the scope hierarchy from most specific to global.
     * - Casts each raw value to the native PHP type defined by the setting type (string, integer, float, boolean, json).
     * - Returns the schema-defined default value when no stored value exists at any scope level.
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
     * - Reads all raw configuration values from key-value storage (Redis) for the given scope.
     * - Uses the synchronization key pattern to generate the storage key.
     * - Returns a map of `settingKey => rawValue` pairs.
     * - Returns an empty array when no data exists for the given scope.
     *
     * @api
     *
     * @param string $scope
     * @param string|null $scopeIdentifier
     *
     * @return array<string, string>
     */
    public function getStorageDataForScope(string $scope, ?string $scopeIdentifier = null): array;
}
