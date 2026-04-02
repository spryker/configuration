<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Reader;

use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;

class ConfigurationSettingReader implements ConfigurationSettingReaderInterface
{
    public function __construct(protected ConfigurationFacadeInterface $configurationFacade)
    {
    }

    /**
     * @param string $settingKey
     *
     * @return array<string, mixed>
     */
    public function getFileUploadConfig(string $settingKey): array
    {
        foreach ($this->configurationFacade->getAllConfigurationSettings() as $setting) {
            if ($setting->getKey() !== $settingKey) {
                continue;
            }

            return $setting->getFileUpload() ?: [];
        }

        return [];
    }

    /**
     * @return array<string>
     */
    public function getFileUploadSettingKeys(): array
    {
        $keys = [];

        foreach ($this->configurationFacade->getAllConfigurationSettings() as $setting) {
            if (!$setting->getFileUpload()) {
                continue;
            }

            $keys[] = $setting->getKeyOrFail();
        }

        return $keys;
    }
}
