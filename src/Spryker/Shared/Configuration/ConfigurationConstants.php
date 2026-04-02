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
    public const string SCOPE_GLOBAL = 'global';

    public const string VALUE_TYPE_STRING = 'string';

    public const string VALUE_TYPE_INTEGER = 'integer';

    public const string VALUE_TYPE_BOOLEAN = 'boolean';

    public const string VALUE_TYPE_JSON = 'json';

    public const string CONSTRAINT_TYPE_REQUIRED = 'required';

    public const string CONSTRAINT_TYPE_MIN = 'min';

    public const string CONSTRAINT_TYPE_MAX = 'max';

    public const string CONSTRAINT_TYPE_REGEX = 'regex';

    public const string CONSTRAINT_TYPE_RANGE = 'range';

    public const string CONSTRAINT_TYPE_EMAIL = 'email';

    public const string CONSTRAINT_TYPE_URL = 'url';

    public const string CONSTRAINT_TYPE_CHOICE = 'choice';

    public const string CONSTRAINT_TYPE_LENGTH = 'length';

    public const string ENTITY_SPY_CONFIGURATION_VALUE_CREATE = 'Entity.spy_configuration_value.create';

    public const string ENTITY_SPY_CONFIGURATION_VALUE_UPDATE = 'Entity.spy_configuration_value.update';

    public const string ENTITY_SPY_CONFIGURATION_VALUE_DELETE = 'Entity.spy_configuration_value.delete';

    public const string STORAGE_KEY_PREFIX = 'configuration';

    public const string STORAGE_KEY_SEPARATOR = ':';

    public const string VALUE_TYPE_FLOAT = 'float';

    public const string VALUE_TYPE_COLOR = 'color';

    public const string VALUE_TYPE_FILE = 'file';

    public const string VALUE_TYPE_TEXT = 'text';

    public const string VALUE_TYPE_SELECT = 'select';

    public const string VALUE_TYPE_MULTISELECT = 'multiselect';

    public const string VALUE_TYPE_RADIO = 'radio';

    public const string BOOLEAN_STRING_FALSE = 'false';

    public const string SCHEMA_KEY_FEATURES = 'features';

    public const string SCHEMA_KEY_TABS = 'tabs';

    public const string SCHEMA_KEY_GROUPS = 'groups';

    public const string SCHEMA_KEY_SETTINGS = 'settings';

    public const string SCHEMA_KEY_KEY = 'key';

    public const string SCHEMA_KEY_NAME = 'name';

    public const string SCHEMA_KEY_TYPE = 'type';

    public const string SCHEMA_KEY_DESCRIPTION = 'description';

    public const string SCHEMA_KEY_ORDER = 'order';

    public const string SCHEMA_KEY_ENABLED = 'enabled';

    public const string SCHEMA_KEY_ICON = 'icon';

    public const string SCHEMA_KEY_DEFAULT_VALUE = 'default_value';

    public const string SCHEMA_KEY_SECRET = 'secret';

    public const string SCHEMA_KEY_STOREFRONT = 'storefront';

    public const string SCHEMA_KEY_SCOPES = 'scopes';

    public const string SCHEMA_KEY_OPTIONS = 'options';

    public const string SCHEMA_KEY_CONSTRAINTS = 'constraints';

    public const string SCHEMA_KEY_DEPENDENCIES = 'dependencies';

    public const string SCHEMA_KEY_HELP_TEXT = 'help_text';

    public const string SCHEMA_KEY_FILE_UPLOAD = 'file_upload';

    public const string SCHEMA_KEY_PLACEHOLDER = 'placeholder';

    public const string SCHEMA_KEY_NOTE = 'note';

    public const string SCHEMA_KEY_TEMPLATE = 'template';

    public const string SCHEMA_KEY_SANITIZE_XSS = 'sanitize_xss';

    public const string SCHEMA_KEY_SANITIZE_XSS_ALLOW_SAFE_ELEMENTS = 'allow_safe_elements';

    public const string SCHEMA_KEY_SANITIZE_XSS_ALLOW_STATIC_ELEMENTS = 'allow_static_elements';

    public const string SCHEMA_KEY_SANITIZE_XSS_ALLOW_ELEMENTS = 'allow_elements';

    public const string SCHEMA_KEY_SANITIZE_XSS_ALLOW_ATTRIBUTES = 'allow_attributes';

    public const string SCHEMA_KEY_SANITIZE_XSS_ALLOW_RELATIVE_LINKS = 'allow_relative_links';

    public const string SCHEMA_KEY_SANITIZE_XSS_ALLOWED_LINK_SCHEMES = 'allowed_link_schemes';

    public const string SCHEMA_KEY_SANITIZE_XSS_ALLOWED_LINK_HOSTS = 'allowed_link_hosts';

    public const string SCHEMA_KEY_SANITIZE_XSS_FORCE_HTTPS_URLS = 'force_https_urls';

    public const string CONSTRAINT_KEY_TYPE = 'type';

    public const string CONSTRAINT_KEY_MESSAGE = 'message';

    public const string CONSTRAINT_KEY_OPTIONS = 'options';

    public const string CONSTRAINT_OPTION_MIN = 'min';

    public const string CONSTRAINT_OPTION_MAX = 'max';

    public const string CONSTRAINT_OPTION_PATTERN = 'pattern';

    public const string CONSTRAINT_OPTION_CHOICES = 'choices';

    /**
     * Specification:
     * - Defines queue name as used for processing configuration storage synchronization.
     *
     * @api
     *
     * @var string
     */
    public const QUEUE_NAME_SYNC_CONFIGURATION = 'sync.storage.configuration';

    /**
     * Specification:
     * - Encryption key used for encrypting/decrypting secret configuration values.
     *
     * @api
     *
     * @var string
     */
    public const string ENCRYPTION_KEY = 'CONFIGURATION:ENCRYPTION_KEY';

    /**
     * Specification:
     * - Initialization vector used for encrypting/decrypting secret configuration values.
     *
     * @api
     *
     * @var string
     */
    public const string ENCRYPTION_INIT_VECTOR = 'CONFIGURATION:ENCRYPTION_INIT_VECTOR';
}
