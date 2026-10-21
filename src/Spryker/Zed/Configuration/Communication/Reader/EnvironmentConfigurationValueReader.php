<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Reader;

use Spryker\Zed\Configuration\ConfigurationConfig;

class EnvironmentConfigurationValueReader implements EnvironmentConfigurationValueReaderInterface
{
    public const string MAPPING_SEPARATOR = '=';

    public const string MAPPING_KEY_SETTING_KEY = 'settingKey';

    public const string MAPPING_KEY_VARIABLE_NAME = 'variableName';

    protected const string VARIABLE_NAME_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    public function __construct(protected ConfigurationConfig $configurationConfig)
    {
    }

    public function findEnvironmentValue(string $variableName): ?string
    {
        $value = getenv($variableName);

        if ($value === false || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @return array<string, string>|null
     */
    public function findMapping(string $mapping): ?array
    {
        $separatorPosition = strpos($mapping, static::MAPPING_SEPARATOR);

        if ($separatorPosition === false) {
            return null;
        }

        $settingKey = trim(substr($mapping, 0, $separatorPosition));
        $variableName = trim(substr($mapping, $separatorPosition + strlen(static::MAPPING_SEPARATOR)));

        if ($settingKey === '' || preg_match(static::VARIABLE_NAME_PATTERN, $variableName) !== 1) {
            return null;
        }

        return [
            static::MAPPING_KEY_SETTING_KEY => $settingKey,
            static::MAPPING_KEY_VARIABLE_NAME => $variableName,
        ];
    }

    public function isAvailableScope(string $scope): bool
    {
        return in_array($scope, $this->configurationConfig->getAvailableScopes(), true);
    }
}
