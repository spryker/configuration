<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Configuration\Reader;

use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;

interface ConfigurationStorageReaderInterface
{
    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed;

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationValues(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): array;

    /**
     * Reads all raw configuration values from storage for the given scope.
     *
     * @param string $scope
     * @param string|null $scopeIdentifier
     *
     * @return array<string, string>
     */
    public function getStorageDataForScope(string $scope, ?string $scopeIdentifier = null): array;
}
