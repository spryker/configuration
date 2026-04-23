<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Persistence;

use Orm\Zed\Configuration\Persistence\SpyConfigurationStorageQuery;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValueQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;

/**
 * @method \Spryker\Zed\Configuration\ConfigurationConfig getConfig()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface getRepository()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationEntityManagerInterface getEntityManager()
 */
class ConfigurationPersistenceFactory extends AbstractPersistenceFactory
{
    public function createSpyConfigurationValueQuery(): SpyConfigurationValueQuery
    {
        return SpyConfigurationValueQuery::create();
    }

    public function createSpyConfigurationStorageQuery(): SpyConfigurationStorageQuery
    {
        return SpyConfigurationStorageQuery::create();
    }
}
