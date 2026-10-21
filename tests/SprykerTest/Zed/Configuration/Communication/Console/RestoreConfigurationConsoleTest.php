<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Console;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use RuntimeException;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory;
use SprykerTest\Zed\Configuration\ConfigurationCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Communication
 * @group Console
 * @group RestoreConfigurationConsoleTest
 * Add your own group annotations below this line
 */
class RestoreConfigurationConsoleTest extends Unit
{
    protected ConfigurationCommunicationTester $tester;

    /**
     * @var string
     */
    protected const SETTING_KEY_MEASUREMENT_ID = 'integrations:google_analytics:tracking:measurement_id';

    /**
     * @var string
     */
    protected const SETTING_KEY_COLOR = 'theme:storefront:colors:main';

    public function testRestoreFileRestoresEveryNamedValueInTheExport(): void
    {
        // Arrange
        $file = $this->givenExportFile([
            $this->createExportedValue(static::SETTING_KEY_MEASUREMENT_ID, ConfigurationConstants::SCOPE_GLOBAL, '', 'G-GLOBAL'),
            $this->createExportedValue(static::SETTING_KEY_MEASUREMENT_ID, 'store', 'DE', 'G-STOREDE'),
        ]);
        $console = $this->createConsole();

        // Act
        $restoredCount = $console->restoreFileForTest($file, [static::SETTING_KEY_MEASUREMENT_ID]);

        // Assert
        unlink($file);
        $this->assertSame(2, $restoredCount);
    }

    public function testRestoreFileLeavesValuesThatAreNotNamed(): void
    {
        // Arrange
        $file = $this->givenExportFile([
            $this->createExportedValue(static::SETTING_KEY_MEASUREMENT_ID, ConfigurationConstants::SCOPE_GLOBAL, '', 'G-GLOBAL'),
            $this->createExportedValue(static::SETTING_KEY_COLOR, ConfigurationConstants::SCOPE_GLOBAL, '', '#ff0000'),
        ]);
        $console = $this->createConsole();

        // Act
        $restoredCount = $console->restoreFileForTest($file, [static::SETTING_KEY_MEASUREMENT_ID]);

        // Assert
        unlink($file);
        $this->assertSame(1, $restoredCount);
        $this->assertStringContainsString('1 value(s)', $console->getOutputForTest());
    }

    public function testRestoreFileReportsANamedSettingTheExportDoesNotHold(): void
    {
        // Arrange
        $file = $this->givenExportFile([
            $this->createExportedValue(static::SETTING_KEY_MEASUREMENT_ID, ConfigurationConstants::SCOPE_GLOBAL, '', 'G-GLOBAL'),
        ]);
        $console = $this->createConsole();

        // Act
        $console->restoreFileForTest($file, [static::SETTING_KEY_MEASUREMENT_ID, static::SETTING_KEY_COLOR]);

        // Assert
        unlink($file);
        $this->assertStringContainsString(static::SETTING_KEY_COLOR, $console->getOutputForTest());
    }

    public function testRestoreFileReportsAFileThatIsNotAnExport(): void
    {
        // Arrange
        $file = tempnam(sys_get_temp_dir(), 'configuration-export');
        file_put_contents($file, 'not json at all');
        $console = $this->createConsole();

        // Act
        $restoredCount = $console->restoreFileForTest($file, [static::SETTING_KEY_MEASUREMENT_ID]);

        // Assert
        unlink($file);
        $this->assertSame(0, $restoredCount);
        $this->assertSame(1, $console->getFailedCountForTest());
        $this->assertStringContainsString('not readable as an export', $console->getOutputForTest());
    }

