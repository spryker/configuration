<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface ConfigurationSchemaConstants
{
    /**
     * @api
     */
    public const string VALUE_TYPE_STRING = 'string';

    /**
     * @api
     */
    public const string VALUE_TYPE_INTEGER = 'integer';

    /**
     * @api
     */
    public const string VALUE_TYPE_BOOLEAN = 'boolean';

    /**
     * @api
     */
    public const string VALUE_TYPE_JSON = 'json';

    /**
     * @api
     */
    public const string VALUE_TYPE_FLOAT = 'float';

    /**
     * @api
     */
    public const string VALUE_TYPE_COLOR = 'color';

    /**
     * @api
     */
    public const string VALUE_TYPE_TEXT = 'text';

    /**
     * @api
     */
    public const string VALUE_TYPE_SELECT = 'select';

    /**
     * @api
     */
    public const string VALUE_TYPE_MULTISELECT = 'multiselect';

    /**
     * @api
     */
    public const string VALUE_TYPE_RADIO = 'radio';

    /**
     * @api
     */
    public const string VALUE_TYPE_FILE = 'file';

    /**
     * @api
     */
    public const string BOOLEAN_STRING_FALSE = 'false';

    /**
     * @api
     */
    public const string CONSTRAINT_TYPE_REQUIRED = 'required';

    /**
     * @api
     */
    public const string CONSTRAINT_TYPE_MIN = 'min';

    /**
     * @api
     */
    public const string CONSTRAINT_TYPE_MAX = 'max';

    /**
     * @api
     */
    public const string CONSTRAINT_TYPE_REGEX = 'regex';

    /**
     * @api
     */
    public const string CONSTRAINT_TYPE_RANGE = 'range';

    /**
     * @api
     */
    public const string CONSTRAINT_TYPE_EMAIL = 'email';

    /**
     * @api
     */
    public const string CONSTRAINT_TYPE_URL = 'url';

    /**
     * @api
     */
    public const string CONSTRAINT_TYPE_CHOICE = 'choice';

    /**
     * @api
     */
    public const string CONSTRAINT_TYPE_LENGTH = 'length';

    /**
     * @api
     */
    public const string CONSTRAINT_KEY_TYPE = 'type';

    /**
     * @api
     */
    public const string CONSTRAINT_KEY_MESSAGE = 'message';

    /**
     * @api
     */
    public const string CONSTRAINT_KEY_OPTIONS = 'options';

    /**
     * @api
     */
    public const string CONSTRAINT_OPTION_MIN = 'min';

    /**
     * @api
     */
    public const string CONSTRAINT_OPTION_MAX = 'max';

    /**
     * @api
     */
    public const string CONSTRAINT_OPTION_PATTERN = 'pattern';

    /**
     * @api
     */
    public const string CONSTRAINT_OPTION_CHOICES = 'choices';

    /**
     * @api
     */
    public const string SCHEMA_KEY_FEATURES = 'features';

    /**
     * @api
     */
    public const string SCHEMA_KEY_TABS = 'tabs';

    /**
     * @api
     */
    public const string SCHEMA_KEY_GROUPS = 'groups';

    /**
     * @api
     */
    public const string SCHEMA_KEY_SETTINGS = 'settings';

    /**
     * @api
     */
    public const string SCHEMA_KEY_KEY = 'key';

    /**
     * @api
     */
    public const string SCHEMA_KEY_NAME = 'name';

    /**
     * @api
     */
    public const string SCHEMA_KEY_TYPE = 'type';

    /**
     * @api
     */
    public const string SCHEMA_KEY_DESCRIPTION = 'description';

    /**
     * @api
     */
    public const string SCHEMA_KEY_ORDER = 'order';

    /**
     * @api
     */
    public const string SCHEMA_KEY_ENABLED = 'enabled';

    /**
     * @api
     */
    public const string SCHEMA_KEY_ICON = 'icon';

    /**
     * @api
     */
    public const string SCHEMA_KEY_DEFAULT_VALUE = 'default_value';

    /**
     * @api
     */
    public const string SCHEMA_KEY_SECRET = 'secret';

    /**
     * @api
     */
    public const string SCHEMA_KEY_STOREFRONT = 'storefront';

    /**
     * @api
     */
    public const string SCHEMA_KEY_SCOPES = 'scopes';

    /**
     * @api
     */
    public const string SCHEMA_KEY_OPTIONS = 'options';

    /**
     * @api
     */
    public const string SCHEMA_KEY_CONSTRAINTS = 'constraints';

    /**
     * @api
     */
    public const string SCHEMA_KEY_DEPENDENCIES = 'dependencies';

    /**
     * @api
     */
    public const string SCHEMA_KEY_HELP_TEXT = 'help_text';

    public const string SCHEMA_KEY_FILE_UPLOAD = 'file_upload';

    public const string SCHEMA_KEY_PLACEHOLDER = 'placeholder';

    /**
     * @api
     */
    public const string SCHEMA_KEY_NOTE = 'note';

    /**
     * @api
     */
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

    /**
     * @api
     */
    public const string SCHEMA_KEY_DATA_OBJECT = 'data_object';

    /**
     * @api
     */
    public const string SCHEMA_KEY_STATUS = 'status';

    /**
     * @api
     */
    public const string SCHEMA_KEY_OVERRIDES = 'overrides';

    /**
     * @api
     */
    public const string STATUS_BETA = 'beta';

    /**
     * @api
     */
    public const string STATUS_EARLY_ACCESS = 'early_access';

    /**
     * @api
     */
    public const string OVERRIDE_KEY_CORE_CLASS = 'coreClass';

    /**
     * @api
     */
    public const string OVERRIDE_KEY_CORE_METHOD = 'coreMethod';

    /**
     * @api
     */
    public const string OVERRIDE_KEY_PROJECT_CLASS = 'projectClass';

    /**
     * @api
     */
    public const string OVERRIDE_KEY_PROJECT_METHOD = 'projectMethod';
}
