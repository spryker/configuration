<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business\DataImport;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationErrorTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Spryker\Zed\Configuration\Business\DataImport\ConfigurationValueDataImportStep;
use Spryker\Zed\Configuration\Business\DataImport\DataSet\ConfigurationValueDataSetInterface;
use Spryker\Zed\Configuration\Business\Writer\ConfigurationValueWriterInterface;
use Spryker\Zed\DataImport\Business\Exception\DataImportException;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSet;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group DataImport
 * @group ConfigurationValueDataImportStepTest
 * Add your own group annotations below this line
 */
class ConfigurationValueDataImportStepTest extends Unit
{
    protected function createDataSet(array $data = []): DataSet
    {
        $defaults = [
            ConfigurationValueDataSetInterface::COLUMN_SETTING_KEY => 'catalog:items_per_page',
            ConfigurationValueDataSetInterface::COLUMN_SCOPE => 'global',
            ConfigurationValueDataSetInterface::COLUMN_SCOPE_IDENTIFIER => '',
            ConfigurationValueDataSetInterface::COLUMN_VALUE => '24',
        ];

        return new DataSet(array_merge($defaults, $data));
    }

    public function testExecuteSavesConfigurationValue(): void
    {
        // Arrange
        $writerMock = $this->createMock(ConfigurationValueWriterInterface::class);
        $response = (new ConfigurationValueCollectionResponseTransfer())
            ->setIsSuccess(true)
            ->setSavedCount(1);

        $writerMock->expects($this->once())
            ->method('saveConfigurationValues')
            ->with($this->callback(function (ConfigurationValueCollectionRequestTransfer $request) {
                $values = $request->getConfigurationValues();

                return $values->count() === 1
                    && $values[0]->getSettingKey() === 'catalog:items_per_page'
                    && $values[0]->getScope() === 'global'
                    && $values[0]->getValue() === '24';
            }))
            ->willReturn($response);

        $step = new ConfigurationValueDataImportStep($writerMock);

        // Act
        $step->execute($this->createDataSet());
    }

    public function testExecuteSavesStoreSpecificValue(): void
    {
        // Arrange
        $writerMock = $this->createMock(ConfigurationValueWriterInterface::class);
        $response = (new ConfigurationValueCollectionResponseTransfer())
            ->setIsSuccess(true)
            ->setSavedCount(1);

        $writerMock->expects($this->once())
            ->method('saveConfigurationValues')
            ->with($this->callback(function (ConfigurationValueCollectionRequestTransfer $request) {
                $values = $request->getConfigurationValues();

                return $values[0]->getScope() === 'store'
                    && $values[0]->getScopeIdentifier() === 'DE';
            }))
            ->willReturn($response);

        $step = new ConfigurationValueDataImportStep($writerMock);

        // Act
        $step->execute($this->createDataSet([
            ConfigurationValueDataSetInterface::COLUMN_SCOPE => 'store',
            ConfigurationValueDataSetInterface::COLUMN_SCOPE_IDENTIFIER => 'DE',
        ]));
    }

    public function testExecuteSkipsWhenIsSkippedFlagIsSet(): void
    {
        // Arrange
        $writerMock = $this->createMock(ConfigurationValueWriterInterface::class);
        $writerMock->expects($this->never())->method('saveConfigurationValues');

        $step = new ConfigurationValueDataImportStep($writerMock);

        // Act
        $step->execute($this->createDataSet([
            ConfigurationValueDataSetInterface::COLUMN_IS_SKIPPED => true,
        ]));
    }

    public function testExecuteThrowsExceptionWhenWriterReturnsErrors(): void
    {
        // Arrange
        $response = (new ConfigurationValueCollectionResponseTransfer())
            ->setIsSuccess(false)
            ->setSavedCount(0)
            ->addError(
                (new ConfigurationErrorTransfer())
                    ->setSettingKey('catalog:items_per_page')
                    ->setMessage('Validation failed'),
            );

        $writerMock = $this->createMock(ConfigurationValueWriterInterface::class);
        $writerMock->method('saveConfigurationValues')->willReturn($response);

        $step = new ConfigurationValueDataImportStep($writerMock);

        // Assert
        $this->expectException(DataImportException::class);
        $this->expectExceptionMessage('Failed to import configuration value');

        // Act
        $step->execute($this->createDataSet());
    }
}
