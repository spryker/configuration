<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration;

/**
 * The keys of the file `configuration:export` writes and `configuration:restore` reads back. The two
 * commands are a producer and a consumer of the same file with nothing else linking them, so the
 * names live here rather than once on each side.
 */
interface ConfigurationExportFileFormat
{
    public const string KEY_VALUES = 'values';

    public const string KEY_SETTING_KEY = 'setting_key';

    public const string KEY_SCOPE = 'scope';

    public const string KEY_SCOPE_IDENTIFIER = 'scope_identifier';

    public const string KEY_VALUE = 'value';
}
