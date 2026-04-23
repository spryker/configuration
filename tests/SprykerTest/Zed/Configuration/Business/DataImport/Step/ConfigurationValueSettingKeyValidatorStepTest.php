<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business\DataImport\Step;

use Codeception\Test\Unit;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Zed\Configuration\Business\DataImport\DataSet\ConfigurationValueDataSetInterface;
use Spryker\Zed\Configuration\Business\DataImport\Step\ConfigurationValueSettingKeyValidatorStep;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;
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
 * @group ConfigurationValueSettingKeyValidatorStepTest
 * Add your own group annotations below this line
 */
class ConfigurationValueSettingKeyValidatorStepTest extends Unit
{
    protected function createSchemaProviderMock(): ConfigurationSchemaProviderInterface
    {
        $settingsMap = [
            'catalog:items_per_page' => [
                ConfigurationSchemaConstants::SCHEMA_KEY_TYPE => ConfigurationSchemaConstants::VALUE_TYPE_INTEGER,
                ConfigurationSchemaConstants::SCHEMA_KEY_SECRET => false,
            ],
            'payment:api_key' => [
                ConfigurationSchemaConstants::SCHEMA_KEY_TYPE => ConfigurationSchemaConstants::VALUE_TYPE_STRING,
                ConfigurationSchemaConstants::SCHEMA_KEY_SECRET => true,
            ],
        ];

        $mock = $this->createMock(ConfigurationSchemaProviderInterface::class);
        $mock->method('getSettingsMap')->willReturn($settingsMap);

        return $mock;
    }

    public function testExecutePassesForValidSettingKey(): void
    {
        // Arrange
        $step = new ConfigurationValueSettingKeyValidatorStep($this->createSchemaProviderMock());
        $dataSet = new DataSet([
            ConfigurationValueDataSetInterface::COLUMN_SETTING_KEY => 'catalog:items_per_page',
        ]);

        // Act
        $step->execute($dataSet);

        // Assert — no exception, no skip flag
        $this->assertEmpty($dataSet[ConfigurationValueDataSetInterface::COLUMN_IS_SKIPPED] ?? null);
    }

    public function testExecuteThrowsExceptionForUnknownSettingKey(): void
    {
        // Arrange
        $step = new ConfigurationValueSettingKeyValidatorStep($this->createSchemaProviderMock());
        $dataSet = new DataSet([
            ConfigurationValueDataSetInterface::COLUMN_SETTING_KEY => 'unknown:setting',
        ]);

        // Assert
        $this->expectException(DataImportException::class);
        $this->expectExceptionMessage('Setting key "unknown:setting" does not exist');

        // Act
        $step->execute($dataSet);
    }

    public function testExecuteSetsSkipFlagForSecretSetting(): void
    {
        // Arrange
        $step = new ConfigurationValueSettingKeyValidatorStep($this->createSchemaProviderMock());
        $dataSet = new DataSet([
            ConfigurationValueDataSetInterface::COLUMN_SETTING_KEY => 'payment:api_key',
        ]);

        // Act
        set_error_handler(static function (): bool {
            return true;
        }, E_USER_WARNING);

        try {
            $step->execute($dataSet);
        } finally {
            restore_error_handler();
        }

        // Assert
        $this->assertTrue($dataSet[ConfigurationValueDataSetInterface::COLUMN_IS_SKIPPED]);
    }
}
