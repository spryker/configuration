<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Validator;

use Symfony\Component\Validator\Constraint;

interface ConfigurationConstraintMapperInterface
{
    /**
     * Maps a raw constraint definition (from YAML schema) to a Symfony Constraint object.
     * Supports both short names (e.g., "required") and fully qualified class names.
     *
     * For `min` and `max` constraints, the setting type determines behavior:
     * - String/text types: validates string **length** (Symfony Length constraint).
     * - Numeric types (integer, float): validates numeric **value** (GreaterThanOrEqual / LessThanOrEqual).
     *
     * @param array<string, mixed> $constraintDefinition
     * @param string|null $settingType
     *
     * @return \Symfony\Component\Validator\Constraint
     */
    public function mapToSymfonyConstraint(array $constraintDefinition, ?string $settingType = null): Constraint;
}
