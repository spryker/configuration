<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business\DataImport\Step;

use Codeception\Test\Unit;
use Spryker\Zed\Configuration\Business\DataImport\DataSet\ConfigurationValueDataSetInterface;
use Spryker\Zed\Configuration\Business\DataImport\Step\ConfigurationValueScopeValidatorStep;
use Spryker\Zed\Configuration\ConfigurationConfig;
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
 * @group ConfigurationValueScopeValidatorStepTest
 * Add your own group annotations below this line
 */
class ConfigurationValueScopeValidatorStepTest extends Unit
{
    protected function createConfigMock(): ConfigurationConfig
    {
        $mock = $this->createMock(ConfigurationConfig::class);
        $mock->method('getAvailableScopes')->willReturn(['global', 'store']);

        return $mock;
    }

    public function testExecutePassesForValidScope(): void
    {
        // Arrange
        $step = new ConfigurationValueScopeValidatorStep($this->createConfigMock());
        $dataSet = new DataSet([
            ConfigurationValueDataSetInterface::COLUMN_SCOPE => 'global',
        ]);

        // Act — no exception means pass
        $step->execute($dataSet);

        // Assert
        $this->assertTrue(true);
    }

    public function testExecuteThrowsExceptionForInvalidScope(): void
    {
        // Arrange
        $step = new ConfigurationValueScopeValidatorStep($this->createConfigMock());
        $dataSet = new DataSet([
            ConfigurationValueDataSetInterface::COLUMN_SCOPE => 'invalid_scope',
        ]);

        // Assert
        $this->expectException(DataImportException::class);
        $this->expectExceptionMessage('Scope "invalid_scope" is not valid');

        // Act
        $step->execute($dataSet);
    }
}
