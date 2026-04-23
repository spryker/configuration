<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Logger;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;

interface ConfigurationAuditLoggerInterface
{
    public function logConfigurationValueSaved(ConfigurationValueTransfer $configurationValueTransfer): void;

    /**
     * @param array<\Generated\Shared\Transfer\ConfigurationErrorTransfer> $errors
     */
    public function logConfigurationValueSaveFailed(
        ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer,
        array $errors,
    ): void;
}
