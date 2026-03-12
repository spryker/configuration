<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Sync;

use Generated\Shared\Transfer\ConfigurationSyncResponseTransfer;

interface ConfigurationSchemaSyncInterface
{
    public function sync(): ConfigurationSyncResponseTransfer;
}
