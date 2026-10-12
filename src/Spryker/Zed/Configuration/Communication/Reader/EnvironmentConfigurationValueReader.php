<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Reader;

use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\ConfigurationConfig;

class EnvironmentConfigurationValueReader implements EnvironmentConfigurationValueReaderInterface
{
    public const string ENVIRONMENT_VARIABLE_PREFIX = 'configuration:';

    public function __construct(protected ConfigurationConfig $configurationConfig)
    {
    }

    public function getEnvironmentVariablePrefix(): string
    {
        return static::ENVIRONMENT_VARIABLE_PREFIX;
    }

    public function findEnvironmentValue(string $variableName): ?string
    {
        $value = getenv($variableName);

        if ($value === false || $value === '') {
            return null;
        }

        return $value;
    }

    public function variableNameToConfigurationValueTransfer(string $variableName): ?ConfigurationValueTransfer
    {
        if (!str_starts_with($variableName, static::ENVIRONMENT_VARIABLE_PREFIX)) {
            return null;
        }

        $variableNameWithoutPrefix = substr($variableName, strlen(static::ENVIRONMENT_VARIABLE_PREFIX));

        foreach ($this->configurationConfig->getAvailableScopes() as $scope) {
            $scopePrefix = $scope . ':';

            if (!str_starts_with($variableNameWithoutPrefix, $scopePrefix)) {
                continue;
            }

            return $this->createConfigurationValue($scope, substr($variableNameWithoutPrefix, strlen($scopePrefix)));
        }

        return null;
    }

    protected function createConfigurationValue(string $scope, string $scopeRemainder): ?ConfigurationValueTransfer
    {
        if ($scope === ConfigurationConstants::SCOPE_GLOBAL) {
            return $scopeRemainder === ''
                ? null
                : $this->createConfigurationValueTransfer($scope, null, $scopeRemainder);
        }

        $separatorPosition = strpos($scopeRemainder, ':');

        if ($separatorPosition === false || $separatorPosition === 0) {
            return null;
        }

        $settingKey = substr($scopeRemainder, $separatorPosition + 1);

        if ($settingKey === '') {
            return null;
        }

        return $this->createConfigurationValueTransfer($scope, substr($scopeRemainder, 0, $separatorPosition), $settingKey);
    }

    protected function createConfigurationValueTransfer(string $scope, ?string $scopeIdentifier, string $settingKey): ConfigurationValueTransfer
    {
        return (new ConfigurationValueTransfer())
            ->setScope($scope)
            ->setScopeIdentifier($scopeIdentifier)
            ->setSettingKey($settingKey);
    }
}
