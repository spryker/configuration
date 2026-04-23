<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Builder;

interface ConfigurationNavigationSchemaMetadataExtractorInterface
{
    /**
     * Extracts feature and tab metadata (name, description, order, status, icon, enabled) from the merged schema.
     *
     * @return array<mixed>
     */
    public function extractSchemaMetadata(): array;

    /**
     * Returns a map of feature keys that are disabled in the schema.
     *
     * @param array<mixed> $schemaMetadata
     *
     * @return array<string, true>
     */
    public function extractDisabledFeatureKeys(array $schemaMetadata): array;

    /**
     * Returns a map of disabled tab keys grouped by feature key.
     *
     * @param array<mixed> $schemaMetadata
     *
     * @return array<string, array<string, true>>
     */
    public function extractDisabledTabKeys(array $schemaMetadata): array;
}
