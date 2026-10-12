<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Console;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Shared\Configuration\ConfigurationExportFileFormat;
use Spryker\Zed\Kernel\BundleConfigResolverAwareTrait;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Saves the configuration values from an exported file to the database.
 *
 * @method \Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface getFacade()
 * @method \Spryker\Zed\Configuration\ConfigurationConfig getConfig()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface getRepository()
 * @method \Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory getFactory()
 */
class RestoreConfigurationConsole extends Console
{
    use BundleConfigResolverAwareTrait;

    protected const string COMMAND_NAME = 'configuration:restore';

    protected const string ARGUMENT_SETTING_KEY = 'setting-key';

    protected const string COMMAND_DESCRIPTION = 'Restores configuration values captured before by configuration:export command';

    protected const string KEY_SETTING_KEY = ConfigurationExportFileFormat::KEY_SETTING_KEY;

    protected const string KEY_VALUES = ConfigurationExportFileFormat::KEY_VALUES;

    protected int $failedCount = 0;

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription(static::COMMAND_DESCRIPTION)
            ->addArgument(
                static::ARGUMENT_SETTING_KEY,
                InputArgument::IS_ARRAY,
                'Setting keys to restore. Anything else in the file is left alone.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        try {
            return $this->executeRestore($input);
        } catch (Throwable $exception) {
            $this->error(sprintf(
                'Nothing was restored, because %s: %s. The command still exits successfully.',
                get_class($exception),
                $exception->getMessage(),
            ));

            return static::CODE_SUCCESS;
        }
    }

    protected function executeRestore(InputInterface $input): int
    {
        /** @var array<string> $settingKeys */
        $settingKeys = (array)$input->getArgument(static::ARGUMENT_SETTING_KEY);

        if ($settingKeys === []) {
            $this->info('No setting keys named, nothing to restore.');

            return static::CODE_SUCCESS;
        }

        $settingsMap = $this->getFactory()->createConfigurationSchemaReader()->getSettingsMap();

        if ($settingsMap === []) {
            $this->error(
                'The configuration schema holds no settings, so it is unknown which of them still exist or are '
                . 'secret. Nothing was restored. The command still exits successfully; run "configuration:sync" '
                . 'before this command.',
            );

            return static::CODE_SUCCESS;
        }

        $settingKeys = $this->filterRestorableSettingKeys($settingKeys, $settingsMap);

        if ($settingKeys === []) {
            $this->info('None of the named settings can be restored, nothing to do.');

            return static::CODE_SUCCESS;
        }

        $file = $this->findExportFile();

        if ($file === null) {
            return static::CODE_SUCCESS;
        }

        if (!file_exists($file)) {
            $this->info(sprintf('No export at "%s", nothing to restore.', $file));

            return static::CODE_SUCCESS;
        }

        if (!is_readable($file)) {
            $this->error(sprintf(
                'The export at "%s" exists but the current user cannot read it; nothing was restored. '
                . 'The command still exits successfully.',
                $file,
            ));

            return static::CODE_SUCCESS;
        }

        $restoredCount = $this->restoreFile($file, $settingKeys);

        if ($restoredCount === 0 || $this->failedCount > 0) {
            $this->warning(sprintf(
                'Restored %d value(s) and could not restore %d, so "%s" is kept. Fix the cause reported above '
                . 'and run this command again.',
                $restoredCount,
                $this->failedCount,
                $file,
            ));

            return static::CODE_SUCCESS;
        }

        $this->success(sprintf('Restored %d configuration value(s).', $restoredCount));

        if (is_file($file) && !unlink($file)) {
            $this->warning(sprintf(
                'Everything was restored, but "%s" could not be removed; delete it by hand.',
                $file,
            ));
        }

        return static::CODE_SUCCESS;
    }

    /**
     * @param array<string> $settingKeys
     * @param array<string, array<string, mixed>> $settingsMap
     *
     * @return array<string>
     */
    protected function filterRestorableSettingKeys(array $settingKeys, array $settingsMap): array
    {
        $secretSettingKeys = $this->getFactory()
            ->createConfigurationSettingReader()
            ->filterSecretSettingKeys($settingsMap);

        $restorableSettingKeys = [];

        foreach ($settingKeys as $settingKey) {
            if (!isset($settingsMap[$settingKey])) {
                $this->warning(sprintf(
                    'Not restoring "%s": the schema no longer defines it, so it cannot be classified as secret.',
                    $settingKey,
                ));

                continue;
            }

            if (in_array($settingKey, $secretSettingKeys, true)) {
                $this->warning(sprintf(
                    'Not restoring "%s": the schema declares it secret. A secret is seeded from the environment '
                    . 'by "configuration:import-environment", never from this file.',
                    $settingKey,
                ));

                continue;
            }

            $restorableSettingKeys[] = $settingKey;
        }

        return $restorableSettingKeys;
    }

    protected function findExportFile(): ?string
    {
        try {
            return $this->getConfig()->getExportFile();
        } catch (Throwable $exception) {
            $this->error(sprintf(
                'Nothing was restored: %s',
                $exception->getMessage(),
            ));

            return null;
        }
    }

    /**
     * @param array<string> $settingKeys
     */
    protected function restoreFile(string $file, array $settingKeys): int
    {
        $configValuesJson = file_get_contents($file);

        if ($configValuesJson === false) {
            $this->error(sprintf(
                'Could not read "%s"; nothing was restored. The command still exits successfully.',
                $file,
            ));

            return 0;
        }

        $export = $this->getFactory()->getUtilEncodingService()->decodeJson($configValuesJson, true);
        $values = is_array($export) ? ($export[static::KEY_VALUES] ?? null) : null;

        if (!is_array($values)) {
            $this->error(sprintf(
                '"%s" is not readable as an export; nothing was restored. The command still exits successfully.',
                $file,
            ));

            return 0;
        }

        $restoredCount = 0;
        $skippedCount = 0;
        $settingKeysInExport = [];
        $this->failedCount = 0;

        foreach ($values as $value) {
            if (!is_array($value)) {
                $this->failedCount++;
                $this->warning('Skipping an entry in the export that is not a value object.');

                continue;
            }

            if (!in_array((string)($value[static::KEY_SETTING_KEY] ?? ''), $settingKeys, true)) {
                $skippedCount++;

                continue;
            }

            $settingKeysInExport[(string)$value[static::KEY_SETTING_KEY]] = true;

            if (!$this->restoreConfigurationValue($value)) {
                $this->failedCount++;

                continue;
            }

            $restoredCount++;
        }

        if ($skippedCount > 0) {
            $this->info(sprintf('Left %d value(s) in the export alone; they are not among the named settings.', $skippedCount));
        }

        $this->reportSettingKeysNotInExport($settingKeys, $settingKeysInExport);

        return $restoredCount;
    }

    /**
     * @param array<string> $settingKeys
     * @param array<string, bool> $settingKeysInExport
     */
    protected function reportSettingKeysNotInExport(array $settingKeys, array $settingKeysInExport): void
    {
        foreach ($settingKeys as $settingKey) {
            if (isset($settingKeysInExport[$settingKey])) {
                continue;
            }

            $this->info(sprintf('The export holds no value for "%s", so nothing was restored for it.', $settingKey));
        }
    }

    /**
     * @param array<mixed> $value
     */
    protected function restoreConfigurationValue(array $value): bool
    {
        $settingKey = (string)($value[static::KEY_SETTING_KEY] ?? '');
        $scope = (string)($value[ConfigurationExportFileFormat::KEY_SCOPE] ?? '');
        $scopeIdentifier = (string)($value[ConfigurationExportFileFormat::KEY_SCOPE_IDENTIFIER] ?? '');

        if ($settingKey === '' || $scope === '') {
            $this->warning(sprintf(
                'Skipping a malformed entry in the export: setting key "%s", scope "%s". Both are required.',
                $settingKey,
                $scope,
            ));

            return false;
        }

        if (!in_array($scope, $this->getConfig()->getAvailableScopes(), true)) {
            $this->warning(sprintf(
                'Skipping "%s": the export names scope "%s", which this environment does not define.',
                $settingKey,
                $scope,
            ));

            return false;
        }

        $configurationValueTransfer = (new ConfigurationValueTransfer())
            ->setSettingKey($settingKey)
            ->setScope($scope)
            ->setScopeIdentifier($scopeIdentifier === '' ? null : $scopeIdentifier)
            ->setValue((string)($value[ConfigurationExportFileFormat::KEY_VALUE] ?? ''));

        $requestTransfer = (new ConfigurationValueCollectionRequestTransfer())
            ->addConfigurationValue($configurationValueTransfer);

        try {
            $responseTransfer = $this->getFacade()->saveConfigurationValues($requestTransfer);
        } catch (Throwable $exception) {
            $this->warning(sprintf(
                'Skipping "%s": %s: %s.',
                $settingKey,
                get_class($exception),
                $exception->getMessage(),
            ));

            return false;
        }

        if ($responseTransfer->getIsSuccess() !== true) {
            if (count($responseTransfer->getErrors()) === 0) {
                $this->warning(sprintf('Skipping "%s": the save was rejected without a stated reason.', $settingKey));

                return false;
            }

            foreach ($responseTransfer->getErrors() as $configurationErrorTransfer) {
                $this->warning(sprintf('Skipping "%s": %s', $settingKey, $configurationErrorTransfer->getMessage()));
            }

            return false;
        }

        return true;
    }
}
