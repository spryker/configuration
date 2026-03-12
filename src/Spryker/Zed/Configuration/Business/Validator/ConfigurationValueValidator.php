<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Validator;

use Generated\Shared\Transfer\ConfigurationErrorTransfer;
use Generated\Shared\Transfer\ConfigurationValidationResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;
use Symfony\Component\Validator\Validation;

class ConfigurationValueValidator implements ConfigurationValueValidatorInterface
{
    public function __construct(
        protected ConfigurationSchemaProviderInterface $schemaProvider,
        protected ConfigurationConstraintMapperInterface $constraintMapper,
    ) {
    }

    public function validate(ConfigurationValueTransfer $configurationValueTransfer): ConfigurationValidationResponseTransfer
    {
        $settingKey = $configurationValueTransfer->getSettingKeyOrFail();
        $value = $configurationValueTransfer->getValue();

        $settingsMap = $this->schemaProvider->getSettingsMap();

        if (!isset($settingsMap[$settingKey])) {
            return $this->createSuccessResponse();
        }

        $settingEntry = $settingsMap[$settingKey];
        $constraintDefinitions = $settingEntry[ConfigurationConstants::SCHEMA_KEY_CONSTRAINTS] ?? [];

        if (!$constraintDefinitions) {
            return $this->createSuccessResponse();
        }

        $settingType = $settingEntry[ConfigurationConstants::SCHEMA_KEY_TYPE] ?? null;
        $symfonyConstraints = $this->mapConstraintDefinitions($constraintDefinitions, $settingType);
        $violations = Validation::createValidator()->validate($value, $symfonyConstraints);

        if ($violations->count() === 0) {
            return $this->createSuccessResponse();
        }

        return $this->createFailureResponse($settingKey, $violations);
    }

    /**
     * @param array<array<string, mixed>> $constraintDefinitions
     * @param string|null $settingType
     *
     * @return array<\Symfony\Component\Validator\Constraint>
     */
    protected function mapConstraintDefinitions(array $constraintDefinitions, ?string $settingType = null): array
    {
        $constraints = [];

        foreach ($constraintDefinitions as $definition) {
            $constraints[] = $this->constraintMapper->mapToSymfonyConstraint($definition, $settingType);
        }

        return $constraints;
    }

    protected function createSuccessResponse(): ConfigurationValidationResponseTransfer
    {
        return (new ConfigurationValidationResponseTransfer())
            ->setIsValid(true);
    }

    /**
     * @param string $settingKey
     * @param \Symfony\Component\Validator\ConstraintViolationListInterface<\Symfony\Component\Validator\ConstraintViolationInterface> $violations
     *
     * @return \Generated\Shared\Transfer\ConfigurationValidationResponseTransfer
     */
    protected function createFailureResponse(string $settingKey, $violations): ConfigurationValidationResponseTransfer
    {
        $response = (new ConfigurationValidationResponseTransfer())
            ->setIsValid(false);

        foreach ($violations as $violation) {
            $error = (new ConfigurationErrorTransfer())
                ->setSettingKey($settingKey)
                ->setMessage($violation->getMessage());

            $response->addError($error);
        }

        return $response;
    }
}
