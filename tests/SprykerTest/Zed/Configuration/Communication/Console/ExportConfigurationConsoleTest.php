<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Console;

use Codeception\Test\Unit;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\ConfigurationConfig;
use SprykerTest\Zed\Configuration\ConfigurationCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Communication
 * @group Console
 * @group ExportConfigurationConsoleTest
 * Add your own group annotations below this line
 */
class ExportConfigurationConsoleTest extends Unit
{
    protected ConfigurationCommunicationTester $tester;

    /**
     * @var string
     */
    protected const SCOPE_STORE = 'store';

    /**
     * @var string
     */
    protected const SETTING_KEY_MEASUREMENT_ID = 'integrations:google_analytics:tracking:measurement_id';

    /**
     * @var string
     */
    protected const SETTING_KEY_SECRET = 'integrations:google_analytics:connection:service_account_credentials_json';

    public function testWriteFileWritesTheValuesUnderTheValuesKey(): void
    {
        // Arrange
        $console = $this->createConsole();
        $file = tempnam(sys_get_temp_dir(), 'configuration-export');
        $values = [[
            'setting_key' => static::SETTING_KEY_MEASUREMENT_ID,
            'scope' => ConfigurationConstants::SCOPE_GLOBAL,
            'scope_identifier' => '',
            'value' => 'a value, with "quotes" and a trailing backslash \\',
        ]];

        // Act
        $console->writeFileForTest($file, $values);

        // Assert
        $written = json_decode((string)file_get_contents($file), true);
        unlink($file);
        $this->assertSame($values, $written['values']);
    }

    public function testWriteFileLeavesTheExportReadableOnlyByItsOwner(): void
    {
        // Arrange
        $console = $this->createConsole();
        $directory = sys_get_temp_dir() . '/' . uniqid('configuration-export', true) . '/nested';
        $file = $directory . '/exported-configuration-values.json';

        // Act
        $console->writeFileForTest($file, [[
            'setting_key' => static::SETTING_KEY_MEASUREMENT_ID,
            'scope' => ConfigurationConstants::SCOPE_GLOBAL,
            'scope_identifier' => '',
            'value' => 'G-R9NCW72D85',
        ]]);

        // Assert
        $this->assertSame(0600, fileperms($file) & 0777);
        $this->assertSame(0700, fileperms($directory) & 0777);
        unlink($file);
        rmdir($directory);
        rmdir(dirname($directory));
    }

    protected function createConsole(): ExportConfigurationConsoleStub
    {
        $configurationConfigMock = $this->createMock(ConfigurationConfig::class);
        $configurationFacadeMock = $this->createMock(ConfigurationFacade::class);

        return new ExportConfigurationConsoleStub($configurationConfigMock, $configurationFacadeMock);
    }
}
