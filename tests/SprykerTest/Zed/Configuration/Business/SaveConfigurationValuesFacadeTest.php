<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueDeletionTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValue;
use Orm\Zed\Configuration\Persistence\SpyConfigurationValueQuery;
use Spryker\Service\UtilEncryption\UtilEncryptionServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConfig as SprykerConfigurationConfig;
use Spryker\Zed\Configuration\Business\ConfigurationBusinessFactory;
use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Configuration\ConfigurationDependencyProvider;
use SprykerTest\Zed\Configuration\ConfigurationBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Facade
 * @group SaveConfigurationValuesFacadeTest
 * Add your own group annotations below this line
 */
class SaveConfigurationValuesFacadeTest extends Unit
{
    protected ConfigurationBusinessTester $tester;

    public function testSaveConfigurationValuesSuccessfullyPersistsValidValues(): void
    {
        // Arrange
        $facade = $this->createFacade();

        $requestTransfer = (new ConfigurationValueCollectionRequestTransfer())
            ->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey('catalog:general:display:items_per_page')
                    ->setScope('global')
                    ->setValue('24'),
            );

        // Act
        $result = $facade->saveConfigurationValues($requestTransfer);

        // Assert
        $this->assertTrue($result->getIsSuccess());
        $this->assertSame(1, $result->getSavedCount());
        $this->assertCount(0, $result->getErrors());

        $savedEntity = SpyConfigurationValueQuery::create()
            ->filterBySettingKey('catalog:general:display:items_per_page')
            ->filterByScope('global')
            ->findOne();

