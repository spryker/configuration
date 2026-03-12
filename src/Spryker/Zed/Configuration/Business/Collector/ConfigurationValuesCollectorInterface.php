<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Collector;

use Generated\Shared\Transfer\ConfigurationSettingValueCollectionTransfer;
use Generated\Shared\Transfer\ConfigurationSettingValuesCriteriaTransfer;

interface ConfigurationValuesCollectorInterface
{
    /**
     * Specification:
     * - Fetches saved values for all keys in the criteria at the exact (scope, scopeIdentifier).
     * - Resolves inherited values by walking up the scope hierarchy (one bulk query per level).
     * - Returns direct values and inherited values as separate key→value maps.
     *
     * @param \Generated\Shared\Transfer\ConfigurationSettingValuesCriteriaTransfer $criteria
     *
     * @return \Generated\Shared\Transfer\ConfigurationSettingValueCollectionTransfer
     */
    public function collectValues(ConfigurationSettingValuesCriteriaTransfer $criteria): ConfigurationSettingValueCollectionTransfer;
}
