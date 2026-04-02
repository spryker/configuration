<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Reader;

interface ConfigurationSettingReaderInterface
{
    /**
     * @param string $settingKey
     *
     * @return array<string, mixed>
     */
    public function getFileUploadConfig(string $settingKey): array;

    /**
     * @return array<string>
     */
    public function getFileUploadSettingKeys(): array;
}
