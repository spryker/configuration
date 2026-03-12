<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Validator;

use Generated\Shared\Transfer\ConfigurationValidationResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;

interface ConfigurationValueValidatorInterface
{
    /**
     * Validates the given configuration value against its schema-defined constraints.
     *
     * @param \Generated\Shared\Transfer\ConfigurationValueTransfer $configurationValueTransfer
     *
     * @return \Generated\Shared\Transfer\ConfigurationValidationResponseTransfer
     */
    public function validate(ConfigurationValueTransfer $configurationValueTransfer): ConfigurationValidationResponseTransfer;
}
