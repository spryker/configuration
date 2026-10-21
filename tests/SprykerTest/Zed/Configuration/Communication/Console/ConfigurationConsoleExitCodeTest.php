<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Console;

use Codeception\Test\Unit;
use Exception;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use PDOException;
use ReflectionProperty;
use RuntimeException;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReaderInterface;
use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory;
use Spryker\Zed\Configuration\Communication\Console\ExportConfigurationConsole;
use Spryker\Zed\Configuration\Communication\Console\ImportEnvironmentConfigurationConsole;
use Spryker\Zed\Configuration\Communication\Console\RestoreConfigurationConsole;
use Spryker\Zed\Configuration\Communication\Reader\ConfigurationSettingReaderInterface;
use Spryker\Zed\Configuration\Communication\Reader\ConfigurationValueExportReaderInterface;
use Spryker\Zed\Configuration\Communication\Reader\EnvironmentConfigurationValueReader;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Kernel\Communication\Console\Console;
use SprykerTest\Zed\Configuration\ConfigurationCommunicationTester;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Communication
 * @group Console
 * @group ConfigurationConsoleExitCodeTest
 * Add your own group annotations below this line
 */
class ConfigurationConsoleExitCodeTest extends Unit
{
    protected ConfigurationCommunicationTester $tester;

    protected const string SETTING_KEY = 'integrations:google_analytics:tracking:measurement_id';

    protected const string VARIABLE_NAME = 'CONFIGURATION_TEST_GA_MEASUREMENT_ID';

    protected const string READER_THROWS_MISSING_TABLE = 'missing table';

    protected const string READER_THROWS_OTHER_ERROR = 'other error';

    protected const string READER_RETURNS_NOTHING = 'nothing stored';

    protected const string READER_RETURNS_VALUE = 'value stored';

    protected const string FILE_ABSENT = 'absent';

    protected const string FILE_INVALID = 'invalid';

    protected const string FILE_VALID = 'valid';

    protected function _before(): void
    {
        putenv(sprintf('%s=%s', static::VARIABLE_NAME, 'G-R9NCW72D85'));
    }

    protected function _after(): void
    {
        putenv(static::VARIABLE_NAME);
    }

