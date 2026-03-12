<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Storage;

use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;
use Spryker\Zed\Configuration\Persistence\ConfigurationEntityManagerInterface;
use Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface;

class ConfigurationStorageWriter implements ConfigurationStorageWriterInterface
{
    /**
     * @see \Orm\Zed\Configuration\Persistence\Map\SpyConfigurationValueTableMap::COL_SCOPE
     */
    protected const string SCOPE_FIELD = 'spy_configuration_value.scope';

    /**
     * @see \Orm\Zed\Configuration\Persistence\Map\SpyConfigurationValueTableMap::COL_SCOPE_IDENTIFIER
     */
    protected const string SCOPE_IDENTIFIER_FIELD = 'spy_configuration_value.scope_identifier';

    public function __construct(
        protected ConfigurationRepositoryInterface $repository,
        protected ConfigurationEntityManagerInterface $entityManager,
        protected ConfigurationSchemaProviderInterface $schemaProvider,
    ) {
    }

    /**
     * @param list<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     *
     * @return void
     */
    public function writeByConfigurationValueEvents(array $eventEntityTransfers): void
    {
        foreach ($this->extractUniqueScopeKeys($eventEntityTransfers) as $scopeKey) {
            $this->rebuildScopeStorageEntry($scopeKey['scope'], $scopeKey['scopeIdentifier']);
        }
    }

    /**
     * Rebuilds the single storage row for (scope, scopeIdentifier) by loading all saved values
     * for that scope, filtering to storefront-enabled non-secret settings, and writing the map.
     * Deletes the storage row when no publishable values remain.
     */
    protected function rebuildScopeStorageEntry(string $scope, ?string $scopeIdentifier): void
    {
        $storageKey = $this->buildStorageKey($scope, $scopeIdentifier);
        $allValues = $this->repository->findAllConfigurationValuesByScope($scope, $scopeIdentifier);

        if (!$allValues) {
            $this->entityManager->deleteConfigurationStorage($storageKey);

            return;
        }

        $settingsMap = $this->schemaProvider->getSettingsMap();
        $data = [];

        foreach ($allValues as $settingKey => $valueTransfer) {
            if (!isset($settingsMap[$settingKey])) {
                continue;
            }

            $settingEntry = $settingsMap[$settingKey];

            if (empty($settingEntry[ConfigurationConstants::SCHEMA_KEY_STOREFRONT]) || !empty($settingEntry[ConfigurationConstants::SCHEMA_KEY_SECRET])) {
                continue;
            }

            $data[$settingKey] = $valueTransfer->getValueOrFail();
        }

        $this->entityManager->saveConfigurationStorage($storageKey, $data);
    }

    /**
     * Extracts unique (scope, scopeIdentifier) pairs from the event batch so the scope row
     * is rebuilt at most once per scope even when multiple settings changed together.
     *
     * @param list<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     *
     * @return array<string, array{scope: string, scopeIdentifier: string|null}>
     */
    protected function extractUniqueScopeKeys(array $eventEntityTransfers): array
    {
        $scopeKeys = [];

        foreach ($eventEntityTransfers as $eventEntityTransfer) {
            $scope = $eventEntityTransfer->getAdditionalValues()[static::SCOPE_FIELD] ?? null;
            $scopeIdentifier = $eventEntityTransfer->getAdditionalValues()[static::SCOPE_IDENTIFIER_FIELD] ?? null;

            if ($scope === null) {
                continue;
            }

            $deduplicationKey = sprintf('%s:%s', $scope, $scopeIdentifier ?? '');
            $scopeKeys[$deduplicationKey] = ['scope' => $scope, 'scopeIdentifier' => $scopeIdentifier];
        }

        return $scopeKeys;
    }

    protected function buildStorageKey(string $scope, ?string $scopeIdentifier): string
    {
        $parts = [$scope];

        if ($scopeIdentifier !== null) {
            $parts[] = $scopeIdentifier;
        }

        return implode(ConfigurationConstants::STORAGE_KEY_SEPARATOR, $parts);
    }
}
