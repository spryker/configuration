<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationSyncResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValue;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValueQuery;
use PDOException;
use Propel\Runtime\Connection\Exception\ConnectionException;
use ReflectionProperty;
use RuntimeException;
use Spryker\Shared\Configuration\ConfigurationConfig as SharedConfigurationConfig;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptor;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptorInterface;
use Spryker\Shared\Configuration\Reader\AbstractConfigurationValueResolver;
use Spryker\Zed\Configuration\Business\ConfigurationBusinessFactory;
use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\Business\Migrator\ConfigurationSecretValueMigratorInterface;
use Spryker\Zed\Configuration\Business\Sync\SecretValueMigratingConfigurationSchemaSync;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Configuration\ConfigurationDependencyProvider;
use SprykerTest\Zed\Configuration\ConfigurationBusinessTester;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Facade
 * @group SyncConfigurationSchemasSecretValuesFacadeTest
 * Add your own group annotations below this line
 */
class SyncConfigurationSchemasSecretValuesFacadeTest extends Unit
{
    protected const string SETTING_KEY = 'secret_feature:general:credentials:api_key';

    protected const string PLAIN_VALUE = 'plain-api-key-value';

    protected const string SCOPE_GLOBAL = 'global';

    protected const string SCOPE_STORE = 'store';

    protected const string STORE_NAME = 'DE';

    protected const string ENCRYPTION_KEY = '0123456789abcdef0123456789abcdef';

    protected const string OTHER_ENCRYPTION_KEY = 'fedcba9876543210fedcba9876543210';

    protected const string ENCRYPTION_INIT_VECTOR = 'abcdef0123456789';

    protected ConfigurationBusinessTester $tester;

    protected string $tempDirectory;

    protected function _before(): void
    {
        $this->tempDirectory = __DIR__ . '/../_data/secret_value_sync_' . uniqid();
        mkdir($this->tempDirectory . '/schemas', 0755, true);

        $this->resetResolverCaches();
    }

    protected function _after(): void
    {
        foreach (['schemas/test.configuration.yaml', 'merged-schema.php', 'settings-map.php'] as $fileName) {
            if (file_exists($this->tempDirectory . '/' . $fileName)) {
                unlink($this->tempDirectory . '/' . $fileName);
            }
        }

        rmdir($this->tempDirectory . '/schemas');
        rmdir($this->tempDirectory);

        $this->resetResolverCaches();
    }

    public function testGivenPlainValuesWhenTheSettingBecomesSecretThenTheSyncEncryptsThemAndTheReaderReturnsThem(): void
    {
        // Arrange
        $facade = $this->createFacadeWithSettingsMap(false);
        $this->haveConfigurationValue(static::PLAIN_VALUE, static::SCOPE_GLOBAL);
        $this->haveConfigurationValue(static::PLAIN_VALUE, static::SCOPE_STORE, static::STORE_NAME);

        // Act
        $configurationSyncResponseTransfer = $this->syncSchemaWithSecretFlag($facade, true);

        // Assert
        $this->assertTrue($configurationSyncResponseTransfer->getIsSuccess());
        $this->assertCount(2, $this->filterWarningMessages($configurationSyncResponseTransfer, 'Rotate this credential'));
        $this->assertStoredValuesAreEncrypted([static::SCOPE_GLOBAL => null, static::SCOPE_STORE => static::STORE_NAME]);
        $this->assertSame(static::PLAIN_VALUE, $this->readConfigurationValue($facade));
    }

    /**
     * In a docker deploy the image build writes the settings map, so the deploy sync finds the flag already changed.
     */
    public function testGivenASettingsMapThatIsAlreadySecretWhenTheSchemaIsSyncedThenThePlainValueIsEncrypted(): void
    {
        // Arrange
        $facade = $this->createFacadeWithSettingsMap(true);
        $this->haveConfigurationValue(static::PLAIN_VALUE, static::SCOPE_GLOBAL);

        // Act
        $configurationSyncResponseTransfer = $this->syncSchemaWithSecretFlag($facade, true);

        // Assert
        $this->assertCount(1, $this->filterWarningMessages($configurationSyncResponseTransfer, 'Rotate this credential'));
        $this->assertStoredValuesAreEncrypted([static::SCOPE_GLOBAL => null]);
    }