    public function testRestoreFileCountsARejectedSaveAsFailed(): void
    {
        // Arrange
        $file = $this->givenExportFile([
            $this->createExportedValue(static::SETTING_KEY_MEASUREMENT_ID, ConfigurationConstants::SCOPE_GLOBAL, '', 'G-GLOBAL'),
        ]);
        $console = $this->createConsole(false);

        // Act
        $restoredCount = $console->restoreFileForTest($file, [static::SETTING_KEY_MEASUREMENT_ID]);

        // Assert
        unlink($file);
        $this->assertSame(0, $restoredCount);
        $this->assertSame(1, $console->getFailedCountForTest());
    }

    public function testRestoreFileDoesNotCountAnExportWithoutTheNamedSettingsAsFailed(): void
    {
        // Arrange
        $file = $this->givenExportFile([
            $this->createExportedValue(static::SETTING_KEY_COLOR, ConfigurationConstants::SCOPE_GLOBAL, '', '#ff0000'),
        ]);
        $console = $this->createConsole();

        // Act
        $restoredCount = $console->restoreFileForTest($file, [static::SETTING_KEY_MEASUREMENT_ID]);

        // Assert
        unlink($file);
        $this->assertSame(0, $restoredCount);
        $this->assertSame(0, $console->getFailedCountForTest());
    }

    public function testRestoreFileNeverPrintsTheValueOfAFailedSave(): void
    {
        // Arrange
        $value = 'a-value-that-must-never-be-printed';
        $file = $this->givenExportFile([
            $this->createExportedValue(static::SETTING_KEY_MEASUREMENT_ID, ConfigurationConstants::SCOPE_GLOBAL, '', $value),
        ]);
        $configurationFacadeMock = $this->createMock(ConfigurationFacade::class);
        $configurationFacadeMock->method('saveConfigurationValues')
            ->willThrowException(new RuntimeException(sprintf('Invalid value "%s".', $value)));
        $console = new RestoreConfigurationConsoleStub($configurationFacadeMock, $this->createFactoryMock());

        // Act
        $console->restoreFileForTest($file, [static::SETTING_KEY_MEASUREMENT_ID]);

        // Assert
        unlink($file);
        $output = $console->getOutputForTest();
        $this->assertSame(1, $console->getFailedCountForTest());
        $this->assertStringContainsString(static::SETTING_KEY_MEASUREMENT_ID, $output);
        $this->assertStringNotContainsString($value, $output);
    }

    /**
     * @param list<array<string, string>> $values
     */
    protected function givenExportFile(array $values): string
    {
        $file = tempnam(sys_get_temp_dir(), 'configuration-export');
        file_put_contents($file, (string)json_encode(['values' => $values]));

        return $file;
    }

    /**
     * @return array<string, string>
     */
    protected function createExportedValue(string $settingKey, string $scope, string $scopeIdentifier, string $value): array
    {
        return [
            'setting_key' => $settingKey,
            'scope' => $scope,
            'scope_identifier' => $scopeIdentifier,
            'value' => $value,
        ];
    }

    protected function createConsole(bool $isSaveSuccessful = true): RestoreConfigurationConsoleStub
    {
        $configurationFacadeMock = $this->createMock(ConfigurationFacade::class);
        $configurationFacadeMock->method('saveConfigurationValues')->willReturn(
            (new ConfigurationValueCollectionResponseTransfer())->setIsSuccess($isSaveSuccessful),
        );

        return new RestoreConfigurationConsoleStub($configurationFacadeMock, $this->createFactoryMock());
    }

    protected function createFactoryMock(): ConfigurationCommunicationFactory
    {
        $utilEncodingServiceMock = $this->createMock(UtilEncodingServiceInterface::class);
        $utilEncodingServiceMock->method('decodeJson')->willReturnCallback(
            static fn (string $json) => json_decode($json, true),
        );

        $configurationCommunicationFactoryMock = $this->createMock(ConfigurationCommunicationFactory::class);
        $configurationCommunicationFactoryMock->method('getUtilEncodingService')->willReturn($utilEncodingServiceMock);

        return $configurationCommunicationFactoryMock;
    }
}
