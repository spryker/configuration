<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Writer;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;

interface ConfigurationValueWriterInterface
{
    /**
     * Validates and saves a batch of configuration values, processes deletions.
     *
     * @param \Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer
     *
     * @return \Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer
     */
    public function saveConfigurationValues(
        ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer,
    ): ConfigurationValueCollectionResponseTransfer;
}
