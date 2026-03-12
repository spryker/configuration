<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Persistence;

use Generated\Shared\Transfer\ConfigurationValueTransfer;

interface ConfigurationRepositoryInterface
{
    public function findConfigurationValueByKeyAndScope(string $key, string $scope, ?string $scopeIdentifier = null): ?ConfigurationValueTransfer;

    /**
     * Returns saved values for the given keys at the exact (scope, scopeIdentifier) pair.
     * Result is keyed by settingKey for O(1) lookup.
     *
     * @param array<string> $keys
     * @param string $scope
     * @param string|null $scopeIdentifier
     *
     * @return array<string, \Generated\Shared\Transfer\ConfigurationValueTransfer>
     */
    public function findConfigurationValuesByKeysAndScope(array $keys, string $scope, ?string $scopeIdentifier = null): array;

    /**
     * Returns all saved values at the exact (scope, scopeIdentifier) pair.
     * Result is keyed by settingKey.
     *
     * @param string $scope
     * @param string|null $scopeIdentifier
     *
     * @return array<string, \Generated\Shared\Transfer\ConfigurationValueTransfer>
     */
    public function findAllConfigurationValuesByScope(string $scope, ?string $scopeIdentifier = null): array;
}