    public function testGivenAnEncryptedValueWhenTheSettingIsNoLongerSecretThenTheSyncDecryptsIt(): void
    {
        // Arrange
        $facade = $this->createFacadeWithSettingsMap(true);
        $this->haveConfigurationValue($this->createEncryptor()->encrypt(static::PLAIN_VALUE), static::SCOPE_GLOBAL);

        // Act
        $configurationSyncResponseTransfer = $this->syncSchemaWithSecretFlag($facade, false);

        // Assert
        $this->assertCount(1, $this->filterWarningMessages($configurationSyncResponseTransfer, 'stored as plain text'));
        $this->assertSame(static::PLAIN_VALUE, $this->findStoredValue(static::SCOPE_GLOBAL));
        $this->assertSame(static::PLAIN_VALUE, $this->readConfigurationValue($facade));
    }

    public function testGivenAMigratedValueWhenTheSchemaIsSyncedAgainThenNothingChanges(): void
    {
        // Arrange
        $facade = $this->createFacadeWithSettingsMap(false);
        $this->haveConfigurationValue(static::PLAIN_VALUE, static::SCOPE_GLOBAL);
        $this->syncSchemaWithSecretFlag($facade, true);

        // Act
        $configurationSyncResponseTransfer = $this->syncSchemaWithSecretFlag($facade, true);

        // Assert
        $this->assertSame([], $configurationSyncResponseTransfer->getWarningMessages());
        $this->assertStoredValuesAreEncrypted([static::SCOPE_GLOBAL => null]);
    }

    public function testGivenAValueEncryptedWithAnotherKeyWhenTheSchemaIsSyncedThenTheValueIsNotChangedAndAWarningIsReturned(): void
    {
        // Arrange
        $facade = $this->createFacadeWithSettingsMap(true);
        $foreignCiphertext = $this->createEncryptor(static::OTHER_ENCRYPTION_KEY)->encrypt(static::PLAIN_VALUE);
        $this->haveConfigurationValue($foreignCiphertext, static::SCOPE_GLOBAL);

        // Act
        $configurationSyncResponseTransfer = $this->syncSchemaWithSecretFlag($facade, true);

        // Assert
        $this->assertCount(1, $this->filterWarningMessages($configurationSyncResponseTransfer, 'cannot be decrypted'));
        $this->assertSame($foreignCiphertext, $this->findStoredValue(static::SCOPE_GLOBAL));
    }

    public function testGivenAnEmptyValueWhenTheSettingBecomesSecretThenTheValueStaysEmpty(): void
    {
        // Arrange
        $facade = $this->createFacadeWithSettingsMap(false);
        $this->haveConfigurationValue('', static::SCOPE_GLOBAL);

        // Act
        $configurationSyncResponseTransfer = $this->syncSchemaWithSecretFlag($facade, true);

        // Assert
        $this->assertSame([], $configurationSyncResponseTransfer->getWarningMessages());
        $this->assertSame('', $this->findStoredValue(static::SCOPE_GLOBAL));
    }

    /**
     * @dataProvider provideDatabaseUnavailableExceptions
     */
    public function testGivenNoDatabaseWhenTheSchemaIsSyncedThenTheSyncSucceedsWithAWarning(Throwable $databaseException): void
    {
        // Arrange
        $this->writeSchemaWithSecretFlag(true);
        $configurationSchemaSync = $this->createConfigurationSchemaSyncWithFailingMigrator($databaseException);

        // Act
        $configurationSyncResponseTransfer = $configurationSchemaSync->sync();

        // Assert
        $this->assertTrue($configurationSyncResponseTransfer->getIsSuccess());
        $this->assertCount(1, $this->filterWarningMessages($configurationSyncResponseTransfer, 'database is not reachable'));
        $this->assertFileExists($this->getSettingsMapFilePath());
    }

    public function testGivenAnotherDatabaseErrorWhenTheSchemaIsSyncedThenTheErrorIsThrown(): void
    {
        // Arrange
        $this->writeSchemaWithSecretFlag(true);
        $configurationSchemaSync = $this->createConfigurationSchemaSyncWithFailingMigrator(new PDOException('Deadlock found', 40001));

        // Expect
        $this->expectException(PDOException::class);

        // Act
        $configurationSchemaSync->sync();
    }

