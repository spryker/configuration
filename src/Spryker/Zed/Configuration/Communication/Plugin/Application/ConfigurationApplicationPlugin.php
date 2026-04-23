<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Plugin\Application;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface getFacade()
 * @method \Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory getFactory()
 * @method \Spryker\Zed\Configuration\ConfigurationConfig getConfig()
 */
class ConfigurationApplicationPlugin extends AbstractPlugin implements ApplicationPluginInterface
{
    protected const string SERVICE_CONFIGURATION = 'configuration';

    public function provide(ContainerInterface $container): ContainerInterface
    {
        $container->set(static::SERVICE_CONFIGURATION, function () {
            return $this->getFacade();
        });

        return $container;
    }
}
