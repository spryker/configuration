<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Sync;

use Generator;

interface ConfigurationSchemaLoaderInterface
{
    /**
     * @return \Generator<\Symfony\Component\Finder\SplFileInfo>
     */
    public function loadCoreSchemas(): Generator;

    /**
     * @return \Generator<\Symfony\Component\Finder\SplFileInfo>
     */
    public function loadProjectSchemas(): Generator;
}
