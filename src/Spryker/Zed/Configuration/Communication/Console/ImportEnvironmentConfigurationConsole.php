<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Console;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Zed\Configuration\Communication\Reader\EnvironmentConfigurationValueReader;
use Spryker\Zed\Kernel\BundleConfigResolverAwareTrait;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Saves listed settings from the environment variables that declare them.
 *
 * The variable name is the setting key namespaced with `configuration:` and the
 * scope it applies to. The scope is always named, `global` included:
 *
 *   configuration:global:<setting key> -> global scope
 *   configuration:<scope>:<identifier>:<setting key> -> scoped value
 *
 * @method \Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface getFacade()
 * @method \Spryker\Zed\Configuration\ConfigurationConfig getConfig()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface getRepository()
 * @method \Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory getFactory()
 */
class ImportEnvironmentConfigurationConsole extends Console
{
    use BundleConfigResolverAwareTrait;

    protected const string COMMAND_NAME = 'configuration:import-environment';

    protected const string COMMAND_DESCRIPTION = 'Imports configuration values declared in the environment';

    protected const string ARGUMENT_ENVIRONMENT_VARIABLE = 'environment-variable';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription(static::COMMAND_DESCRIPTION)
            ->addArgument(
                static::ARGUMENT_ENVIRONMENT_VARIABLE,
                InputArgument::IS_ARRAY,
                sprintf(
                    'Names of the environment variables to import, each prefixed with "%s". '
                    . 'Only the NAME is passed here - the value is read from the environment.',
                    EnvironmentConfigurationValueReader::ENVIRONMENT_VARIABLE_PREFIX,
                ),
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        try {
            return $this->executeImport($input);
        } catch (Throwable $exception) {
            $this->error(sprintf(
                'Nothing was imported, because %s: %s. The command still exits successfully.',
                get_class($exception),
                $exception->getMessage(),
            ));

            return static::CODE_SUCCESS;
        }
    }

    protected function executeImport(InputInterface $input): int
    {
        /** @var array<string> $variableNames */
        $variableNames = (array)$input->getArgument(static::ARGUMENT_ENVIRONMENT_VARIABLE);

        if ($variableNames === []) {
            $this->info('No environment variables named, nothing to import.');

            return static::CODE_SUCCESS;
        }

        try {
            $settingsMap = $this->getFactory()->createConfigurationSchemaReader()->getSettingsMap();
        } catch (Throwable $exception) {
            $this->error(sprintf(
                'Could not read the configuration schema (%s), so nothing was imported. '
                . 'The command still exits successfully; run "configuration:sync" and this command again.',
                get_class($exception),
            ));

            return static::CODE_SUCCESS;
        }

        if ($settingsMap === []) {
            $this->error(
                'The configuration schema holds no settings, so a secret setting cannot be told apart from '
                . 'an ordinary one: it would be stored unencrypted. '
                . 'Nothing was imported. The command still exits successfully; run "configuration:sync" '
                . 'before this command.',
            );

            return static::CODE_SUCCESS;
        }

        $secretSettingKeys = $this->getFactory()
            ->createConfigurationSettingReader()
            ->filterSecretSettingKeys($settingsMap);

        foreach ($variableNames as $variableName) {
            $this->importEnvironmentVariable($variableName, $secretSettingKeys);
        }

        return static::CODE_SUCCESS;
    }

