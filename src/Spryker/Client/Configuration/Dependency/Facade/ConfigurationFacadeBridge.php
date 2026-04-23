<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Configuration\Dependency\Facade;

use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Spryker\Client\Configuration\Reader\ConfigurationReaderInterface;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;

/**
 * @SuppressWarnings(PHPMD)
 */
class ConfigurationFacadeBridge implements ConfigurationReaderInterface
{
    protected ConfigurationFacadeInterface|null $configurationFacade;

    /**
     * @param \Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface|null $configurationFacade
     */
    public function __construct($configurationFacade)
    {
        $this->configurationFacade = $configurationFacade;
    }

    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed
    {
        if ($this->configurationFacade === null) {
            return null;
        }

        return $this->configurationFacade->getConfigurationValue($configurationValueRequestTransfer);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurationValues(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): array
    {
        if ($this->configurationFacade === null) {
            return [];
        }

        return $this->configurationFacade->getConfigurationValues($configurationValueRequestTransfer);
    }

    /**
     * {@inheritDoc}
     */
    public function getStorageDataForScope(string $scope, ?string $scopeIdentifier = null): array
    {
        return [];
    }
}
