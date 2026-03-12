<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Schema;

interface SchemaMergerInterface
{
    /**
     * @param array<mixed> $coreSchema
     * @param array<mixed> $projectSchema
     *
     * @return array<mixed>
     */
    public function merge(array $coreSchema, array $projectSchema): array;
}
