<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication;

use Spryker\Client\Configuration\ConfigurationClientInterface;
use Spryker\Zed\Acl\Business\AclFacadeInterface;
use Spryker\Zed\Configuration\Communication\Builder\ConfigurationNavigationBuilder;
use Spryker\Zed\Configuration\Communication\Builder\ConfigurationNavigationBuilderInterface;
use Spryker\Zed\Configuration\Communication\Loader\ConfigurationSettingsLoader;
use Spryker\Zed\Configuration\Communication\Loader\ConfigurationSettingsLoaderInterface;
use Spryker\Zed\Configuration\ConfigurationDependencyProvider;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;

/**
 * @method \Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface getFacade()
 * @method \Spryker\Zed\Configuration\ConfigurationConfig getConfig()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface getRepository()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationEntityManagerInterface getEntityManager()
 */
class ConfigurationCommunicationFactory extends AbstractCommunicationFactory
{
    public function createConfigurationNavigationBuilder(): ConfigurationNavigationBuilderInterface
    {
        return new ConfigurationNavigationBuilder(
            $this->getFacade(),
        );
    }

    public function createConfigurationSettingsLoader(): ConfigurationSettingsLoaderInterface
    {
        return new ConfigurationSettingsLoader(
            $this->getFacade(),
            $this->getConfigurationClient(),
        );
    }

    public function getConfigurationClient(): ConfigurationClientInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::CLIENT_CONFIGURATION);
    }

    public function getAclFacade(): AclFacadeInterface
    {
        return $this->getProvidedDependency(ConfigurationDependencyProvider::FACADE_ACL);
    }
}
