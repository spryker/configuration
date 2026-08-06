<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business\Sync;

use Codeception\Test\Unit;
use Spryker\Zed\Configuration\Business\Sync\ConfigurationSchemaLoader;
use Spryker\Zed\Configuration\ConfigurationConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Sync
 * @group ConfigurationSchemaLoaderTest
 * Add your own group annotations below this line
 */
class ConfigurationSchemaLoaderTest extends Unit
{
    protected const string SCHEMA_DIRECTORY_NAME = 'loader_schemas';

    protected const string SCHEMA_FILE_NAME = 'test.configuration.yaml';

    protected string $schemaDirectory;

    protected function _before(): void
    {
        $this->schemaDirectory = __DIR__ . '/../../_data/' . static::SCHEMA_DIRECTORY_NAME;

        if (!is_dir($this->schemaDirectory)) {
            mkdir($this->schemaDirectory, 0755, true);
        }

        file_put_contents($this->schemaDirectory . '/' . static::SCHEMA_FILE_NAME, $this->getSchemaYaml());
    }

    protected function _after(): void
    {
        $schemaFile = $this->schemaDirectory . '/' . static::SCHEMA_FILE_NAME;

        if (file_exists($schemaFile)) {
            unlink($schemaFile);
        }

        if (is_dir($this->schemaDirectory)) {
            rmdir($this->schemaDirectory);
        }
    }

    public function testGivenOverlappingModuleDirectoryPattensWhenCoreSchemasAreLoadedThenEachFileIsYieldedOnce(): void
    {
        // Arrange
        $relativePath = $this->getRelativeSchemaPath();
        $moduleDirectory = dirname($relativePath);
        $loader = new ConfigurationSchemaLoader($this->createConfigMock(
            [basename($relativePath)],
            [$moduleDirectory, dirname($moduleDirectory) . DIRECTORY_SEPARATOR . '*'],
        ));

        // Act
        $files = iterator_to_array($loader->loadCoreSchemas(), false);

        // Assert
        $this->assertCount(
            1,
            $files,
            'Two pattens resolving to the same directory must not make the loader read its schemas twice.',
        );
    }

    public function testGivenAWildcardModuleDirectoryPattenWhenCoreSchemasAreLoadedThenTheSchemaIsFound(): void
    {
        // Arrange
        $relativePath = $this->getRelativeSchemaPath();
        $loader = new ConfigurationSchemaLoader($this->createConfigMock(
            [basename($relativePath)],
            [dirname($relativePath, 2) . DIRECTORY_SEPARATOR . '*'],
        ));

        // Act
        $files = iterator_to_array($loader->loadCoreSchemas(), false);

        // Assert
        $this->assertCount(1, $files);
        $this->assertSame(static::SCHEMA_FILE_NAME, $files[0]->getFilename());
    }

    public function testGivenNoModuleDirectoryPattensWhenSchemasAreLoadedThenSchemaPattensResolveFromTheApplicationRoot(): void
    {
        // Arrange
        $loader = new ConfigurationSchemaLoader($this->createConfigMock(
            [$this->getRelativeSchemaPath()],
            [],
        ));

        // Act
        $files = iterator_to_array($loader->loadCoreSchemas(), false);

        // Assert
        $this->assertCount(1, $files);
    }

    /**
     * @param array<string> $schemaPattens
     * @param array<string> $moduleDirectoryPattens
     */
    protected function createConfigMock(array $schemaPattens, array $moduleDirectoryPattens): ConfigurationConfig
    {
        $configMock = $this->createMock(ConfigurationConfig::class);
        $configMock->method('getCoreConfigSchemaPattens')->willReturn($schemaPattens);
        $configMock->method('getCoreModuleDirectoryPattens')->willReturn($moduleDirectoryPattens);

        return $configMock;
    }

    protected function getRelativeSchemaPath(): string
    {
        $absolutePath = (string)realpath($this->schemaDirectory);

        return ltrim(str_replace(APPLICATION_ROOT_DIR, '', $absolutePath), DIRECTORY_SEPARATOR);
    }

    protected function getSchemaYaml(): string
    {
        return <<<'YAML'
        features:
          - key: loader_test_feature
            name: Loader Test Feature
            tabs:
              - key: general
                name: General
                groups:
                  - key: display
                    name: Display
                    scopes:
                      - global
                    settings:
                      - key: test_setting
                        name: Test Setting
                        type: string
                        scopes:
                          - global
        YAML;
    }
}
