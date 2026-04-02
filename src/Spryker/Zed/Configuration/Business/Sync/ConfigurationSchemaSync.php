<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Sync;

use Generated\Shared\Transfer\ConfigurationSyncResponseTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Shared\Configuration\Schema\SchemaParserInterface;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaSettingsMapperInterface;
use Spryker\Zed\Configuration\ConfigurationConfig;

class ConfigurationSchemaSync implements ConfigurationSchemaSyncInterface
{
    public function __construct(
        protected ConfigurationSchemaLoaderInterface $schemaLoader,
        protected ConfigurationSchemaMergerInterface $schemaMerger,
        protected SchemaParserInterface $schemaParser,
        protected ConfigurationSchemaSettingsMapperInterface $settingsMapper,
        protected ConfigurationConfig $config,
    ) {
    }

    public function sync(): ConfigurationSyncResponseTransfer
    {
        $configurationSyncResponseTransfer = (new ConfigurationSyncResponseTransfer())
            ->setIsSuccess(false)
            ->setProcessedCount(0)
            ->setErrorMessages([]);

        $mergedSchema = $this->schemaMerger->merge(
            $this->schemaLoader->loadCoreSchemas(),
            $this->schemaLoader->loadProjectSchemas(),
        );

        if ($mergedSchema === []) {
            return $configurationSyncResponseTransfer
                ->setIsSuccess(true)
                ->addErrorMessage('No configuration schemas found');
        }

        if (!$this->schemaParser->validate($mergedSchema)) {
            $configurationSyncResponseTransfer->addErrorMessage('Schema validation failed:');

            foreach ($this->schemaParser->getValidationErrors() as $error) {
                $configurationSyncResponseTransfer->addErrorMessage(sprintf('  - %s', $error));
            }

            return $configurationSyncResponseTransfer;
        }

        $availableScopes = $this->config->getAvailableScopes();
        $mergedSchema = $this->filterInvalidScopes($mergedSchema, $availableScopes);

        $this->writeSchemaToFile($mergedSchema);
        $this->writeSettingsMapToFile($mergedSchema);

        $processedCount = count($this->settingsMapper->mapSchemaToSettingTransfers($mergedSchema));

        return $configurationSyncResponseTransfer
            ->setIsSuccess(true)
            ->setProcessedCount($processedCount);
    }

    /**
     * @param array<mixed> $schema
     *
     * @return void
     */
    protected function writeSchemaToFile(array $schema): void
    {
        $filePath = $this->config->getMergedSchemaFilePath();
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, sprintf("<?php\n\nreturn %s;\n", var_export($schema, true)));
    }

    /**
     * @param array<mixed> $schema
     *
     * @return void
     */
    protected function writeSettingsMapToFile(array $schema): void
    {
        $filePath = $this->config->getSettingsMapFilePath();
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $settingsMap = $this->buildSettingsMap($schema);

        file_put_contents($filePath, sprintf("<?php\n\nreturn %s;\n", var_export($settingsMap, true)));
    }

    /**
     * @param array<mixed> $schema
     *
     * @return array<string, array<string, mixed>>
     */
    protected function buildSettingsMap(array $schema): array
    {
        $map = [];

        if (!isset($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES]) || !is_array($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES])) {
            return $map;
        }

        foreach ($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES] as $feature) {
            if (!isset($feature[ConfigurationConstants::SCHEMA_KEY_TABS]) || !is_array($feature[ConfigurationConstants::SCHEMA_KEY_TABS])) {
                continue;
            }

            foreach ($feature[ConfigurationConstants::SCHEMA_KEY_TABS] as $tab) {
                if (!isset($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS]) || !is_array($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS])) {
                    continue;
                }

                foreach ($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS] as $group) {
                    if (!isset($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS]) || !is_array($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS])) {
                        continue;
                    }

                    foreach ($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS] as $setting) {
                        $compoundKey = sprintf(
                            '%s:%s:%s:%s',
                            $feature[ConfigurationConstants::SCHEMA_KEY_KEY],
                            $tab[ConfigurationConstants::SCHEMA_KEY_KEY],
                            $group[ConfigurationConstants::SCHEMA_KEY_KEY],
                            $setting[ConfigurationConstants::SCHEMA_KEY_KEY],
                        );

                        $defaultValue = $setting[ConfigurationConstants::SCHEMA_KEY_DEFAULT_VALUE] ?? null;

                        if ($defaultValue !== null && !is_string($defaultValue)) {
                            $defaultValue = (string)json_encode($defaultValue);
                        }

                        $map[$compoundKey] = [
                            ConfigurationConstants::SCHEMA_KEY_TYPE => $setting[ConfigurationConstants::SCHEMA_KEY_TYPE],
                            ConfigurationConstants::SCHEMA_KEY_DEFAULT_VALUE => $defaultValue,
                            ConfigurationConstants::SCHEMA_KEY_SECRET => $setting[ConfigurationConstants::SCHEMA_KEY_SECRET] ?? false,
                            ConfigurationConstants::SCHEMA_KEY_STOREFRONT => $setting[ConfigurationConstants::SCHEMA_KEY_STOREFRONT] ?? false,
                            ConfigurationConstants::SCHEMA_KEY_SCOPES => $setting[ConfigurationConstants::SCHEMA_KEY_SCOPES] ?? [],
                            ConfigurationConstants::SCHEMA_KEY_CONSTRAINTS => $setting[ConfigurationConstants::SCHEMA_KEY_CONSTRAINTS] ?? [],
                            ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS => $setting[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS] ?? [],
                        ];
                    }
                }
            }
        }

        return $map;
    }

    /**
     * @param array<mixed> $schema
     * @param array<string> $availableScopes
     *
     * @return array<mixed>
     */
    protected function filterInvalidScopes(array $schema, array $availableScopes): array
    {
        if (!isset($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES])) {
            return $schema;
        }

        foreach ($schema[ConfigurationConstants::SCHEMA_KEY_FEATURES] as &$feature) {
            if (!isset($feature[ConfigurationConstants::SCHEMA_KEY_TABS])) {
                continue;
            }

            foreach ($feature[ConfigurationConstants::SCHEMA_KEY_TABS] as &$tab) {
                if (!isset($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS])) {
                    continue;
                }

                foreach ($tab[ConfigurationConstants::SCHEMA_KEY_GROUPS] as &$group) {
                    $group[ConfigurationConstants::SCHEMA_KEY_SCOPES] = array_intersect($group[ConfigurationConstants::SCHEMA_KEY_SCOPES] ?? [], $availableScopes);

                    if (!isset($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS])) {
                        continue;
                    }

                    foreach ($group[ConfigurationConstants::SCHEMA_KEY_SETTINGS] as &$setting) {
                        $setting[ConfigurationConstants::SCHEMA_KEY_SCOPES] = array_intersect($setting[ConfigurationConstants::SCHEMA_KEY_SCOPES] ?? [], $availableScopes);
                    }
                }
            }
        }

        return $schema;
    }
}