    /**
     * @dataProvider getImportCases
     *
     * @param array<string, mixed> $input
     */
    public function testImportEnvironmentExitCode(array $input, bool $hasSettingsMap, bool $isSchemaReadable, int $expectedExitCode): void
    {
        // Arrange
        $console = new ImportEnvironmentConfigurationConsole();
        $console->setFactory($this->createFactoryMock($hasSettingsMap, $isSchemaReadable, static::READER_RETURNS_NOTHING));
        $console->setFacade($this->createFacadeMock(true));

        // Act
        $exitCode = $console->run(new ArrayInput($input), new BufferedOutput());

        // Assert
        $this->assertSame($expectedExitCode, $exitCode);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function getImportCases(): array
    {
        $validMapping = static::SETTING_KEY . '=' . static::VARIABLE_NAME;

        return [
            'no mappings' => [[], true, true, Console::CODE_SUCCESS],
            'imported' => [['mapping' => [$validMapping]], true, true, Console::CODE_SUCCESS],
            'variable not set' => [['mapping' => [static::SETTING_KEY . '=CONFIGURATION_TEST_ABSENT']], true, true, Console::CODE_SUCCESS],
            'schema unreadable' => [['mapping' => [$validMapping]], true, false, Console::CODE_ERROR],
            'settings map missing' => [['mapping' => [$validMapping]], false, true, Console::CODE_ERROR],
            'invalid scope options' => [['mapping' => [$validMapping], '--scope-identifier' => 'DE'], true, true, Console::CODE_ERROR],
            'invalid mapping' => [['mapping' => [static::SETTING_KEY]], true, true, Console::CODE_ERROR],
            'unknown setting key' => [['mapping' => ['catalog:unknown:group:setting=' . static::VARIABLE_NAME]], true, true, Console::CODE_ERROR],
        ];
    }

    /**
     * @dataProvider getExportCases
     *
     * @param array<string> $settingKeys
     */
    public function testExportExitCode(
        array $settingKeys,
        bool $hasSettingsMap,
        string $exportReaderBehaviour,
        bool $isExportFileConfigured,
        int $expectedExitCode
    ): void {
        // Arrange
        $exportFile = sprintf('%s/%s/configuration-values.json', sys_get_temp_dir(), uniqid('configuration-export', true));
        $console = new ExportConfigurationConsole();
        $console->setFactory($this->createFactoryMock($hasSettingsMap, true, $exportReaderBehaviour));
        $console->setConfig($this->createConfigMock($isExportFileConfigured, $exportFile));

        // Act
        $exitCode = $console->run(new ArrayInput(['setting-key' => $settingKeys]), new BufferedOutput());

        // Assert
        $this->removeExportFile($exportFile);
        $this->assertSame($expectedExitCode, $exitCode);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function getExportCases(): array
    {
        $settingKeys = [static::SETTING_KEY];

        return [
            'no setting keys' => [[], true, static::READER_RETURNS_VALUE, true, Console::CODE_SUCCESS],
            'exported' => [$settingKeys, true, static::READER_RETURNS_VALUE, true, Console::CODE_SUCCESS],
            'nothing stored' => [$settingKeys, true, static::READER_RETURNS_NOTHING, true, Console::CODE_SUCCESS],
            'values table does not exist yet' => [$settingKeys, true, static::READER_THROWS_MISSING_TABLE, true, Console::CODE_SUCCESS],
            'values cannot be read for another reason' => [$settingKeys, true, static::READER_THROWS_OTHER_ERROR, true, Console::CODE_ERROR],
            'settings map missing' => [$settingKeys, false, static::READER_RETURNS_VALUE, true, Console::CODE_ERROR],
            'export file path cannot be resolved' => [$settingKeys, true, static::READER_RETURNS_VALUE, false, Console::CODE_ERROR],
        ];
    }

    /**
     * @dataProvider getRestoreCases
     *
     * @param array<string> $settingKeys
     */
    public function testRestoreExitCode(
        array $settingKeys,
        bool $hasSettingsMap,
        bool $isExportFileConfigured,
        string $exportFileState,
        bool $isSaveSuccessful,
        int $expectedExitCode
    ): void {
        // Arrange
        $exportFile = $this->givenExportFile($exportFileState);
        $console = new RestoreConfigurationConsole();
        $console->setFactory($this->createFactoryMock($hasSettingsMap, true, static::READER_RETURNS_NOTHING));
        $console->setFacade($this->createFacadeMock($isSaveSuccessful));
        $console->setConfig($this->createConfigMock($isExportFileConfigured, $exportFile));

        // Act
        $exitCode = $console->run(new ArrayInput(['setting-key' => $settingKeys]), new BufferedOutput());

        // Assert
        $this->removeExportFile($exportFile);
        $this->assertSame($expectedExitCode, $exitCode);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function getRestoreCases(): array
    {
        $settingKeys = [static::SETTING_KEY];

        return [
            'no setting keys' => [[], true, true, static::FILE_VALID, true, Console::CODE_SUCCESS],
            'restored' => [$settingKeys, true, true, static::FILE_VALID, true, Console::CODE_SUCCESS],
            'no export file' => [$settingKeys, true, true, static::FILE_ABSENT, true, Console::CODE_SUCCESS],
            'export file is not an export' => [$settingKeys, true, true, static::FILE_INVALID, true, Console::CODE_ERROR],
            'save rejected' => [$settingKeys, true, true, static::FILE_VALID, false, Console::CODE_ERROR],
            'settings map missing' => [$settingKeys, false, true, static::FILE_VALID, true, Console::CODE_ERROR],
            'export file path cannot be resolved' => [$settingKeys, true, false, static::FILE_VALID, true, Console::CODE_ERROR],
        ];
    }

    public function testRestoreRemovesTheExportFileOnlyWhenEverythingWasRestored(): void
    {
        // Arrange
        $exportFile = $this->givenExportFile(static::FILE_VALID);
        $console = new RestoreConfigurationConsole();
        $console->setFactory($this->createFactoryMock(true, true, static::READER_RETURNS_NOTHING));
        $console->setFacade($this->createFacadeMock(true));
        $console->setConfig($this->createConfigMock(true, $exportFile));

        // Act
        $console->run(new ArrayInput(['setting-key' => [static::SETTING_KEY]]), new BufferedOutput());

        // Assert
        $isExportFileKept = file_exists($exportFile);
        $this->removeExportFile($exportFile);
        $this->assertFalse($isExportFileKept);
    }

    protected function createFactoryMock(bool $hasSettingsMap, bool $isSchemaReadable, string $exportReaderBehaviour): ConfigurationCommunicationFactory
    {
        $configurationSettingReaderMock = $this->createMock(ConfigurationSettingReaderInterface::class);
        $configurationSettingReaderMock->method('filterSecretSettingKeys')->willReturn([]);

        $utilEncodingServiceMock = $this->createMock(UtilEncodingServiceInterface::class);
        $utilEncodingServiceMock->method('encodeJson')->willReturnCallback(static fn ($value) => json_encode($value));
        $utilEncodingServiceMock->method('decodeJson')->willReturnCallback(static fn (string $json) => json_decode($json, true));

        $configurationConfigMock = $this->createConfigMock(true, '');

        $configurationCommunicationFactoryMock = $this->createMock(ConfigurationCommunicationFactory::class);
        $configurationCommunicationFactoryMock->method('createConfigurationSchemaReader')
            ->willReturn($this->createSchemaReaderMock($hasSettingsMap, $isSchemaReadable));
        $configurationCommunicationFactoryMock->method('createConfigurationSettingReader')->willReturn($configurationSettingReaderMock);
        $configurationCommunicationFactoryMock->method('createConfigurationValueExportReader')
            ->willReturn($this->createExportReaderMock($exportReaderBehaviour));
        $configurationCommunicationFactoryMock->method('createEnvironmentConfigurationValueReader')
            ->willReturn(new EnvironmentConfigurationValueReader($configurationConfigMock));
        $configurationCommunicationFactoryMock->method('getUtilEncodingService')->willReturn($utilEncodingServiceMock);

        return $configurationCommunicationFactoryMock;
    }

    protected function createSchemaReaderMock(bool $hasSettingsMap, bool $isSchemaReadable): ConfigurationSchemaReaderInterface
    {
        $configurationSchemaReaderMock = $this->createMock(ConfigurationSchemaReaderInterface::class);

        if (!$isSchemaReadable) {
            $configurationSchemaReaderMock->method('getSettingsMap')->willThrowException(new RuntimeException('The settings map cannot be read.'));

            return $configurationSchemaReaderMock;
        }

        $settingsMap = $hasSettingsMap ? [static::SETTING_KEY => []] : [];
        $configurationSchemaReaderMock->method('getSettingsMap')->willReturn($settingsMap);

        return $configurationSchemaReaderMock;
    }

    protected function createExportReaderMock(string $exportReaderBehaviour): ConfigurationValueExportReaderInterface
    {
        $configurationValueExportReaderMock = $this->createMock(ConfigurationValueExportReaderInterface::class);
        $configurationValueExportReaderMock->method('getScopesWithoutIdentifier')->willReturn([]);

        if ($exportReaderBehaviour === static::READER_THROWS_MISSING_TABLE) {
            $missingTableException = $this->createPdoException('Base table or view not found', '42S02');
            $configurationValueExportReaderMock->method('getExportableConfigurationValues')
                ->willThrowException(new RuntimeException('Unable to execute SELECT statement.', 0, $missingTableException));

            return $configurationValueExportReaderMock;
        }

        if ($exportReaderBehaviour === static::READER_THROWS_OTHER_ERROR) {
            $connectionException = $this->createPdoException('Connection refused', '08006');
            $configurationValueExportReaderMock->method('getExportableConfigurationValues')
                ->willThrowException(new RuntimeException('Unable to execute SELECT statement.', 0, $connectionException));

            return $configurationValueExportReaderMock;
        }

        $storedValues = [];

        if ($exportReaderBehaviour === static::READER_RETURNS_VALUE) {
            $storedValues[] = (new ConfigurationValueTransfer())
                ->setSettingKey(static::SETTING_KEY)
                ->setScope(ConfigurationConstants::SCOPE_GLOBAL)
                ->setValue('G-R9NCW72D85');
        }

        $configurationValueExportReaderMock->method('getExportableConfigurationValues')->willReturn($storedValues);

        return $configurationValueExportReaderMock;
    }

    protected function createPdoException(string $message, string $sqlState): PDOException
    {
        $pdoException = new PDOException($message);

        // PDO puts the SQLSTATE string into the exception code, which the constructor only accepts as an integer.
        $codeProperty = new ReflectionProperty(Exception::class, 'code');
        $codeProperty->setValue($pdoException, $sqlState);

        return $pdoException;
    }

    protected function createConfigMock(bool $isExportFileConfigured, string $exportFile): ConfigurationConfig
    {
        $configurationConfigMock = $this->createMock(ConfigurationConfig::class);
        $configurationConfigMock->method('getAvailableScopes')->willReturn([ConfigurationConstants::SCOPE_GLOBAL]);

        if (!$isExportFileConfigured) {
            $configurationConfigMock->method('getExportFile')
                ->willThrowException(new RuntimeException('The project-level export file path cannot be resolved.'));

            return $configurationConfigMock;
        }

        $configurationConfigMock->method('getExportFile')->willReturn($exportFile);

        return $configurationConfigMock;
    }

    protected function createFacadeMock(bool $isSaveSuccessful): ConfigurationFacade
    {
        $configurationFacadeMock = $this->createMock(ConfigurationFacade::class);
        $configurationFacadeMock->method('saveConfigurationValues')
            ->willReturn((new ConfigurationValueCollectionResponseTransfer())->setIsSuccess($isSaveSuccessful));

        return $configurationFacadeMock;
    }

    protected function givenExportFile(string $exportFileState): string
    {
        $exportFile = sprintf('%s/%s.json', sys_get_temp_dir(), uniqid('configuration-export', true));

        if ($exportFileState === static::FILE_INVALID) {
            file_put_contents($exportFile, 'not json at all');
        }

        if ($exportFileState === static::FILE_VALID) {
            file_put_contents($exportFile, (string)json_encode([
            'values' => [[
                'setting_key' => static::SETTING_KEY,
                'scope' => ConfigurationConstants::SCOPE_GLOBAL,
                'scope_identifier' => '',
                'value' => 'G-R9NCW72D85',
            ]]]));
        }

        return $exportFile;
    }

    protected function removeExportFile(string $exportFile): void
    {
        if (is_file($exportFile)) {
            unlink($exportFile);
        }

        $exportDirectory = dirname($exportFile);

        if ($exportDirectory !== sys_get_temp_dir() && is_dir($exportDirectory)) {
            rmdir($exportDirectory);
        }
    }
}
