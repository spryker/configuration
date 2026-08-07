<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Cache;

use Spryker\Shared\Configuration\ConfigurationConstants;

class ConfigurationCacheManager implements ConfigurationCacheManagerInterface
{
    /**
     * @var array<string, string>
     */
    protected static array $runtimeCache = [];

    /**
     * @var array<string, bool>
     */
    protected static array $warmScopes = [];

    public function invalidate(string $key, string $scope, ?string $scopeIdentifier = null): void
    {
        $cacheKey = $this->buildCacheKey($key, $scope, $scopeIdentifier);

        unset(static::$runtimeCache[$cacheKey]);

        unset(static::$warmScopes[$this->buildScopeCacheKey($scope, $scopeIdentifier)]);
    }

    public function clearAll(): void
    {
        static::$runtimeCache = [];
        static::$warmScopes = [];
    }

    public function get(string $key, string $scope, ?string $scopeIdentifier = null): ?string
    {
        $cacheKey = $this->buildCacheKey($key, $scope, $scopeIdentifier);

        return static::$runtimeCache[$cacheKey] ?? null;
    }

    public function set(string $key, string $scope, ?string $scopeIdentifier, string $value): void
    {
        $cacheKey = $this->buildCacheKey($key, $scope, $scopeIdentifier);

        static::$runtimeCache[$cacheKey] = $value;
    }

    public function isScopeWarm(string $scope, ?string $scopeIdentifier = null): bool
    {
        return isset(static::$warmScopes[$this->buildScopeCacheKey($scope, $scopeIdentifier)]);
    }

    /**
     * @param array<string, string> $rawValues
     */
    public function warmUpScope(string $scope, ?string $scopeIdentifier, array $rawValues): void
    {
        foreach ($rawValues as $settingKey => $rawValue) {
            $this->set($settingKey, $scope, $scopeIdentifier, $rawValue);
        }

        static::$warmScopes[$this->buildScopeCacheKey($scope, $scopeIdentifier)] = true;
    }

    protected function buildCacheKey(string $key, string $scope, ?string $scopeIdentifier): string
    {
        $parts = [ConfigurationConstants::CACHE_KEY_PREFIX, $key, $scope];

        if ($scopeIdentifier !== null) {
            $parts[] = $scopeIdentifier;
        }

        return implode(ConfigurationConstants::STORAGE_KEY_SEPARATOR, $parts);
    }

    protected function buildScopeCacheKey(string $scope, ?string $scopeIdentifier): string
    {
        $parts = [ConfigurationConstants::CACHE_KEY_PREFIX, $scope];

        if ($scopeIdentifier !== null) {
            $parts[] = $scopeIdentifier;
        }

        return implode(ConfigurationConstants::STORAGE_KEY_SEPARATOR, $parts);
    }
}
