<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Reader;

use Generated\Shared\Transfer\ConfigurationValueTransfer;

interface EnvironmentConfigurationValueReaderInterface
{
    public function getEnvironmentVariablePrefix(): string;

    public function findEnvironmentValue(string $variableName): ?string;

    public function variableNameToConfigurationValueTransfer(string $variableName): ?ConfigurationValueTransfer;
}
