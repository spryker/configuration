<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business\Writer;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptorInterface;
use Spryker\Zed\Configuration\Business\Cache\ConfigurationCacheManagerInterface;
use Spryker\Zed\Configuration\Business\Logger\ConfigurationAuditLoggerInterface;
use Spryker\Zed\Configuration\Business\Sanitizer\ConfigurationValueSanitizerInterface;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;
use Spryker\Zed\Configuration\Business\Validator\ConfigurationValueValidatorInterface;
use Spryker\Zed\Configuration\Business\Writer\ConfigurationValueWriter;
use Spryker\Zed\Configuration\Persistence\ConfigurationEntityManagerInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Writer
 * @group ConfigurationValueWriterTest
 * Add your own group annotations below this line
 */
class ConfigurationValueWriterTest extends Unit
{
    protected const string SECRET_SETTING_KEY = 'integrations:x:connection:api_key';

    protected const string SECRET_VALUE = 'sk-must-never-be-stored-in-clear';

    /**
     * `configuration:sync` generates the settings map, and that map is the only thing that says which
     * settings are secret. When it has not been generated the map reads as empty, which is
     * indistinguishable from a schema declaring no secrets - so every key would miss the lookup in
     * encryptIfSecret() and be persisted in clear, with nothing failing and nothing logged.
     */
    public function testRefusesToSaveAnythingWhenTheSchemaHasNotBeenGenerated(): void
    {
        // Arrange
        $configurationEntityManagerMock = $this->createMock(ConfigurationEntityManagerInterface::class);
        $configurationEntityManagerMock->expects($this->never())->method('saveConfigurationValue');

        $configurationValueWriter = $this->createWriter([], $configurationEntityManagerMock);

        // Act
        $configurationValueCollectionResponseTransfer = $configurationValueWriter->saveConfigurationValues(
            $this->createRequest(),
        );

        // Assert
        $this->assertFalse($configurationValueCollectionResponseTransfer->getIsSuccess());
        $this->assertSame(0, $configurationValueCollectionResponseTransfer->getSavedCount());
    }

    public function testTheRefusalNamesTheMissingSchemaAndNeverQuotesTheValue(): void
    {
        // Arrange
        $configurationValueWriter = $this->createWriter([]);

        // Act
        $configurationValueCollectionResponseTransfer = $configurationValueWriter->saveConfigurationValues(
            $this->createRequest(),
        );

        // Assert
        $messages = [];

        foreach ($configurationValueCollectionResponseTransfer->getErrors() as $configurationErrorTransfer) {
            $messages[] = (string)$configurationErrorTransfer->getMessage();
        }

        $joinedMessages = implode(' ', $messages);
        $this->assertStringContainsString('configuration:sync', $joinedMessages);
        $this->assertStringNotContainsString(static::SECRET_VALUE, $joinedMessages);
    }

    protected function createRequest(): ConfigurationValueCollectionRequestTransfer
    {
        return (new ConfigurationValueCollectionRequestTransfer())->addConfigurationValue(
            (new ConfigurationValueTransfer())
                ->setSettingKey(static::SECRET_SETTING_KEY)
                ->setScope(ConfigurationConstants::SCOPE_GLOBAL)
                ->setValue(static::SECRET_VALUE),
        );
    }

    /**
     * @param array<string, mixed> $settingsMap
     */
    protected function createWriter(array $settingsMap, ?ConfigurationEntityManagerInterface $configurationEntityManager = null): ConfigurationValueWriter
    {
        $configurationSchemaProviderMock = $this->createMock(ConfigurationSchemaProviderInterface::class);
        $configurationSchemaProviderMock->method('getSettingsMap')->willReturn($settingsMap);

        return new ConfigurationValueWriter(
            $configurationEntityManager ?? $this->createMock(ConfigurationEntityManagerInterface::class),
            $this->createMock(ConfigurationCacheManagerInterface::class),
            $this->createMock(ConfigurationValueValidatorInterface::class),
            $this->createMock(ConfigurationValueEncryptorInterface::class),
            $configurationSchemaProviderMock,
            $this->createMock(ConfigurationAuditLoggerInterface::class),
            [],
            [],
            $this->createMock(ConfigurationValueSanitizerInterface::class),
        );
    }
}
