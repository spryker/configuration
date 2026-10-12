<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Reader;

use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface;

class ConfigurationValueExportReader implements ConfigurationValueExportReaderInterface
{
    public function __construct(
        protected ConfigurationFacadeInterface $configurationFacade,
        protected ConfigurationRepositoryInterface $configurationRepository,
        protected ConfigurationConfig $configurationConfig
    ) {
    }

    /**
     * @param array<string> $settingKeys
     * @param array<string> $secretSettingKeys
     *
     * @return array<\Generated\Shared\Transfer\ConfigurationValueTransfer>
     */
    public function getExportableConfigurationValues(array $settingKeys, array $secretSettingKeys): array
    {
        $configurationValueTransfers = [];

        foreach ($this->configurationConfig->getAvailableScopes() as $scope) {
            foreach ($this->findScopeIdentifiers($scope) as $scopeIdentifier) {
                $configurationValueTransfers = array_merge(
                    $configurationValueTransfers,
                    $this->findScopeValues($scope, $scopeIdentifier, $settingKeys, $secretSettingKeys),
                );
            }
        }

        return $configurationValueTransfers;
    }

    /**
     * @return array<string>
     */
    public function getScopesWithoutIdentifier(): array
    {
        $scopes = [];

        foreach ($this->configurationConfig->getAvailableScopes() as $scope) {
            if ($scope === ConfigurationConstants::SCOPE_GLOBAL) {
                continue;
            }

            if ($this->configurationFacade->getScopeIdentifiers($scope) !== []) {
                continue;
            }

            $scopes[] = $scope;
        }

        return $scopes;
    }

    /**
     * @param array<string> $settingKeys
     * @param array<string> $secretSettingKeys
     *
     * @return array<\Generated\Shared\Transfer\ConfigurationValueTransfer>
     */
    protected function findScopeValues(string $scope, ?string $scopeIdentifier, array $settingKeys, array $secretSettingKeys): array
    {
        $configurationValueTransfers = [];

        foreach ($this->configurationRepository->findAllConfigurationValuesByScope($scope, $scopeIdentifier) as $configurationValueTransfer) {
            $settingKey = (string)$configurationValueTransfer->getSettingKey();

            if (!in_array($settingKey, $settingKeys, true) || in_array($settingKey, $secretSettingKeys, true)) {
                continue;
            }

            $configurationValueTransfers[] = $configurationValueTransfer;
        }

        return $configurationValueTransfers;
    }

    /**
     * @return array<string|null>
     */
    protected function findScopeIdentifiers(string $scope): array
    {
        if ($scope === ConfigurationConstants::SCOPE_GLOBAL) {
            return [null];
        }

        return array_values($this->configurationFacade->getScopeIdentifiers($scope));
    }
}
