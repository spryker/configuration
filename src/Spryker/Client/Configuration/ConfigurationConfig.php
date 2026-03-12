<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Configuration;

use Spryker\Client\Kernel\AbstractBundleConfig;
use Spryker\Shared\Configuration\ConfigurationConfig as SharedConfigurationConfig;

/**
 * @api
 *
 * @method \Spryker\Shared\Configuration\ConfigurationConfig getSharedConfig()
 */
class ConfigurationConfig extends AbstractBundleConfig
{
    public function getSharedModuleConfig(): SharedConfigurationConfig
    {
        return $this->getSharedConfig();
    }
}
