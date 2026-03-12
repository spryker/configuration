<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Loader;

interface ConfigurationSettingsLoaderInterface
{
    /**
     * Load all settings for a specific tab, organized by groups
     *
     * @param string $tabKey
     * @param string $scope
     * @param string|null $scopeIdentifier
     *
     * @return array<array<string, mixed>>
     */
    public function loadSettingsForTab(string $tabKey, string $scope, ?string $scopeIdentifier = null): array;
}
