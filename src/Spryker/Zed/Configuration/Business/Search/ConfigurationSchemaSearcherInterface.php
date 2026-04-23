<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Search;

interface ConfigurationSchemaSearcherInterface
{
    /**
     * Searches the merged configuration schema for features, tabs, groups, and settings matching the given term.
     * Translates names and descriptions to the current Backoffice locale before matching.
     * Filters settings by scope availability before matching.
     * Matches against translated name and description at feature, tab, and group levels.
     * Matches against translated name, description, and raw key at setting level.
     * A tab is included if it or any of its scope-available descendant groups/settings match.
     * A feature is included if it or any of its descendant tabs match.
     * Returns an associative array keyed by feature key, with values being arrays of matching tab keys.
     *
     * @return array<string, array<string>>
     */
    public function search(string $term, string $scope): array;
}
