<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Search;

interface ConfigurationOverrideCollectorInterface
{
    /**
     * Resolves the project-level override class for the given core Config class and appends override
     * records keyed by configuration key when the project method overrides the core method
     * without calling getModuleConfig(). Layer is one of Yves, Zed, Glue, Client.
     *
     * @param array<string, array<int, array{coreClass: string, coreMethod: string, projectClass: string, projectMethod: string}>> $overrides
     * @param array<string, array<string>> $methodsWithKeys
     */
    public function collectOverrides(array &$overrides, string $coreClassName, string $layer, array $methodsWithKeys): void;
}
