<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Reader;

use Codeception\Test\Unit;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\Communication\Reader\ConfigurationSettingReader;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Communication
 * @group Reader
 * @group ConfigurationSettingReaderTest
 * Add your own group annotations below this line
 */
class ConfigurationSettingReaderTest extends Unit
{
    protected const string SECRET_SETTING_KEY = 'integrations:x:connection:api_key';

    protected const string PLAIN_SETTING_KEY = 'integrations:x:tracking:measurement_id';

    public function testFilterSecretSettingKeysReturnsOnlyTheSettingsTheSchemaMarksSecret(): void
    {
        // Arrange
        $configurationSettingReader = $this->createReader();

        // Act
        $secretSettingKeys = $configurationSettingReader->filterSecretSettingKeys([
            static::SECRET_SETTING_KEY => [ConfigurationSchemaConstants::SCHEMA_KEY_SECRET => true],
            static::PLAIN_SETTING_KEY => [ConfigurationSchemaConstants::SCHEMA_KEY_SECRET => false],
            'theme:storefront:colors:main' => [],
        ]);

        // Assert
        $this->assertSame([static::SECRET_SETTING_KEY], $secretSettingKeys);
    }

    /**
     * A schema that declares no secrets is a normal, safe answer - unlike an empty settings list,
     * which the callers reject before reaching here because it means nothing is known at all.
     */
    public function testFilterSecretSettingKeysReturnsNothingWhenNoSettingIsSecret(): void
    {
        // Arrange
        $configurationSettingReader = $this->createReader();

        // Act
        $secretSettingKeys = $configurationSettingReader->filterSecretSettingKeys([
            static::PLAIN_SETTING_KEY => [ConfigurationSchemaConstants::SCHEMA_KEY_SECRET => false],
            'theme:storefront:colors:main' => [ConfigurationSchemaConstants::SCHEMA_KEY_SECRET => false],
        ]);

        // Assert
        $this->assertSame([], $secretSettingKeys);
    }

    public function testFilterSecretSettingKeysReturnsNothingForAnEmptyMap(): void
    {
        // Arrange
        $configurationSettingReader = $this->createReader();

        // Act
        $secretSettingKeys = $configurationSettingReader->filterSecretSettingKeys([]);

        // Assert
        $this->assertSame([], $secretSettingKeys);
    }

    protected function createReader(): ConfigurationSettingReader
    {
        return new ConfigurationSettingReader($this->createMock(ConfigurationFacade::class));
    }
}
