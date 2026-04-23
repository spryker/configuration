<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Configuration\Reader;

use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;

class ConfigurationReaderResolver implements ConfigurationReaderInterface
{
    public function __construct(
        protected ConfigurationReaderInterface $storageReader,
        protected ConfigurationReaderInterface $facadeReader,
        protected bool $isConfigurationServiceProvided,
    ) {
    }

    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed
    {
        return $this->resolveReader()
            ->getConfigurationValue($configurationValueRequestTransfer);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurationValues(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): array
    {
        return $this->resolveReader()
            ->getConfigurationValues($configurationValueRequestTransfer);
    }

    /**
     * {@inheritDoc}
     */
    public function getStorageDataForScope(string $scope, ?string $scopeIdentifier = null): array
    {
        return $this->storageReader
            ->getStorageDataForScope($scope, $scopeIdentifier);
    }

    protected function resolveReader(): ConfigurationReaderInterface
    {
        if ($this->isConfigurationServiceProvided) {
            return $this->facadeReader;
        }

        return $this->storageReader;
    }
}
