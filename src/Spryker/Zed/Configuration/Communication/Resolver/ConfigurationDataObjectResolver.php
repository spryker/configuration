<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Resolver;

use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationSettingDataProviderPluginInterface;

class ConfigurationDataObjectResolver implements ConfigurationDataObjectResolverInterface
{
    public function resolve(array $setting): array
    {
        $dataObjectClassName = $setting[ConfigurationSchemaConstants::SCHEMA_KEY_DATA_OBJECT] ?? null;

        if ($dataObjectClassName === null) {
            return $setting;
        }

        if (!class_exists($dataObjectClassName)) {
            return $setting;
        }

        $dataObject = new $dataObjectClassName();

        if (!$dataObject instanceof ConfigurationSettingDataProviderPluginInterface) {
            return $setting;
        }

        return array_merge($setting, $dataObject->getData());
    }
}
