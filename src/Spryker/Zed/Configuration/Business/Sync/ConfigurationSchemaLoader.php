<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Sync;

use Generator;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Symfony\Component\Finder\Finder;

class ConfigurationSchemaLoader implements ConfigurationSchemaLoaderInterface
{
    /**
     * @var array<string>
     */
    protected const array SCHEMA_EXTENSIONS = ['*.configuration.yaml', '*.configuration.yml'];

    public function __construct(protected ConfigurationConfig $configurationConfig)
    {
    }

    /**
     * @return \Generator<\Symfony\Component\Finder\SplFileInfo>
     */
    public function loadCoreSchemas(): Generator
    {
        yield from $this->findSchemaFiles($this->configurationConfig->getCoreConfigSchemaPattens());
    }

    /**
     * @return \Generator<\Symfony\Component\Finder\SplFileInfo>
     */
    public function loadProjectSchemas(): Generator
    {
        yield from $this->findSchemaFiles($this->configurationConfig->getProjectConfigSchemaPattens());
    }

    /**
     * @param array<string> $patterns
     *
     * @return \Generator<\Symfony\Component\Finder\SplFileInfo>
     */
    protected function findSchemaFiles(array $patterns): Generator
    {
        if ($patterns === []) {
            return;
        }

        $finder = new Finder();
        $finder->files()
            ->in(APPLICATION_ROOT_DIR)
            ->path($patterns)
            ->name(static::SCHEMA_EXTENSIONS)
            ->sortByName();

        foreach ($finder as $file) {
            yield $file;
        }
    }
}
