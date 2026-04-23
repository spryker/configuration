<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Persistence;

use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationPersistenceFactory getFactory()
 */
class ConfigurationRepository extends AbstractRepository implements ConfigurationRepositoryInterface
{
    public function findConfigurationValueByKeyAndScope(string $key, string $scope, ?string $scopeIdentifier = null): ?ConfigurationValueTransfer
    {
        $valueQuery = $this->getFactory()->createSpyConfigurationValueQuery()
            ->filterBySettingKey($key)
            ->filterByScope($scope);

        if ($scopeIdentifier !== null) {
            $valueQuery->filterByScopeIdentifier($scopeIdentifier);
        } else {
            $valueQuery->filterByScopeIdentifier(null, Criteria::ISNULL);
        }

        /** @var \Orm\Zed\Configuration\Persistence\SpyConfigurationValue|null $valueEntity */
        $valueEntity = $valueQuery->findOne();

        if (!$valueEntity) {
            return null;
        }

        return (new ConfigurationValueTransfer())
            ->setIdConfigurationValue($valueEntity->getIdConfigurationValue())
            ->setSettingKey($valueEntity->getSettingKey())
            ->setScope($valueEntity->getScope())
            ->setScopeIdentifier($valueEntity->getScopeIdentifier())
            ->setValue($valueEntity->getValue());
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string, \Generated\Shared\Transfer\ConfigurationValueTransfer>
     */
    public function findConfigurationValuesByKeysAndScope(array $keys, string $scope, ?string $scopeIdentifier = null): array
    {
        if (!$keys) {
            return [];
        }

        $valueQuery = $this->getFactory()->createSpyConfigurationValueQuery()
            ->filterBySettingKey($keys, Criteria::IN)
            ->filterByScope($scope);

        if ($scopeIdentifier !== null) {
            $valueQuery->filterByScopeIdentifier($scopeIdentifier);
        } else {
            $valueQuery->filterByScopeIdentifier(null, Criteria::ISNULL);
        }

        $result = [];

        foreach ($valueQuery->find() as $valueEntity) {
            $result[$valueEntity->getSettingKey()] = (new ConfigurationValueTransfer())
                ->setIdConfigurationValue($valueEntity->getIdConfigurationValue())
                ->setSettingKey($valueEntity->getSettingKey())
                ->setScope($valueEntity->getScope())
                ->setScopeIdentifier($valueEntity->getScopeIdentifier())
                ->setValue($valueEntity->getValue());
        }

        return $result;
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\ConfigurationValueTransfer>
     */
    public function findAllConfigurationValuesByScope(string $scope, ?string $scopeIdentifier = null): array
    {
        $valueQuery = $this->getFactory()->createSpyConfigurationValueQuery()
            ->filterByScope($scope);

        if ($scopeIdentifier !== null) {
            $valueQuery->filterByScopeIdentifier($scopeIdentifier);
        } else {
            $valueQuery->filterByScopeIdentifier(null, Criteria::ISNULL);
        }

        $result = [];

        foreach ($valueQuery->find() as $valueEntity) {
            $result[$valueEntity->getSettingKey()] = (new ConfigurationValueTransfer())
                ->setIdConfigurationValue($valueEntity->getIdConfigurationValue())
                ->setSettingKey($valueEntity->getSettingKey())
                ->setScope($valueEntity->getScope())
                ->setScopeIdentifier($valueEntity->getScopeIdentifier())
                ->setValue($valueEntity->getValue());
        }

        return $result;
    }
}
