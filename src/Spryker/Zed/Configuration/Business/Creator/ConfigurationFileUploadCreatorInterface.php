<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Creator;

use Generated\Shared\Transfer\ConfigurationFileUploadCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationFileUploadCollectionResponseTransfer;

interface ConfigurationFileUploadCreatorInterface
{
    public function createFileUploadCollection(
        ConfigurationFileUploadCollectionRequestTransfer $configurationFileUploadCollectionRequestTransfer,
    ): ConfigurationFileUploadCollectionResponseTransfer;
}
