<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Logger;

use Generated\Shared\Transfer\AuditLoggerConfigCriteriaTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Shared\Log\AuditLoggerTrait;

class ConfigurationAuditLogger implements ConfigurationAuditLoggerInterface
{
    use AuditLoggerTrait;

    /**
     * @uses \Spryker\Shared\Log\LogConfig::AUDIT_LOGGER_CHANNEL_NAME_SECURITY
     */
    protected const string AUDIT_LOGGER_CHANNEL_NAME_SECURITY = 'security';

    /**
     * @uses \Spryker\Shared\Log\Handler\TagFilterBufferedStreamHandler::RECORD_KEY_CONTEXT_TAGS
     */
    protected const string AUDIT_LOGGER_RECORD_KEY_CONTEXT_TAGS = 'tags';

    protected const string LOG_MESSAGE_VALUE_UPDATED = 'Configuration value updated';

    protected const string LOG_TAG_VALUE_UPDATED = 'configuration_value_updated';

    protected const string LOG_MESSAGE_VALUE_SAVE_FAILED = 'Configuration value save failed';

    protected const string LOG_TAG_VALUE_SAVE_FAILED = 'configuration_value_save_failed';

    protected const string CONTEXT_KEY_SETTING_KEY = 'setting_key';

    protected const string CONTEXT_KEY_SETTING_KEYS = 'setting_keys';

    protected const string CONTEXT_KEY_SCOPE = 'scope';

    protected const string CONTEXT_KEY_SCOPE_IDENTIFIER = 'scope_identifier';

    protected const string CONTEXT_KEY_ERRORS = 'errors';

    public function logConfigurationValueSaved(ConfigurationValueTransfer $configurationValueTransfer): void
    {
        $this->getAuditLogger(
            (new AuditLoggerConfigCriteriaTransfer())->setChannelName(static::AUDIT_LOGGER_CHANNEL_NAME_SECURITY),
        )->info(static::LOG_MESSAGE_VALUE_UPDATED, [
            static::AUDIT_LOGGER_RECORD_KEY_CONTEXT_TAGS => [static::LOG_TAG_VALUE_UPDATED],
            static::CONTEXT_KEY_SETTING_KEY => $configurationValueTransfer->getSettingKey(),
            static::CONTEXT_KEY_SCOPE => $configurationValueTransfer->getScope(),
            static::CONTEXT_KEY_SCOPE_IDENTIFIER => $configurationValueTransfer->getScopeIdentifier(),
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<\Generated\Shared\Transfer\ConfigurationErrorTransfer> $errors
     */
    public function logConfigurationValueSaveFailed(
        ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer,
        array $errors,
    ): void {
        $settingKeys = [];

        foreach ($configurationValueCollectionRequestTransfer->getConfigurationValues() as $configurationValueTransfer) {
            $settingKeys[] = $configurationValueTransfer->getSettingKey();
        }

        $errorMessages = [];

        foreach ($errors as $error) {
            $errorMessages[] = sprintf('%s: %s', $error->getSettingKey(), $error->getMessage());
        }

        $this->getAuditLogger(
            (new AuditLoggerConfigCriteriaTransfer())->setChannelName(static::AUDIT_LOGGER_CHANNEL_NAME_SECURITY),
        )->info(static::LOG_MESSAGE_VALUE_SAVE_FAILED, [
            static::AUDIT_LOGGER_RECORD_KEY_CONTEXT_TAGS => [static::LOG_TAG_VALUE_SAVE_FAILED],
            static::CONTEXT_KEY_SETTING_KEYS => $settingKeys,
            static::CONTEXT_KEY_ERRORS => $errorMessages,
        ]);
    }
}
