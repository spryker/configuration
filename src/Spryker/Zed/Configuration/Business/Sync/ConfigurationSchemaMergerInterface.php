<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Sync;

use Generator;

interface ConfigurationSchemaMergerInterface
{
    /**
     * @param \Generator<\Symfony\Component\Finder\SplFileInfo> $coreSchemaFiles
     * @param \Generator<\Symfony\Component\Finder\SplFileInfo> $projectSchemaFiles
     *
     * @return array<mixed>
     */
    public function merge(Generator $coreSchemaFiles, Generator $projectSchemaFiles): array;
}
