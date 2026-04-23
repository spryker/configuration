<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business\DataImport\Step;

use Codeception\Test\Unit;
use Spryker\Zed\Configuration\Business\DataImport\DataSet\ConfigurationValueDataSetInterface;
use Spryker\Zed\Configuration\Business\DataImport\Step\ConfigurationValueScopeIdentifierValidatorStep;
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
 * @group Step
 * @group ConfigurationValueScopeIdentifierValidatorStepTest
 * Add your own group annotations below this line
 */
class ConfigurationValueScopeIdentifierValidatorStepTest extends Unit
{
    public function testExecutePassesForGlobalScopeWithoutIdentifier(): void
    {
        // Arrange
        $step = new ConfigurationValueScopeIdentifierValidatorStep();
        $dataSet = new DataSet([
            ConfigurationValueDataSetInterface::COLUMN_SCOPE => 'global',
            ConfigurationValueDataSetInterface::COLUMN_SCOPE_IDENTIFIER => '',
        ]);

        // Act — no exception means pass
        $step->execute($dataSet);

        // Assert
        $this->assertTrue(true);
    }

    public function testExecutePassesForStoreScopeWithIdentifier(): void
    {
        // Arrange
        $step = new ConfigurationValueScopeIdentifierValidatorStep();
        $dataSet = new DataSet([
            ConfigurationValueDataSetInterface::COLUMN_SCOPE => 'store',
            ConfigurationValueDataSetInterface::COLUMN_SCOPE_IDENTIFIER => 'DE',
        ]);

        // Act — no exception means pass
        $step->execute($dataSet);

        // Assert
        $this->assertTrue(true);
    }

    public function testExecuteThrowsExceptionForNonGlobalScopeWithoutIdentifier(): void
    {
        // Arrange
        $step = new ConfigurationValueScopeIdentifierValidatorStep();
        $dataSet = new DataSet([
            ConfigurationValueDataSetInterface::COLUMN_SCOPE => 'store',
            ConfigurationValueDataSetInterface::COLUMN_SCOPE_IDENTIFIER => '',
        ]);

        // Assert
        $this->expectException(DataImportException::class);
        $this->expectExceptionMessage('Scope identifier is required for non-global scope "store"');

        // Act
        $step->execute($dataSet);
    }
}
