<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Reader;

interface EnvironmentConfigurationValueReaderInterface
{
    public function findEnvironmentValue(string $variableName): ?string;

    /**
     * @return array<string, string>|null Keyed by `EnvironmentConfigurationValueReader::MAPPING_KEY_SETTING_KEY`
     *   and `EnvironmentConfigurationValueReader::MAPPING_KEY_VARIABLE_NAME`; `null` when the mapping is not
     *   of the form `<setting key>=<VARIABLE>` with a valid variable name.
     */
    public function findMapping(string $mapping): ?array;

    public function isAvailableScope(string $scope): bool;
}
