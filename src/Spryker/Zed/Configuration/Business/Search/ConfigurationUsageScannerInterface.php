<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Search;

interface ConfigurationUsageScannerInterface
{
    /**
     * Scans core/shop/feature Config classes across Yves, Zed, Glue layers
     * for methods using getModuleConfig().
     * Resolves project-level overrides via BundleConfigResolver.
     * Returns overrides keyed by configuration key where the project class
     * bypasses getModuleConfig() in the overriding method.
     *
     * @return array<string, array<int, array{coreClass: string, coreMethod: string, projectClass: string, projectMethod: string}>>
     */
    public function scan(): array;
}
