<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Schema;

interface SettingOptionValueExtractorInterface
{
    /**
     * @param array<mixed> $setting
     *
     * @return list<string> Empty when the setting type is not a single-choice type or the setting has no options.
     */
    public function extractAllowedValues(array $setting): array;

    public function normalizeOptionValue(mixed $value): string;
}
