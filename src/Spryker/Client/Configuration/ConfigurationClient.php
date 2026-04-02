<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Configuration;

use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \Spryker\Client\Configuration\ConfigurationFactory getFactory()
 */
class ConfigurationClient extends AbstractClient implements ConfigurationClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed
    {
        return $this->getFactory()
            ->createConfigurationStorageReader()
            ->getConfigurationValue($configurationValueRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getConfigurationValues(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): array
    {
        return $this->getFactory()
            ->createConfigurationStorageReader()
            ->getConfigurationValues($configurationValueRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getStorageDataForScope(string $scope, ?string $scopeIdentifier = null): array
    {
        return $this->getFactory()
            ->createConfigurationStorageReader()
            ->getStorageDataForScope($scope, $scopeIdentifier);
    }
}
