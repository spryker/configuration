<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Validator;

interface ConfigurationSchemaKeyValidatorInterface
{
    /**
     * @param array<mixed> $schema
     *
     * @return array<string>
     */
    public function validate(array $schema): array;
}
