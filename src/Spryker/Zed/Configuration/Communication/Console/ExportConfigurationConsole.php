<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Console;

use Spryker\Shared\Configuration\ConfigurationExportFileFormat;
use Spryker\Zed\Kernel\BundleConfigResolverAwareTrait;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Writes the listed configuration values to a file.
 * Secret values are not exported.
 *
 * @method \Spryker\Zed\Configuration\ConfigurationConfig getConfig()
 * @method \Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory getFactory()
 */
class ExportConfigurationConsole extends Console
{
    use BundleConfigResolverAwareTrait;

    protected const string COMMAND_NAME = 'configuration:export';

    protected const string ARGUMENT_SETTING_KEY = 'setting-key';

    protected const string COMMAND_DESCRIPTION = 'Writes the named non-secret configuration values to the file.';

    protected const int EXPORT_FILE_PERMISSIONS = 0600;

    protected const int EXPORT_DIRECTORY_PERMISSIONS = 0700;

    protected const string KEY_VALUES = ConfigurationExportFileFormat::KEY_VALUES;

    protected const string KEY_SETTING_KEY = ConfigurationExportFileFormat::KEY_SETTING_KEY;

    protected const string KEY_SCOPE = ConfigurationExportFileFormat::KEY_SCOPE;

    protected const string KEY_SCOPE_IDENTIFIER = ConfigurationExportFileFormat::KEY_SCOPE_IDENTIFIER;