        $this->assertNotNull($savedEntity);
        $this->assertSame('24', $savedEntity->getValue());
    }

    public function testSaveConfigurationValuesRejectsInvalidEmail(): void
    {
        // Arrange
        $facade = $this->createFacade();

        $requestTransfer = (new ConfigurationValueCollectionRequestTransfer())
            ->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey('catalog:email:notifications:sender_email')
                    ->setScope('global')
                    ->setValue('not-an-email'),
            );

        // Act
        $result = $facade->saveConfigurationValues($requestTransfer);

        // Assert
        $this->assertFalse($result->getIsSuccess());
        $this->assertSame(0, $result->getSavedCount());
        $this->assertCount(1, $result->getErrors());
        $this->assertSame('catalog:email:notifications:sender_email', $result->getErrors()[0]->getSettingKey());

        $savedEntity = SpyConfigurationValueQuery::create()
            ->filterBySettingKey('catalog:email:notifications:sender_email')
            ->findOne();

        $this->assertNull($savedEntity);
    }

    public function testSaveConfigurationValuesRejectsBlankRequiredField(): void
    {
        // Arrange
        $facade = $this->createFacade();

        $requestTransfer = (new ConfigurationValueCollectionRequestTransfer())
            ->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey('catalog:general:display:items_per_page')
                    ->setScope('global')
                    ->setValue(''),
            );

        // Act
        $result = $facade->saveConfigurationValues($requestTransfer);

        // Assert
        $this->assertFalse($result->getIsSuccess());
        $this->assertSame(0, $result->getSavedCount());
        $this->assertGreaterThan(0, $result->getErrors()->count());
    }

    public function testSaveConfigurationValuesSavesValidAndRejectsInvalidInBatch(): void
    {
        // Arrange
        $facade = $this->createFacade();

        $requestTransfer = (new ConfigurationValueCollectionRequestTransfer())
            ->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey('catalog:general:display:items_per_page')
                    ->setScope('global')
                    ->setValue('24'),
            )
            ->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey('catalog:email:notifications:sender_email')
                    ->setScope('global')
                    ->setValue('not-an-email'),
            );

        // Act
        $result = $facade->saveConfigurationValues($requestTransfer);

        // Assert
        $this->assertFalse($result->getIsSuccess());
        $this->assertSame(1, $result->getSavedCount());
        $this->assertCount(1, $result->getErrors());
    }

    public function testSaveConfigurationValuesProcessesDeletions(): void
    {
        // Arrange
        $facade = $this->createFacade();

        $this->createConfigurationValueEntity('catalog:general:display:items_per_page', 'store', '24', 'DE');

        $requestTransfer = (new ConfigurationValueCollectionRequestTransfer())
            ->addDeletionKey(
                (new ConfigurationValueDeletionTransfer())
                    ->setSettingKey('catalog:general:display:items_per_page')
                    ->setScope('store')
                    ->setScopeIdentifier('DE'),
            );

        // Act
        $result = $facade->saveConfigurationValues($requestTransfer);

        // Assert
        $this->assertTrue($result->getIsSuccess());
        $this->assertSame(1, $result->getSavedCount());

        $deletedEntity = SpyConfigurationValueQuery::create()
            ->filterBySettingKey('catalog:general:display:items_per_page')
            ->filterByScope('store')
            ->filterByScopeIdentifier('DE')
            ->findOne();

        $this->assertNull($deletedEntity);
    }

    public function testSaveConfigurationValuesHandlesEmptyRequest(): void
    {
        // Arrange
        $facade = $this->createFacade();
        $requestTransfer = new ConfigurationValueCollectionRequestTransfer();

        // Act
        $result = $facade->saveConfigurationValues($requestTransfer);

        // Assert
        $this->assertTrue($result->getIsSuccess());
        $this->assertSame(0, $result->getSavedCount());
    }

    public function testSaveConfigurationValuesSkipsValidationForUnknownSetting(): void
    {
        // Arrange
        $facade = $this->createFacade();

        $requestTransfer = (new ConfigurationValueCollectionRequestTransfer())
            ->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey('unknown:feature:group:setting')
                    ->setScope('global')
                    ->setValue('any-value'),
            );

        // Act
        $result = $facade->saveConfigurationValues($requestTransfer);

        // Assert
        $this->assertTrue($result->getIsSuccess());
        $this->assertSame(1, $result->getSavedCount());

        $savedEntity = SpyConfigurationValueQuery::create()
            ->filterBySettingKey('unknown:feature:group:setting')
            ->filterByScope('global')
            ->findOne();

        $this->assertNotNull($savedEntity);
        $this->assertSame('any-value', $savedEntity->getValue());
    }

    protected function createFacade(): ConfigurationFacade
    {
        $schemaFilePath = __DIR__ . '/../_data/test-schema.php';

        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('getMergedSchemaFilePath')->willReturn($schemaFilePath);
        $configMock->method('getCoreConfigSchemaPattens')->willReturn([]);
        $configMock->method('getProjectConfigSchemaPattens')->willReturn([]);
        $configMock->method('getSharedModuleConfig')->willReturn($this->createSharedConfigMock($schemaFilePath));
        $configMock->method('isCacheEnabled')->willReturn(false);

        $factory = new ConfigurationBusinessFactory();
        $factory->setConfig($configMock);

        $this->tester->setDependency(ConfigurationDependencyProvider::PLUGINS_CONFIGURATION_VALUE_PRE_SAVE, []);
        $this->tester->setDependency(ConfigurationDependencyProvider::PLUGINS_CONFIGURATION_VALUE_POST_SAVE, []);
        $this->tester->setDependency(ConfigurationDependencyProvider::SERVICE_UTIL_ENCRYPTION, $this->createMock(UtilEncryptionServiceInterface::class));

        $facade = $this->tester->getFacade();
        $facade->setFactory($factory);

        return $facade;
    }

    protected function createSharedConfigMock(string $schemaFilePath): SprykerConfigurationConfig
    {
        $sharedConfigMock = $this->createMock(SprykerConfigurationConfig::class);
        $sharedConfigMock->method('getMergedSchemaFilePath')->willReturn($schemaFilePath);
        $sharedConfigMock->method('getSettingsMapFilePath')->willReturn(__DIR__ . '/../_data/test-settings-map.php');

        return $sharedConfigMock;
    }

    protected function createConfigurationValueEntity(string $settingKey, string $scope, string $value, ?string $scopeIdentifier = null): void
    {
        $entity = new SpyConfigurationValue();
        $entity->setSettingKey($settingKey);
        $entity->setScope($scope);
        $entity->setScopeIdentifier($scopeIdentifier);
        $entity->setValue($value);
        $entity->save();
    }
}
