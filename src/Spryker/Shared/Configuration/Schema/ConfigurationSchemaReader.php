<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Schema;

use Spryker\Shared\Configuration\ConfigurationConfig;

class ConfigurationSchemaReader implements ConfigurationSchemaReaderInterface
{
    public function __construct(protected ConfigurationConfig $config)
    {
    }

    /**
     * @return array<mixed>
     */
    public function getMergedSchema(): array
    {
        $filePath = $this->config->getMergedSchemaFilePath();

        if (!file_exists($filePath)) {
            return [];
        }

        return require $filePath;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getSettingsMap(): array
    {
        $filePath = $this->config->getSettingsMapFilePath();

        if (!file_exists($filePath)) {
            return [];
        }

        return require $filePath;
    }
}
