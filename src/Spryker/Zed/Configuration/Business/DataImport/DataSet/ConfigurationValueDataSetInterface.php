<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\DataImport\DataSet;

interface ConfigurationValueDataSetInterface
{
    public const string COLUMN_SETTING_KEY = 'setting_key';

    public const string COLUMN_SCOPE = 'scope';

    public const string COLUMN_SCOPE_IDENTIFIER = 'scope_identifier';

    public const string COLUMN_VALUE = 'value';

    public const string COLUMN_IS_SKIPPED = 'is_skipped';
}
