<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Reader;

interface ConfigurationValueExportReaderInterface
{
    /**
     * @param array<string> $settingKeys
     * @param array<string> $secretSettingKeys
     *
     * @return array<\Generated\Shared\Transfer\ConfigurationValueTransfer>
     */
    public function getExportableConfigurationValues(array $settingKeys, array $secretSettingKeys): array;

    /**
     * @return array<string>
     */
    public function getScopesWithoutIdentifier(): array;
}