    protected const string KEY_VALUE = ConfigurationExportFileFormat::KEY_VALUE;

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription(static::COMMAND_DESCRIPTION)
            ->addArgument(
                static::ARGUMENT_SETTING_KEY,
                InputArgument::IS_ARRAY,
                'Setting keys to export. Only these are written, at every scope they are stored at.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        try {
            return $this->executeExport($input);
        } catch (Throwable $exception) {
            $this->error(sprintf(
                'Nothing was exported, because %s: %s. The command still exits successfully.',
                get_class($exception),
                $exception->getMessage(),
            ));

            return static::CODE_SUCCESS;
        }
    }

    protected function executeExport(InputInterface $input): int
    {
        /** @var array<string> $settingKeys */
        $settingKeys = (array)$input->getArgument(static::ARGUMENT_SETTING_KEY);

        if ($settingKeys === []) {
            $this->info('No setting keys named, nothing to export.');

            return static::CODE_SUCCESS;
        }

        $settingsMap = $this->getFactory()->createConfigurationSchemaReader()->getSettingsMap();

        if ($settingsMap === []) {
            $this->warning(
                'The configuration schema holds no settings, so a secret setting cannot be told apart from '
                . 'an ordinary one and its stored value would land in the export file. Nothing was exported. '
                . 'The command still exits successfully; run "configuration:sync" before this command.',
            );

            return static::CODE_SUCCESS;
        }

        $secretSettingKeys = $this->getFactory()
            ->createConfigurationSettingReader()
            ->filterSecretSettingKeys($settingsMap);

        try {
            $values = $this->findExportableValues($settingKeys, $secretSettingKeys);
        } catch (Throwable $exception) {
            $this->error(sprintf(
                'Could not read the stored configuration values (%s: %s), so nothing was exported. On a first '
                . 'install this is expected, because the table does not exist yet. The command still exits '
                . 'successfully.',
                get_class($exception),
                $exception->getMessage(),
            ));

            return static::CODE_SUCCESS;
        }

        if ($values === []) {
            $this->info('None of the named settings has a stored value, nothing to export.');

            return static::CODE_SUCCESS;
        }

        $file = $this->findExportFile();

        if ($file === null) {
            return static::CODE_SUCCESS;
        }

        return $this->writeFile($file, $values);
    }

    protected function findExportFile(): ?string
    {
        try {
            return $this->getConfig()->getExportFile();
        } catch (Throwable $exception) {
            $this->error(sprintf(
                'Nothing was exported: %s',
                $exception->getMessage(),
            ));

            return null;
        }
    }

    /**
     * @param array<string> $settingKeys
     * @param array<string> $secretSettingKeys
     *
     * @return array<array<string, string>>
     */
    protected function findExportableValues(array $settingKeys, array $secretSettingKeys): array
    {
        $configurationValueExportReader = $this->getFactory()->createConfigurationValueExportReader();

        $scopesWithoutIdentifier = $configurationValueExportReader->getScopesWithoutIdentifier();
        if ($scopesWithoutIdentifier !== []) {
            $this->reportScopesWithoutIdentifierProvider($scopesWithoutIdentifier);
        }

        $values = [];

        foreach ($configurationValueExportReader->getExportableConfigurationValues($settingKeys, $secretSettingKeys) as $configurationValueTransfer) {
            $values[] = [
                static::KEY_SETTING_KEY => (string)$configurationValueTransfer->getSettingKey(),
                static::KEY_SCOPE => (string)$configurationValueTransfer->getScope(),
                static::KEY_SCOPE_IDENTIFIER => (string)$configurationValueTransfer->getScopeIdentifier(),
                static::KEY_VALUE => (string)$configurationValueTransfer->getValue(),
            ];
        }

        $this->reportSettingKeysWithoutValue(
            $settingKeys,
            $secretSettingKeys,
            array_column($values, static::KEY_SETTING_KEY),
        );

        return $values;
    }

    /**
     * @param array<string> $scopes
     */
    protected function reportScopesWithoutIdentifierProvider(array $scopes): void
    {
        foreach ($scopes as $scope) {
            $this->warning(sprintf(
                'No scope identifier provider handles the "%s" scope, so values stored at that scope cannot be exported.'
                . ' Register a ConfigurationScopeIdentifierProviderPlugin for it.',
                $scope,
            ));
        }
    }

    /**
     * @param array<string> $settingKeys
     * @param array<string> $secretSettingKeys
     * @param array<string> $exportedSettingKeys
     */
    protected function reportSettingKeysWithoutValue(array $settingKeys, array $secretSettingKeys, array $exportedSettingKeys): void
    {
        foreach ($settingKeys as $settingKey) {
            if (in_array($settingKey, $exportedSettingKeys, true)) {
                continue;
            }

            if (in_array($settingKey, $secretSettingKeys, true)) {
                $this->warning(sprintf(
                    'Not exporting "%s": the schema declares it secret, and a secret is never written to this file. '
                    . 'It is seeded from the environment by "configuration:import-environment" instead.',
                    $settingKey,
                ));

                continue;
            }

            $this->info(sprintf('Nothing stored for key "%s", so there is nothing to export for it.', $settingKey));
        }
    }

    /**
     * @param string $file
     * @param array<array<string, string>> $values
     *
     * @return int
     */
    protected function writeFile(string $file, array $values): int
    {
        $configValuesJson = $this->getFactory()->getUtilEncodingService()->encodeJson([static::KEY_VALUES => $values]);

        if ($configValuesJson === null) {
            $this->error('Could not encode the configuration values into JSON; nothing was exported. The command still exits successfully.');

            return static::CODE_SUCCESS;
        }

        if (!$this->createDirectory($file)) {
            return static::CODE_SUCCESS;
        }

        if (!touch($file) || !chmod($file, static::EXPORT_FILE_PERMISSIONS)) {
            if (is_file($file)) {
                unlink($file);
            }

            $this->error(sprintf(
                'Could not create file "%s" with restricted permissions; nothing was exported. '
                . 'The command still exits successfully.',
                $file,
            ));

            return static::CODE_SUCCESS;
        }

        $writtenByteCount = file_put_contents($file, $configValuesJson);

        if ($writtenByteCount !== strlen($configValuesJson)) {
            unlink($file);
            $this->error(sprintf(
                'Wrote %s of %d bytes into file "%s", so the export would be truncated; it was removed and '
                . 'nothing was exported. The command still exits successfully.',
                $writtenByteCount === false ? 'none' : (string)$writtenByteCount,
                strlen($configValuesJson),
                $file,
            ));

            return static::CODE_SUCCESS;
        }

        $this->success(sprintf('Exported %d configuration value(s) to "%s".', count($values), $file));

        return static::CODE_SUCCESS;
    }

    protected function createDirectory(string $file): bool
    {
        $directory = dirname($file);

        if (is_dir($directory)) {
            return true;
        }

        if (!mkdir($directory, static::EXPORT_DIRECTORY_PERMISSIONS, true) && !is_dir($directory)) {
            $this->error(sprintf(
                'Could not create directory "%s"; nothing was exported. The command still exits successfully.',
                $directory,
            ));

            return false;
        }

        return true;
    }
}
