<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Persistence;

use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValue;
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

        $configurationValueEntity = $this->findOrCreateConfigurationValueEntity(
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
        $valueQuery = $this->getFactory()->createSpyConfigurationValueQuery()
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
     * @param array<string, string> $data
     */
    public function saveConfigurationStorage(string $storageKey, array $data): void
    {
        $storageEntity = $this->getFactory()->createSpyConfigurationStorageQuery()
            ->filterByScope($storageKey)
            ->findOneOrCreate();

        $storageEntity->setKey($storageKey);
        $storageEntity->setData($data);
        $storageEntity->save();
    }

    public function deleteConfigurationStorage(string $storageKey): void
    {
        /** @var \Propel\Runtime\Collection\Collection|\Propel\Runtime\Collection\ObjectCollection $configurationStorageEntities */
        $configurationStorageEntities = $this->getFactory()->createSpyConfigurationStorageQuery()
            ->filterByScope($storageKey)
            ->find();

        foreach ($configurationStorageEntities as $configurationStorageEntity) {
            $configurationStorageEntity->delete();
        }
    }

    protected function findOrCreateConfigurationValueEntity(string $settingKey, string $scope, ?string $scopeIdentifier): SpyConfigurationValue
    {
        $valueQuery = $this->getFactory()->createSpyConfigurationValueQuery()
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
}
