<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Sync;

use Generator;
use Spryker\Shared\Configuration\Schema\SchemaMergerInterface;
use Spryker\Shared\Configuration\Schema\SchemaParserInterface;
use Symfony\Component\Finder\SplFileInfo;

class ConfigurationSchemaMerger implements ConfigurationSchemaMergerInterface
{
    public function __construct(
        protected SchemaParserInterface $schemaParser,
        protected SchemaMergerInterface $schemaMerger,
    ) {
    }

    /**
     * @param \Generator<\Symfony\Component\Finder\SplFileInfo> $coreSchemaFiles
     * @param \Generator<\Symfony\Component\Finder\SplFileInfo> $projectSchemaFiles
     *
     * @return array<mixed>
     */
    public function merge(Generator $coreSchemaFiles, Generator $projectSchemaFiles): array
    {
        $coreSchema = $this->mergeSchemaFiles($coreSchemaFiles);
        $projectSchema = $this->mergeSchemaFiles($projectSchemaFiles);

        return $this->schemaMerger->merge($coreSchema, $projectSchema);
    }

    /**
     * @param \Generator<\Symfony\Component\Finder\SplFileInfo> $schemaFiles
     *
     * @return array<mixed>
     */
    protected function mergeSchemaFiles(Generator $schemaFiles): array
    {
        $mergedSchema = [];

        foreach ($schemaFiles as $file) {
            $parsedSchema = $this->parseSchemaFile($file);
            $normalizedSchema = $this->schemaParser->normalize($parsedSchema);

            if ($mergedSchema === []) {
                $mergedSchema = $normalizedSchema;

                continue;
            }

            $mergedSchema = $this->schemaMerger->merge($mergedSchema, $normalizedSchema);
        }

        return $mergedSchema;
    }

    /**
     * @return array<mixed>
     */
    protected function parseSchemaFile(SplFileInfo $file): array
    {
        return $this->schemaParser->parse($file->getContents());
    }
}
