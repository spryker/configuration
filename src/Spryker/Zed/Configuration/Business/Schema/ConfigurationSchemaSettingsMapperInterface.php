<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Schema;

interface ConfigurationSchemaSettingsMapperInterface
{
    /**
     * Maps a merged schema array to an ordered flat list of ConfigurationSettingTransfer objects.
     *
     * @param array<mixed> $schema
     *
     * @return array<\Generated\Shared\Transfer\ConfigurationSettingTransfer>
     */
    public function mapSchemaToSettingTransfers(array $schema): array;
}