    /**
     * @return array<string, array{\Throwable}>
     */
    public function provideDatabaseUnavailableExceptions(): array
    {
        return [
            'connection cannot be opened' => [new ConnectionException('Unable to open connection', 0, new PDOException('Connection refused'))],
            'table does not exist (MariaDB)' => [new RuntimeException('Query failed', 0, $this->createPdoException('42S02'))],
            'table does not exist (PostgreSQL)' => [$this->createPdoException('42P01')],
        ];
    }

    protected function createFacadeWithSettingsMap(bool $isSecret): ConfigurationFacade
    {
        $facade = $this->createFacade();
        $configurationSyncResponseTransfer = $this->syncSchemaWithSecretFlag($facade, $isSecret);
        $this->assertTrue($configurationSyncResponseTransfer->getIsSuccess(), implode(PHP_EOL, $configurationSyncResponseTransfer->getErrorMessages()));

        return $facade;
    }

    protected function createConfigurationSchemaSyncWithFailingMigrator(Throwable $databaseException): SecretValueMigratingConfigurationSchemaSync
    {
        $factory = $this->createFactory();
        $secretValueMigratorMock = $this->createMock(ConfigurationSecretValueMigratorInterface::class);
        $secretValueMigratorMock->method('migrate')->willThrowException($databaseException);

        return new SecretValueMigratingConfigurationSchemaSync(
            $factory->createSecretValueUnawareConfigurationSchemaSync(),
            $secretValueMigratorMock,
            $this->createConfigMock(),
        );
    }

    protected function createPdoException(string $sqlState): PDOException
    {
        $pdoException = new PDOException(sprintf('SQLSTATE[%s]: table not found', $sqlState));
        (new ReflectionProperty(PDOException::class, 'code'))->setValue($pdoException, $sqlState);

        return $pdoException;
    }

    /**
     * @return list<string>
     */
    protected function filterWarningMessages(ConfigurationSyncResponseTransfer $configurationSyncResponseTransfer, string $needle): array
    {
        return array_values(array_filter(
            $configurationSyncResponseTransfer->getWarningMessages(),
            static fn (string $warningMessage): bool => str_contains($warningMessage, $needle),
        ));
    }

    protected function createConfigMock(): ConfigurationConfig
    {
        $sharedConfigMock = $this->createMock(SharedConfigurationConfig::class);
        $sharedConfigMock->method('getEncryptionKey')->willReturn(static::ENCRYPTION_KEY);
        $sharedConfigMock->method('getEncryptionInitVector')->willReturn(static::ENCRYPTION_INIT_VECTOR);
        $sharedConfigMock->method('getMergedSchemaFilePath')->willReturn($this->getMergedSchemaFilePath());
        $sharedConfigMock->method('getSettingsMapFilePath')->willReturn($this->getSettingsMapFilePath());
        $sharedConfigMock->method('getScopeHierarchy')->willReturn([static::SCOPE_GLOBAL => null, static::SCOPE_STORE => static::SCOPE_GLOBAL]);
        $sharedConfigMock->method('getAvailableScopes')->willReturn([static::SCOPE_GLOBAL, static::SCOPE_STORE]);

        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('getSharedModuleConfig')->willReturn($sharedConfigMock);
        $configMock->method('getCoreConfigSchemaPattens')->willReturn([$this->getRelativeSchemaPath()]);
        $configMock->method('getCoreModuleDirectoryPattens')->willReturn([]);
        $configMock->method('getProjectConfigSchemaPattens')->willReturn([]);
        $configMock->method('getMergedSchemaFilePath')->willReturn($this->getMergedSchemaFilePath());
        $configMock->method('getSettingsMapFilePath')->willReturn($this->getSettingsMapFilePath());
        $configMock->method('getAvailableScopes')->willReturn([static::SCOPE_GLOBAL, static::SCOPE_STORE]);
        $configMock->method('getScopeHierarchy')->willReturn([static::SCOPE_GLOBAL => null, static::SCOPE_STORE => static::SCOPE_GLOBAL]);
        $configMock->method('isCacheEnabled')->willReturn(false);

        return $configMock;
    }

    protected function createFactory(): ConfigurationBusinessFactory
    {
        $factory = new ConfigurationBusinessFactory();
        $factory->setConfig($this->createConfigMock());

        $this->tester->setDependency(ConfigurationDependencyProvider::PLUGINS_CONFIGURATION_VALUE_REQUEST_EXPANDER, []);
        $this->tester->setDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION, $this->tester->getLocator()->utilEncryption()->service());

