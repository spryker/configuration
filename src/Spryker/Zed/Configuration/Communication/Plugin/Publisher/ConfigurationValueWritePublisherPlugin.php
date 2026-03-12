<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Plugin\Publisher;

use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\PublisherExtension\Dependency\Plugin\PublisherPluginInterface;

/**
 * @method \Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface getFacade()
 * @method \Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory getFactory()
 * @method \Spryker\Zed\Configuration\Business\ConfigurationBusinessFactory getBusinessFactory()
 * @method \Spryker\Zed\Configuration\ConfigurationConfig getConfig()
 */
class ConfigurationValueWritePublisherPlugin extends AbstractPlugin implements PublisherPluginInterface
{
    /**
     * {@inheritDoc}
     * - Handles `spy_configuration_value` create, update, and delete entity events.
     * - Only publishes values for settings marked with `storefront: true` and `secret: false`.
     * - Writes the affected configuration values to `spy_configuration_storage` for Publish and Synchronize.
     *
     * @api
     *
     * @param list<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     * @param string $eventName
     *
     * @return void
     */
    public function handleBulk(array $eventEntityTransfers, $eventName): void
    {
        $this->getBusinessFactory()->createConfigurationStorageWriter()->writeByConfigurationValueEvents($eventEntityTransfers);
    }

    /**
     * {@inheritDoc}
     * - Returns entity events this plugin subscribes to: `spy_configuration_value` create, update, and delete.
     *
     * @api
     *
     * @return array<string>
     */
    public function getSubscribedEvents(): array
    {
        return [
            ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_CREATE,
            ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_UPDATE,
            ConfigurationConstants::ENTITY_SPY_CONFIGURATION_VALUE_DELETE,
        ];
    }
}
