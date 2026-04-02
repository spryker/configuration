<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Reader;

use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;

interface ConfigurationValueResolverInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed;

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationValues(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): array;
}
