<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface ConfigurationConstants
{
    /**
     * @api
     */
    public const string SCOPE_GLOBAL = 'global';

    /**
     * Specification:
     * - These events will be used for spy_configuration_value creating.
     *
     * @api
     */
    public const string ENTITY_SPY_CONFIGURATION_VALUE_CREATE = 'Entity.spy_configuration_value.create';

    /**
     * Specification:
     * - These events will be used for spy_configuration_value update.
     *
     * @api
     */
    public const string ENTITY_SPY_CONFIGURATION_VALUE_UPDATE = 'Entity.spy_configuration_value.update';

    /**
     * Specification:
     * - These events will be used for spy_configuration_value deleting.
     *
     * @api
     */
    public const string ENTITY_SPY_CONFIGURATION_VALUE_DELETE = 'Entity.spy_configuration_value.delete';

    /**
     * Specification:
     * - These events will be used for ConfigurationValue publishing.
     *
     * @api
     */
    public const string CONFIGURATION_VALUE_PUBLISH_WRITE = 'Configuration.configuration_value.publish';

    /**
     * @api
     */
    public const string STORAGE_KEY_PREFIX = 'configuration';

    /**
     * @api
     */
    public const string STORAGE_KEY_SEPARATOR = ':';

    /**
     * @api
     */
    public const string CACHE_KEY_PREFIX = 'config';

    /**
     * Specification:
     * - Defines queue name as used for processing configuration storage synchronization.
     *
     * @api
     */
    public const string QUEUE_NAME_SYNC_CONFIGURATION = 'sync.storage.configuration';

    /**
     * Specification:
     * - Encryption key used for encrypting/decrypting secret configuration values.
     *
     * @api
     */
    public const string ENCRYPTION_KEY = 'CONFIGURATION:ENCRYPTION_KEY';

    /**
     * Specification:
     * - Initialization vector used for encrypting/decrypting secret configuration values.
     *
     * @api
     */
    public const string ENCRYPTION_INIT_VECTOR = 'CONFIGURATION:ENCRYPTION_INIT_VECTOR';
}
