<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Schema;

interface SchemaParserInterface
{
    /**
     * @param string $yamlContent
     *
     * @return array<mixed>
     */
    public function parse(string $yamlContent): array;

    /**
     * @param array<mixed> $parsedYaml
     *
     * @return array<mixed>
     */
    public function normalize(array $parsedYaml): array;

    /**
     * @param array<mixed> $schema
     *
     * @return bool
     */
    public function validate(array $schema): bool;

    /**
     * @return array<string>
     */
    public function getValidationErrors(): array;
}
