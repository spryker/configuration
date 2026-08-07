<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Cache;

interface ConfigurationCacheManagerInterface
{
    public function invalidate(string $key, string $scope, ?string $scopeIdentifier = null): void;

    public function clearAll(): void;

    public function get(string $key, string $scope, ?string $scopeIdentifier = null): ?string;

    public function set(string $key, string $scope, ?string $scopeIdentifier, string $value): void;

    /**
     * Tells whether the whole scope has already been loaded, which makes a cache miss for a single
     * key conclusive: the key has no stored value and needs no lookup of its own.
     */
    public function isScopeWarm(string $scope, ?string $scopeIdentifier = null): bool;

    /**
     * @param array<string, string> $rawValues All stored values of the scope, keyed by setting key.
     */
    public function warmUpScope(string $scope, ?string $scopeIdentifier, array $rawValues): void;
}
