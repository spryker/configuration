<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\DataImport;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Generated\Shared\Transfer\EventEntityTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\DataImport\DataSet\ConfigurationValueDataSetInterface;
use Spryker\Zed\Configuration\Business\Writer\ConfigurationValueWriterInterface;
use Spryker\Zed\DataImport\Business\Exception\DataImportException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\PublishAwareStep;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\DataImport\Business\Model\Publisher\DataImporterPublisher;

class ConfigurationValueDataImportStep extends PublishAwareStep implements DataImportStepInterface
{
    /**
     * @see \Orm\Zed\Configuration\Persistence\Map\SpyConfigurationValueTableMap::COL_SCOPE
     */
    protected const string SCOPE_FIELD = 'spy_configuration_value.scope';

    /**
     * @see \Orm\Zed\Configuration\Persistence\Map\SpyConfigurationValueTableMap::COL_SCOPE_IDENTIFIER
     */
    protected const string SCOPE_IDENTIFIER_FIELD = 'spy_configuration_value.scope_identifier';

    public function __construct(
        protected ConfigurationValueWriterInterface $configurationValueWriter,
    ) {
    }

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface<string> $dataSet
     *
     * @throws \Spryker\Zed\DataImport\Business\Exception\DataImportException
     */
    public function execute(DataSetInterface $dataSet): void
    {
        if (!empty($dataSet[ConfigurationValueDataSetInterface::COLUMN_IS_SKIPPED])) {
            return;
        }

        $settingKey = $dataSet[ConfigurationValueDataSetInterface::COLUMN_SETTING_KEY];
        $scope = $dataSet[ConfigurationValueDataSetInterface::COLUMN_SCOPE];
        $scopeIdentifier = $dataSet[ConfigurationValueDataSetInterface::COLUMN_SCOPE_IDENTIFIER] ?: null;
        $value = $dataSet[ConfigurationValueDataSetInterface::COLUMN_VALUE];

        $configurationValueTransfer = (new ConfigurationValueTransfer())
            ->setSettingKey($settingKey)
            ->setScope($scope)
            ->setScopeIdentifier($scopeIdentifier ?? '')
            ->setValue($value);

        $configurationValueCollectionRequestTransfer = (new ConfigurationValueCollectionRequestTransfer())
            ->addConfigurationValue($configurationValueTransfer);

        $configurationValueCollectionResponseTransfer = $this->configurationValueWriter->saveConfigurationValues($configurationValueCollectionRequestTransfer);

        if (!$configurationValueCollectionResponseTransfer->getIsSuccess()) {
            $errorMessages = [];

            foreach ($configurationValueCollectionResponseTransfer->getErrors() as $errorTransfer) {
                $errorMessages[] = sprintf(
                    '[%s] %s',
                    $errorTransfer->getSettingKey() ?? $settingKey,
                    $errorTransfer->getMessage(),
                );
            }

            throw new DataImportException(
                sprintf('Failed to import configuration value for key "%s": %s', $settingKey, implode('; ', $errorMessages)),
            );
        }

        $this->publishConfigurationValueEvent($scope, $scopeIdentifier);
    }

    protected function publishConfigurationValueEvent(string $scope, ?string $scopeIdentifier): void
    {
        $eventEntityTransfer = (new EventEntityTransfer())
            ->setAdditionalValues([
                static::SCOPE_FIELD => $scope,
                static::SCOPE_IDENTIFIER_FIELD => $scopeIdentifier,
            ]);

        $deduplicationId = crc32(sprintf('%s:%s', $scope, $scopeIdentifier ?? ''));

        DataImporterPublisher::addEvent(
            ConfigurationConstants::CONFIGURATION_VALUE_PUBLISH_WRITE,
            $deduplicationId,
            $eventEntityTransfer,
        );
    }
}
