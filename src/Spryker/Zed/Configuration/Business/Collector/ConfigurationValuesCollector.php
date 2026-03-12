<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Collector;

use Generated\Shared\Transfer\ConfigurationSettingValueCollectionTransfer;
use Generated\Shared\Transfer\ConfigurationSettingValuesCriteriaTransfer;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface;

class ConfigurationValuesCollector implements ConfigurationValuesCollectorInterface
{
    public function __construct(
        protected ConfigurationRepositoryInterface $repository,
        protected ConfigurationConfig $config,
    ) {
    }

    public function collectValues(ConfigurationSettingValuesCriteriaTransfer $criteria): ConfigurationSettingValueCollectionTransfer
    {
        $keys = $criteria->getSettingKeys();
        $scope = $criteria->getScopeOrFail();
        $scopeIdentifier = $criteria->getScopeIdentifier();

        $directValues = $this->repository->findConfigurationValuesByKeysAndScope($keys, $scope, $scopeIdentifier);

        $inheritedValues = $this->collectInheritedValues($keys, $scope, $scopeIdentifier, $directValues);

        return (new ConfigurationSettingValueCollectionTransfer())
            ->setDirectValues($this->extractValueStrings($directValues))
            ->setInheritedValues($inheritedValues);
    }

    /**
     * Resolves the inherited (hierarchy-fallback) value for each key that has no direct value.
     * Walks parent scopes from the given scope upward, issuing one bulk query per level.
     *
     * @param array<string> $keys
     * @param string $scope
     * @param string|null $scopeIdentifier
     * @param array<string, \Generated\Shared\Transfer\ConfigurationValueTransfer> $directValues
     *
     * @return array<string, string|null>
     */
    protected function collectInheritedValues(array $keys, string $scope, ?string $scopeIdentifier, array $directValues): array
    {
        $inherited = [];

        // Only settings without a direct value need hierarchy resolution.
        $unresolvedKeys = array_values(array_filter($keys, static fn ($key) => !isset($directValues[$key])));

        $parentScope = $this->getParentScope($scope);

        while ($parentScope !== null && (bool)$unresolvedKeys) {
            $parentIdentifier = $this->identifierForScope($parentScope, $scopeIdentifier);
            $found = $this->repository->findConfigurationValuesByKeysAndScope($unresolvedKeys, $parentScope, $parentIdentifier);

            foreach ($found as $key => $valueTransfer) {
                $inherited[$key] = $valueTransfer->getValue();
            }

            $unresolvedKeys = array_values(array_filter($unresolvedKeys, static fn ($key) => !isset($found[$key])));
            $parentScope = $this->getParentScope($parentScope);
        }

        return $inherited;
    }

    /**
     * Returns the identifier that should be used when querying a parent scope.
     * Parent scopes that are broader (e.g. global) have no meaningful identifier, so null is used.
     */
    protected function identifierForScope(string $scope, ?string $currentIdentifier): ?string
    {
        $scopeHierarchy = $this->config->getScopeHierarchy();

        if (!isset($scopeHierarchy[$scope])) {
            return null;
        }

        return $currentIdentifier;
    }

    protected function getParentScope(string $scope): ?string
    {
        $scopeHierarchy = $this->config->getScopeHierarchy();

        return $scopeHierarchy[$scope] ?? null;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\ConfigurationValueTransfer> $valueTransfers
     *
     * @return array<string, string|null>
     */
    protected function extractValueStrings(array $valueTransfers): array
    {
        $result = [];

        foreach ($valueTransfers as $key => $transfer) {
            $result[$key] = $transfer->getValue();
        }

        return $result;
    }
}