    /**
     * @param array<string> $secretSettingKeys
     */
    protected function importEnvironmentVariable(string $variableName, array $secretSettingKeys): void
    {
        $environmentConfigurationValueReader = $this->getFactory()->createEnvironmentConfigurationValueReader();
        $environmentVariablePrefix = $environmentConfigurationValueReader->getEnvironmentVariablePrefix();

        if (!str_starts_with($variableName, $environmentVariablePrefix)) {
            $this->warning(sprintf(
                'Skipping "%s": the name must start with "%s", which is what the setting key is derived from.',
                $variableName,
                $environmentVariablePrefix,
            ));

            return;
        }

        $configurationValueTransfer = $environmentConfigurationValueReader->variableNameToConfigurationValueTransfer($variableName);

        if ($configurationValueTransfer === null) {
            $this->warning(sprintf(
                'Skipping "%s": the name does not resolve to a setting key. The scope is always '
                . 'named, so expected "%sglobal:<setting key>" or '
                . '"%s<scope>:<identifier>:<setting key>".',
                $variableName,
                $environmentVariablePrefix,
                $environmentVariablePrefix,
            ));

            return;
        }

        $rawValue = $environmentConfigurationValueReader->findEnvironmentValue($variableName);

        if ($rawValue === null) {
            $this->info(sprintf('Skipping "%s": not set in this environment.', $variableName));

            return;
        }

        $isSecret = in_array($configurationValueTransfer->getSettingKeyOrFail(), $secretSettingKeys, true);

        $this->saveConfigurationValue($configurationValueTransfer->setValue($rawValue), $isSecret);
    }

    protected function saveConfigurationValue(ConfigurationValueTransfer $configurationValueTransfer, bool $isSecret): void
    {
        $settingKey = $configurationValueTransfer->getSettingKeyOrFail();
        $scope = $this->formatScope($configurationValueTransfer);

        $requestTransfer = (new ConfigurationValueCollectionRequestTransfer())
            ->addConfigurationValue($configurationValueTransfer);

        try {
            $responseTransfer = $this->getFacade()->saveConfigurationValues($requestTransfer);
        } catch (Throwable $exception) {
            $this->reportFailedException($settingKey, $scope, $exception, $isSecret);

            return;
        }

        if ($responseTransfer->getIsSuccess() !== true) {
            $this->reportFailedValidation($settingKey, $scope, $responseTransfer, $isSecret);

            return;
        }

        $this->reportImported($settingKey, $scope, $isSecret);
    }

    protected function reportImported(string $settingKey, string $scope, bool $isSecret): void
    {
        if ($isSecret) {
            $this->success(sprintf('Imported secret setting "%s" (scope: %s), encrypted on save.', $settingKey, $scope));

            return;
        }

        $this->success(sprintf('Imported "%s" (scope: %s).', $settingKey, $scope));
    }

    protected function reportFailedException(string $settingKey, string $scope, Throwable $exception, bool $isSecret): void
    {
        if ($isSecret) {
            $this->reportSuppressedFailure($settingKey, $scope, get_class($exception));

            return;
        }

        $this->error(sprintf(
            'Could not import "%s" (scope: %s): %s: %s. The command still exits successfully.',
            $settingKey,
            $scope,
            get_class($exception),
            $exception->getMessage(),
        ));
    }

    protected function reportFailedValidation(
        string $settingKey,
        string $scope,
        ConfigurationValueCollectionResponseTransfer $responseTransfer,
        bool $isSecret
    ): void {
        if ($isSecret) {
            $this->reportSuppressedFailure($settingKey, $scope, sprintf('%d validation error(s)', count($responseTransfer->getErrors())));

            return;
        }

        foreach ($responseTransfer->getErrors() as $configurationErrorTransfer) {
            $this->error(sprintf(
                'Could not import "%s" (scope: %s): %s. The command still exits successfully.',
                $settingKey,
                $scope,
                $configurationErrorTransfer->getMessage(),
            ));
        }
    }

    protected function reportSuppressedFailure(string $settingKey, string $scope, string $reason): void
    {
        $this->error(sprintf(
            'Could not import secret setting "%s" (scope: %s): %s. '
            . 'Details are suppressed because the setting is secret - verify the value in the environment. '
            . 'The command still exits successfully; this setting keeps its previous value, or none '
            . 'if the database was reset.',
            $settingKey,
            $scope,
            $reason,
        ));
    }

    protected function formatScope(ConfigurationValueTransfer $configurationValueTransfer): string
    {
        $scope = $configurationValueTransfer->getScopeOrFail();
        $scopeIdentifier = $configurationValueTransfer->getScopeIdentifier();

        if ($scopeIdentifier === null) {
            return $scope;
        }

        return sprintf('%s:%s', $scope, $scopeIdentifier);
    }
}
