<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Persistence;

use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Orm\Zed\Configuration\Persistence\SpyConfigurationStorageQuery;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValue;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValueQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationPersistenceFactory getFactory()
 */
class ConfigurationEntityManager extends AbstractEntityManager implements ConfigurationEntityManagerInterface
{
    public function saveConfigurationValue(ConfigurationValueTransfer $configurationValueTransfer): ConfigurationValueTransfer
    {
        $configurationValueTransfer->requireSettingKey()
            ->requireScope()
            ->requireValue();

        $settingKey = $configurationValueTransfer->getSettingKeyOrFail();
        $scope = $configurationValueTransfer->getScopeOrFail();
        $value = $configurationValueTransfer->getValueOrFail();

        $configurationValueEntity = $this->getConfigurationValueEntity(
            $settingKey,
            $scope,
            $configurationValueTransfer->getScopeIdentifier(),
        );

        $configurationValueEntity->setSettingKey($settingKey);
        $configurationValueEntity->setScope($scope);
        $configurationValueEntity->setScopeIdentifier($configurationValueTransfer->getScopeIdentifier());
        $configurationValueEntity->setValue($value);
        $configurationValueEntity->save();

        return $configurationValueTransfer->setIdConfigurationValue($configurationValueEntity->getIdConfigurationValue());
    }

    public function deleteConfigurationValue(string $key, string $scope, ?string $scopeIdentifier = null): void
    {
        $valueQuery = $this->getConfigurationValueQuery()
            ->filterBySettingKey($key)
            ->filterByScope($scope);

        if ($scopeIdentifier !== null) {
            $valueQuery->filterByScopeIdentifier($scopeIdentifier);
        } else {
            $valueQuery->filterByScopeIdentifier(null, Criteria::ISNULL);
        }

        /** @var \Propel\Runtime\Collection\Collection|\Propel\Runtime\Collection\ObjectCollection $configurationValueEntities */
        $configurationValueEntities = $valueQuery->find();
        foreach ($configurationValueEntities as $configurationValueEntity) {
            $configurationValueEntity->delete();
        }
    }

    /**
     * @param string $storageKey
     * @param array<string, string> $data
     *
     * @return void
     */
    public function saveConfigurationStorage(string $storageKey, array $data): void
    {
        $storageEntity = $this->getConfigurationStorageQuery()
            ->filterByScope($storageKey)
            ->findOneOrCreate();

        $storageEntity->setKey($storageKey);
        $storageEntity->setData($data);
        $storageEntity->save();
    }

    public function deleteConfigurationStorage(string $storageKey): void
    {
        /** @var \Propel\Runtime\Collection\Collection|\Propel\Runtime\Collection\ObjectCollection $configurationStorageEntities */
        $configurationStorageEntities = $this->getConfigurationStorageQuery()
            ->filterByScope($storageKey)
            ->find();
        foreach ($configurationStorageEntities as $configurationStorageEntity) {
            $configurationStorageEntity->delete();
        }
    }

    protected function getConfigurationValueEntity(string $settingKey, string $scope, ?string $scopeIdentifier): SpyConfigurationValue
    {
        $valueQuery = $this->getConfigurationValueQuery()
            ->filterBySettingKey($settingKey)
            ->filterByScope($scope);

        if ($scopeIdentifier !== null) {
            $valueQuery->filterByScopeIdentifier($scopeIdentifier);
        } else {
            $valueQuery->filterByScopeIdentifier(null, Criteria::ISNULL);
        }

        /** @var \Orm\Zed\Configuration\Persistence\SpyConfigurationValue|null $valueEntity */
        $valueEntity = $valueQuery->findOne();

        if ($valueEntity) {
            return $valueEntity;
        }

        return new SpyConfigurationValue();
    }

    protected function getConfigurationValueQuery(): SpyConfigurationValueQuery
    {
        /** @var \Orm\Zed\Configuration\Persistence\SpyConfigurationValueQuery $configurationValueQuery */
        $configurationValueQuery = SpyConfigurationValueQuery::create();

        return $configurationValueQuery;
    }

    protected function getConfigurationStorageQuery(): SpyConfigurationStorageQuery
    {
        /** @var \Orm\Zed\Configuration\Persistence\SpyConfigurationStorageQuery $configurationStorageQuery */
        $configurationStorageQuery = SpyConfigurationStorageQuery::create();

        return $configurationStorageQuery;
    }
}
