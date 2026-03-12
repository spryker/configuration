<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Schema;

use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReaderInterface;

interface ConfigurationSchemaProviderInterface extends ConfigurationSchemaReaderInterface
{
    /**
     * @return array<\Generated\Shared\Transfer\ConfigurationSettingTransfer>
     */
    public function getAllSettingTransfers(): array;
}
