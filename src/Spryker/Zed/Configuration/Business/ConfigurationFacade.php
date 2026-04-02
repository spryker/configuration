<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business;

use Generated\Shared\Transfer\ConfigurationFileUploadCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationFileUploadCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationSettingValueCollectionTransfer;
use Generated\Shared\Transfer\ConfigurationSettingValuesCriteriaTransfer;
use Generated\Shared\Transfer\ConfigurationSyncResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Spryker\Zed\Configuration\Business\ConfigurationBusinessFactory getFactory()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface getRepository()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationEntityManagerInterface getEntityManager()
 */
class ConfigurationFacade extends AbstractFacade implements ConfigurationFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed
    {
        return $this->getFactory()
            ->createConfigurationReader()
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
            ->createConfigurationReader()
            ->getConfigurationValues($configurationValueRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function syncConfigurationSchemas(): ConfigurationSyncResponseTransfer
    {
        return $this->getFactory()
            ->createConfigurationSchemaSync()
            ->sync();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAllConfigurationSettings(): array
    {
        return $this->getFactory()
            ->createConfigurationSchemaProvider()
            ->getAllSettingTransfers();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function saveConfigurationValues(
        ConfigurationValueCollectionRequestTransfer $requestTransfer,
    ): ConfigurationValueCollectionResponseTransfer {
        return $this->getFactory()
            ->createConfigurationValueWriter()
            ->saveConfigurationValues($requestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getMergedConfigurationSchema(): array
    {
        return $this->getFactory()
            ->createConfigurationSchemaProvider()
            ->getMergedSchema();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getConfigurationSettingValues(ConfigurationSettingValuesCriteriaTransfer $criteria): ConfigurationSettingValueCollectionTransfer
    {
        return $this->getFactory()
            ->createConfigurationValuesCollector()
            ->collectValues($criteria);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getScopeIdentifiers(string $scope): array
    {
        return $this->getFactory()
            ->createConfigurationScopeIdentifierResolver()
            ->getIdentifiersForScope($scope);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createConfigurationFileUploadCollection(
        ConfigurationFileUploadCollectionRequestTransfer $configurationFileUploadCollectionRequestTransfer,
    ): ConfigurationFileUploadCollectionResponseTransfer {
        return $this->getFactory()
            ->createConfigurationFileUploadCreator()
            ->createFileUploadCollection($configurationFileUploadCollectionRequestTransfer);
    }
}
