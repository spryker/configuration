<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Schema;

use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReaderInterface;

class ConfigurationSchemaProvider implements ConfigurationSchemaProviderInterface
{
    public function __construct(
        protected ConfigurationSchemaReaderInterface $schemaReader,
        protected ConfigurationSchemaSettingsMapperInterface $settingsMapper,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function getMergedSchema(): array
    {
        $schema = $this->schemaReader->getMergedSchema();

        if ($schema) {
            return $schema;
        }

        return [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getSettingsMap(): array
    {
        return $this->schemaReader->getSettingsMap();
    }

    /**
     * @inheritDoc
     */
    public function getAllSettingTransfers(): array
    {
        return $this->settingsMapper->mapSchemaToSettingTransfers($this->getMergedSchema());
    }
}
