<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Sanitizer;

use Generated\Shared\Transfer\ConfigurationValueTransfer;

interface ConfigurationValueSanitizerInterface
{
    public function isSanitizeXssEnabled(string $settingKey): bool;

    public function sanitize(ConfigurationValueTransfer $configurationValueTransfer): void;
}
