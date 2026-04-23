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
    protected array $runtimeCache = [];

    public function invalidate(string $key, string $scope, ?string $scopeIdentifier = null): void
    {
        $cacheKey = $this->buildCacheKey($key, $scope, $scopeIdentifier);

        unset($this->runtimeCache[$cacheKey]);
    }

    public function clearAll(): void
    {
        $this->runtimeCache = [];
    }

    public function get(string $key, string $scope, ?string $scopeIdentifier = null): ?string
    {
        $cacheKey = $this->buildCacheKey($key, $scope, $scopeIdentifier);

        return $this->runtimeCache[$cacheKey] ?? null;
    }

    public function set(string $key, string $scope, ?string $scopeIdentifier, string $value): void
    {
        $cacheKey = $this->buildCacheKey($key, $scope, $scopeIdentifier);

        $this->runtimeCache[$cacheKey] = $value;
    }

    protected function buildCacheKey(string $key, string $scope, ?string $scopeIdentifier): string
    {
        $parts = [ConfigurationConstants::CACHE_KEY_PREFIX, $key, $scope];

        if ($scopeIdentifier !== null) {
            $parts[] = $scopeIdentifier;
        }

        return implode(ConfigurationConstants::STORAGE_KEY_SEPARATOR, $parts);
    }
}
