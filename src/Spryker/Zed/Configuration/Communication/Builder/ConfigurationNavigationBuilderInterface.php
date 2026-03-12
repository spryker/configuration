<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Builder;

use Spryker\Shared\Configuration\ConfigurationConstants;

interface ConfigurationNavigationBuilderInterface
{
    /**
     * Build navigation tree: features -> tabs
     *
     * @param string $scope
     *
     * @return array<array<string, mixed>>
     */
    public function buildNavigationTree(string $scope = ConfigurationConstants::SCOPE_GLOBAL): array;
}
