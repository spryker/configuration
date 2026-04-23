<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\DataImport\Step;

use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Zed\Configuration\Business\DataImport\DataSet\ConfigurationValueDataSetInterface;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;
use Spryker\Zed\DataImport\Business\Exception\DataImportException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;

class ConfigurationValueSettingKeyValidatorStep implements DataImportStepInterface
{
    public function __construct(
        protected ConfigurationSchemaProviderInterface $schemaProvider,
    ) {
    }

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface<string> $dataSet
     *
     * @throws \Spryker\Zed\DataImport\Business\Exception\DataImportException
     */
    public function execute(DataSetInterface $dataSet): void
    {
        $settingKey = $dataSet[ConfigurationValueDataSetInterface::COLUMN_SETTING_KEY];
        $settingsMap = $this->schemaProvider->getSettingsMap();

        if (!isset($settingsMap[$settingKey])) {
            throw new DataImportException(
                sprintf('Setting key "%s" does not exist in the configuration schema. Run "console configuration:sync" first.', $settingKey),
            );
        }

        if (!empty($settingsMap[$settingKey][ConfigurationSchemaConstants::SCHEMA_KEY_SECRET])) {
            trigger_error(
                sprintf('Skipping secret setting "%s" — secret values cannot be imported via CSV.', $settingKey),
                E_USER_WARNING,
            );

            $dataSet[ConfigurationValueDataSetInterface::COLUMN_IS_SKIPPED] = true;
        }
    }
}