        return $factory;
    }

    protected function createFacade(): ConfigurationFacade
    {
        $facade = $this->tester->getFacade();
        $facade->setFactory($this->createFactory());

        return $facade;
    }

    protected function createEncryptor(string $encryptionKey = self::ENCRYPTION_KEY): ConfigurationValueEncryptorInterface
    {
        $sharedConfigMock = $this->createMock(SharedConfigurationConfig::class);
        $sharedConfigMock->method('getEncryptionKey')->willReturn($encryptionKey);
        $sharedConfigMock->method('getEncryptionInitVector')->willReturn(static::ENCRYPTION_INIT_VECTOR);

        return new ConfigurationValueEncryptor($this->tester->getLocator()->utilEncryption()->service(), $sharedConfigMock);
    }

    protected function syncSchemaWithSecretFlag(ConfigurationFacade $facade, bool $isSecret): ConfigurationSyncResponseTransfer
    {
        $this->writeSchemaWithSecretFlag($isSecret);
        $this->resetResolverCaches();

        return $facade->syncConfigurationSchemas();
    }

    protected function writeSchemaWithSecretFlag(bool $isSecret): void
    {
        $setting = ['key' => 'api_key', 'name' => 'API key', 'type' => 'string', 'secret' => $isSecret, 'scopes' => [static::SCOPE_GLOBAL, static::SCOPE_STORE]];
        $schema = [
        'features' => [[
            'key' => 'secret_feature',
            'name' => 'Secret Feature',
            'tabs' => [[
                'key' => 'general',
                'name' => 'General',
                'groups' => [['key' => 'credentials', 'name' => 'Credentials', 'scopes' => [static::SCOPE_GLOBAL, static::SCOPE_STORE], 'settings' => [$setting]]],
            ]],
        ]]];

        file_put_contents($this->tempDirectory . '/schemas/test.configuration.yaml', Yaml::dump($schema, 10, 2));
    }

    protected function haveConfigurationValue(string $value, string $scope, ?string $scopeIdentifier = null): void
    {
        (new SpyConfigurationValue())
            ->setSettingKey(static::SETTING_KEY)
            ->setScope($scope)
            ->setScopeIdentifier($scopeIdentifier)
            ->setValue($value)
            ->save();
    }

    protected function findStoredValue(string $scope, ?string $scopeIdentifier = null): ?string
    {
        return SpyConfigurationValueQuery::create()
            ->filterBySettingKey(static::SETTING_KEY)
            ->filterByScope($scope)
            ->filterByScopeIdentifier($scopeIdentifier)
            ->findOne()
            ?->getValue();
    }

    /**
     * @param array<string, string|null> $scopes
     */
    protected function assertStoredValuesAreEncrypted(array $scopes): void
    {
        foreach ($scopes as $scope => $scopeIdentifier) {
            $storedValue = (string)$this->findStoredValue($scope, $scopeIdentifier);

            $this->assertNotSame(static::PLAIN_VALUE, $storedValue);
            $this->assertSame(static::PLAIN_VALUE, $this->createEncryptor()->decrypt($storedValue));
        }
    }

    protected function readConfigurationValue(ConfigurationFacade $facade): mixed
    {
        $this->resetResolverCaches();

        return $facade->getConfigurationValue((new ConfigurationValueRequestTransfer())->setKey(static::SETTING_KEY));
    }

    protected function resetResolverCaches(): void
    {
        (new ReflectionProperty(AbstractConfigurationValueResolver::class, 'settingCache'))->setValue(null, null);
        (new ReflectionProperty(AbstractConfigurationValueResolver::class, 'resolvedValueCache'))->setValue(null, []);
    }

    protected function getRelativeSchemaPath(): string
    {
        return ltrim(str_replace(APPLICATION_ROOT_DIR, '', $this->tempDirectory . '/schemas'), DIRECTORY_SEPARATOR);
    }

    protected function getMergedSchemaFilePath(): string
    {
        return $this->tempDirectory . '/merged-schema.php';
    }

    protected function getSettingsMapFilePath(): string
    {
        return $this->tempDirectory . '/settings-map.php';
    }
}
