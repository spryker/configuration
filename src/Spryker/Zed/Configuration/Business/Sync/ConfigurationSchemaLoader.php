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
        yield from $this->findSchemaFiles($this->resolveSchemaDirectories(
            $this->configurationConfig->getCoreConfigSchemaPattens(),
            $this->configurationConfig->getCoreModuleDirectoryPattens(),
        ));
    }

    /**
     * @return \Generator<\Symfony\Component\Finder\SplFileInfo>
     */
    public function loadProjectSchemas(): Generator
    {
        yield from $this->findSchemaFiles($this->resolveSchemaDirectories(
            $this->configurationConfig->getProjectConfigSchemaPattens(),
            [],
        ));
    }

    /**
     * @param array<string> $schemaPattens
     * @param array<string> $moduleDirectoryPattens
     *
     * @return array<string>
     */
    protected function resolveSchemaDirectories(array $schemaPattens, array $moduleDirectoryPattens): array
    {
        if ($moduleDirectoryPattens === []) {
            return $this->globDirectories($schemaPattens);
        }

        $globPattens = [];
        foreach ($schemaPattens as $schemaPatten) {
            foreach ($moduleDirectoryPattens as $moduleDirectoryPatten) {
                $globPattens[] = $moduleDirectoryPatten . DIRECTORY_SEPARATOR . $schemaPatten;
            }
        }

        return $this->globDirectories($globPattens);
    }

    /**
     * @param array<string> $globPattens
     *
     * @return array<string>
     */
    protected function globDirectories(array $globPattens): array
    {
        $directories = [];
        foreach ($globPattens as $globPatten) {
            $matches = glob(APPLICATION_ROOT_DIR . DIRECTORY_SEPARATOR . $globPatten, GLOB_ONLYDIR);
            if ($matches) {
                $directories = array_merge($directories, $matches);
            }
        }

        // Overlapping pattens resolve to the same directory; Finder would re-read its files once per duplicate.
        return array_values(array_unique($directories));
    }

    /**
     * @param array<string> $directories
     *
     * @return \Generator<\Symfony\Component\Finder\SplFileInfo>
     */
    protected function findSchemaFiles(array $directories): Generator
    {
        if ($directories === []) {
            return;
        }

        $finder = new Finder();
        $finder->files()
            ->in($directories)
            ->name(static::SCHEMA_EXTENSIONS)
            ->sortByName();

        foreach ($finder as $file) {
            yield $file;
        }
    }
}
