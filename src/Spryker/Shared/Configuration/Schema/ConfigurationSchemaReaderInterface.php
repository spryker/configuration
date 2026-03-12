<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Schema;

interface ConfigurationSchemaReaderInterface
{
    /**
     * @return array<mixed>
     */
    public function getMergedSchema(): array;

    /**
     * Returns a flat associative array keyed by compound setting key,
     * where each value contains only runtime-critical metadata:
     * type, default_value, secret, storefront, scopes, constraints.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getSettingsMap(): array;
}
