<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Resolver;

interface ConfigurationDataObjectResolverInterface
{
    /**
     * @param array<string, mixed> $setting
     *
     * @return array<string, mixed>
     */
    public function resolve(array $setting): array;
}
