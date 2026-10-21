<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Console;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Communication\Reader\EnvironmentConfigurationValueReader;
use Spryker\Zed\Kernel\BundleConfigResolverAwareTrait;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Saves settings from the environment variables that the project maps them to:
 *
 *   configuration:import-environment <setting key>=<VARIABLE> [<setting key>=<VARIABLE> ...]
 *       [--scope=<scope> --scope-identifier=<identifier>]
 *
 * Only the variable NAME is passed; the value is read from the environment, so it never reaches the process
 * list, the shell history or the install recipe. All mappings of one call are saved at the same scope,
 * `global` by default.
 *
 * Exits with an error code when a mapping cannot be imported; a variable that is not set is skipped.
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

    protected const string COMMAND_DESCRIPTION = 'Imports configuration values from the environment variables they are mapped to';

    protected const string ARGUMENT_MAPPING = 'mapping';

    protected const string OPTION_SCOPE = 'scope';

    protected const string OPTION_SCOPE_IDENTIFIER = 'scope-identifier';

    protected const string MAPPING_KEY_SETTING_KEY = EnvironmentConfigurationValueReader::MAPPING_KEY_SETTING_KEY;

    protected const string MAPPING_KEY_VARIABLE_NAME = EnvironmentConfigurationValueReader::MAPPING_KEY_VARIABLE_NAME;

    protected const string FORMER_VARIABLE_NAME_PREFIX = 'configuration:';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription(static::COMMAND_DESCRIPTION)
            ->addArgument(
                static::ARGUMENT_MAPPING,
                InputArgument::IS_ARRAY,
                'Mappings "<setting key>=<VARIABLE>". Only the variable NAME is passed here - the value is read '
                . 'from the environment.',
            )
            ->addOption(
                static::OPTION_SCOPE,
                null,
                InputOption::VALUE_REQUIRED,
                'Scope the values are saved at.',
                ConfigurationConstants::SCOPE_GLOBAL,
            )
            ->addOption(
                static::OPTION_SCOPE_IDENTIFIER,
                null,
                InputOption::VALUE_REQUIRED,
                'Scope identifier, such as a store name. Required for every scope except "global".',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        try {
            return $this->executeImport($input);
        } catch (Throwable $exception) {
            $this->error(sprintf(
                'Nothing was imported, because of %s. Details are not printed, because they can contain a value.',
                get_class($exception),
            ));

            return static::CODE_ERROR;
        }
    }

    protected function executeImport(InputInterface $input): int
    {
        /** @var array<string> $mappings */
        $mappings = (array)$input->getArgument(static::ARGUMENT_MAPPING);

        if ($mappings === []) {
            $this->info('No mappings named, nothing to import.');

            return static::CODE_SUCCESS;
        }

        $scope = (string)$input->getOption(static::OPTION_SCOPE);
        $scopeIdentifier = $this->findScopeIdentifier($input);
        $scopeError = $this->findScopeError($scope, $scopeIdentifier);

        if ($scopeError !== null) {
            $this->error(sprintf('%s Nothing was imported.', $scopeError));

            return static::CODE_ERROR;
        }

        try {
            $settingsMap = $this->getFactory()->createConfigurationSchemaReader()->getSettingsMap();
        } catch (Throwable $exception) {
            $this->error(sprintf(
                'Could not read the configuration schema (%s), so nothing was imported. '
                . 'Run "configuration:sync" and this command again.',
                get_class($exception),
            ));

            return static::CODE_ERROR;
        }

        if ($settingsMap === []) {
            $this->error(
                'The configuration schema holds no settings, so a secret setting cannot be told apart from '
                . 'an ordinary one: it would be stored unencrypted. '
                . 'Nothing was imported. Run "configuration:sync" before this command.',
            );

            return static::CODE_ERROR;
        }

        $secretSettingKeys = $this->getFactory()
            ->createConfigurationSettingReader()
            ->filterSecretSettingKeys($settingsMap);

        $failedMappingCount = $this->importMappings($mappings, $scope, $scopeIdentifier, $settingsMap, $secretSettingKeys);

        if ($failedMappingCount > 0) {
            $this->error(sprintf(
                'Could not import %d of %d mapping(s); see the messages above.',
                $failedMappingCount,
                count($mappings),
            ));

            return static::CODE_ERROR;
        }

        return static::CODE_SUCCESS;
    }

    protected function findScopeIdentifier(InputInterface $input): ?string
    {
        $scopeIdentifier = $input->getOption(static::OPTION_SCOPE_IDENTIFIER);

        if ($scopeIdentifier === null || $scopeIdentifier === '') {
            return null;
        }

        return (string)$scopeIdentifier;
    }

    protected function findScopeError(string $scope, ?string $scopeIdentifier): ?string
    {
        if (!$this->getFactory()->createEnvironmentConfigurationValueReader()->isAvailableScope($scope)) {
            return sprintf('The scope "%s" is not defined in this environment.', $scope);
        }

        if ($scope === ConfigurationConstants::SCOPE_GLOBAL && $scopeIdentifier !== null) {
            return sprintf('The "%s" scope takes no scope identifier, but "%s" was given.', $scope, $scopeIdentifier);
        }

        if ($scope !== ConfigurationConstants::SCOPE_GLOBAL && $scopeIdentifier === null) {
            return sprintf('The "%s" scope needs a scope identifier; pass it with --%s.', $scope, static::OPTION_SCOPE_IDENTIFIER);
        }

        return null;
    }

    /**
     * @param array<string> $mappings
     * @param array<string, mixed> $settingsMap
     * @param array<string> $secretSettingKeys
     */
    protected function importMappings(
        array $mappings,
        string $scope,
        ?string $scopeIdentifier,
        array $settingsMap,
        array $secretSettingKeys
    ): int {
        $environmentConfigurationValueReader = $this->getFactory()->createEnvironmentConfigurationValueReader();
        $failedMappingCount = 0;
        $mappingNumber = 0;

        foreach ($mappings as $mapping) {
            $mappingNumber++;
            $mappingParts = $environmentConfigurationValueReader->findMapping($mapping);

            if ($mappingParts === null) {
                $this->reportInvalidMapping($mappingNumber, str_starts_with($mapping, static::FORMER_VARIABLE_NAME_PREFIX));
                $failedMappingCount++;

                continue;
            }

            $configurationValueTransfer = (new ConfigurationValueTransfer())
                ->setScope($scope)
                ->setScopeIdentifier($scopeIdentifier)
                ->setSettingKey($mappingParts[static::MAPPING_KEY_SETTING_KEY]);

            $rawValue = $environmentConfigurationValueReader->findEnvironmentValue($mappingParts[static::MAPPING_KEY_VARIABLE_NAME]);

            if ($this->importMapping($configurationValueTransfer, $rawValue, $settingsMap, $secretSettingKeys)) {
                continue;
            }

            $failedMappingCount++;
        }

        return $failedMappingCount;
    }

    protected function reportInvalidMapping(int $mappingNumber, bool $isFormerVariableNameFormat): void
    {
        // The mapping is not quoted: when it is not of the expected form, it may hold the value instead of a name.
        $this->error(sprintf(
            'Could not import mapping #%d: expected "<setting key>%s<VARIABLE>", where VARIABLE is the name of an '
            . 'environment variable (letters, digits and underscores). Pass the variable name, never the value.',
            $mappingNumber,
            EnvironmentConfigurationValueReader::MAPPING_SEPARATOR,
        ));

        if (!$isFormerVariableNameFormat) {
            return;
        }

        $this->error(sprintf(
            'Variable names of the form "%s<scope>:<setting key>" are no longer read; map the setting to a variable '
            . 'instead, and pass the scope with --%s.',
            static::FORMER_VARIABLE_NAME_PREFIX,
            static::OPTION_SCOPE,
        ));
    }

    /**
     * @param array<string, mixed> $settingsMap
     * @param array<string> $secretSettingKeys
     */
    protected function importMapping(
        ConfigurationValueTransfer $configurationValueTransfer,
        ?string $rawValue,
        array $settingsMap,
        array $secretSettingKeys
    ): bool {
        $settingKey = $configurationValueTransfer->getSettingKeyOrFail();

        if (!isset($settingsMap[$settingKey])) {
            $this->error(sprintf('Could not import "%s": the schema defines no such setting.', $settingKey));

            return false;
        }

        if ($rawValue === null) {
            // The variable name is not printed: a value passed by mistake can still look like a name.
            $this->info(sprintf('Skipping "%s": its mapped environment variable is not set in this environment.', $settingKey));

            // Not a failure: one recipe serves several environments, and not every environment defines every value.
            return true;
        }

        $isSecret = in_array($settingKey, $secretSettingKeys, true);

        return $this->saveConfigurationValue($configurationValueTransfer->setValue($rawValue), $isSecret);
    }

    protected function saveConfigurationValue(ConfigurationValueTransfer $configurationValueTransfer, bool $isSecret): bool
    {
        $settingKey = $configurationValueTransfer->getSettingKeyOrFail();
        $scope = $this->formatScope($configurationValueTransfer);

        $requestTransfer = (new ConfigurationValueCollectionRequestTransfer())
            ->addConfigurationValue($configurationValueTransfer);

        try {
            $responseTransfer = $this->getFacade()->saveConfigurationValues($requestTransfer);
        } catch (Throwable $exception) {
            $this->reportFailedSave($settingKey, $scope, get_class($exception));

            return false;
        }

        if ($responseTransfer->getIsSuccess() !== true) {
            $this->reportFailedSave($settingKey, $scope, $this->formatRejectionReason($responseTransfer));

            return false;
        }

        $this->reportImported($settingKey, $scope, $isSecret);

        return true;
    }

    protected function reportImported(string $settingKey, string $scope, bool $isSecret): void
    {
        if ($isSecret) {
            $this->success(sprintf('Imported secret setting "%s" (scope: %s), encrypted on save.', $settingKey, $scope));

            return;
        }

        $this->success(sprintf('Imported "%s" (scope: %s).', $settingKey, $scope));
    }

    protected function reportFailedSave(string $settingKey, string $scope, string $reason): void
    {
        // The exception or validation message is not printed: it can quote the value, secret or not.
        $this->error(sprintf(
            'Could not import "%s" (scope: %s): %s. Details are not printed, because they can contain the value - '
            . 'verify the value in the environment. This setting keeps its previous value, or none if the database '
            . 'was reset.',
            $settingKey,
            $scope,
            $reason,
        ));
    }

    protected function formatRejectionReason(ConfigurationValueCollectionResponseTransfer $responseTransfer): string
    {
        $errorCount = count($responseTransfer->getErrors());

        if ($errorCount === 0) {
            return 'the save was rejected without a stated reason';
        }

        return sprintf('the save was rejected with %d validation error(s)', $errorCount);
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
