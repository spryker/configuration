<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Loader;

interface ConfigurationSchemaMetadataExtractorInterface
{
    /**
     * Extracts per-group metadata (name, description, order, status) for a given feature tab.
     *
     * @return array<string, array<string, mixed>>
     */
    public function extractGroupMetadata(string $featureKey, string $tabKey): array;

    /**
     * Extracts per-setting override records keyed by compound setting key for a given feature tab.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    public function extractSettingOverrides(string $featureKey, string $tabKey): array;
}
