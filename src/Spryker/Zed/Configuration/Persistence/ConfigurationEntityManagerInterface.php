<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Persistence;

use Generated\Shared\Transfer\ConfigurationValueTransfer;

interface ConfigurationEntityManagerInterface
{
    public function saveConfigurationValue(ConfigurationValueTransfer $configurationValueTransfer): ConfigurationValueTransfer;

    public function deleteConfigurationValue(string $key, string $scope, ?string $scopeIdentifier = null): void;

    /**
     * @param array<string, string> $data
     */
    public function saveConfigurationStorage(string $storageKey, array $data): void;

    public function deleteConfigurationStorage(string $storageKey): void;
}
