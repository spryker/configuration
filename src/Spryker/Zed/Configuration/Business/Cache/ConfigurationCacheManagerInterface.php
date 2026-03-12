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
}
