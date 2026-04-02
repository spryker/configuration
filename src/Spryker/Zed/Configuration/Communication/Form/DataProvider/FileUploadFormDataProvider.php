<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Form\DataProvider;

use Spryker\Zed\Configuration\Communication\Form\FileUploadForm;
use Spryker\Zed\Configuration\Communication\Reader\ConfigurationSettingReaderInterface;
use Spryker\Zed\Configuration\ConfigurationConfig;

class FileUploadFormDataProvider
{
    public function __construct(
        protected ConfigurationConfig $config,
        protected ConfigurationSettingReaderInterface $configurationSettingReader,
    ) {
    }

    /**
     * @param array<string, mixed> $fileUploadConfigurationSettings
     * @param string $settingKey
     *
     * @return array<string, mixed>
     */
    public function getOptions(array $fileUploadConfigurationSettings, string $settingKey = ''): array
    {
        return [
            FileUploadForm::OPTION_SETTING_KEY => $settingKey,
            FileUploadForm::OPTION_VALID_SETTING_KEYS => $this->configurationSettingReader->getFileUploadSettingKeys(),
            FileUploadForm::OPTION_MAX_FILE_SIZE => $fileUploadConfigurationSettings[FileUploadForm::OPTION_MAX_FILE_SIZE] ?? $this->config->getDefaultFileUploadMaxFileSize(),
            FileUploadForm::OPTION_ALLOWED_MIME_TYPES => $fileUploadConfigurationSettings[FileUploadForm::OPTION_ALLOWED_MIME_TYPES] ?? [],
            FileUploadForm::OPTION_ALLOWED_EXTENSIONS => $fileUploadConfigurationSettings[FileUploadForm::OPTION_ALLOWED_EXTENSIONS] ?? [],
        ];
    }
}
