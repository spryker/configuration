<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Validator;

use Exception;
use InvalidArgumentException;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Url;

class ConfigurationConstraintMapper implements ConfigurationConstraintMapperInterface
{
    /**
     * @var array<string, class-string<\Symfony\Component\Validator\Constraint>>
     */
    protected const array SHORT_NAME_TO_CLASS_MAP = [
        ConfigurationSchemaConstants::CONSTRAINT_TYPE_REQUIRED => NotBlank::class,
        ConfigurationSchemaConstants::CONSTRAINT_TYPE_MIN => GreaterThanOrEqual::class,
        ConfigurationSchemaConstants::CONSTRAINT_TYPE_MAX => LessThanOrEqual::class,
        ConfigurationSchemaConstants::CONSTRAINT_TYPE_EMAIL => Email::class,
        ConfigurationSchemaConstants::CONSTRAINT_TYPE_URL => Url::class,
        ConfigurationSchemaConstants::CONSTRAINT_TYPE_REGEX => Regex::class,
        ConfigurationSchemaConstants::CONSTRAINT_TYPE_CHOICE => Choice::class,
        ConfigurationSchemaConstants::CONSTRAINT_TYPE_LENGTH => Length::class,
        ConfigurationSchemaConstants::CONSTRAINT_TYPE_RANGE => Range::class,
    ];

    /**
     * @var array<string, bool>
     */
    protected const array NUMERIC_SETTING_TYPES = [
        ConfigurationSchemaConstants::VALUE_TYPE_INTEGER => true,
        ConfigurationSchemaConstants::VALUE_TYPE_FLOAT => true,
    ];

    /**
     * @inheritDoc
     */
    public function mapToSymfonyConstraint(array $constraintDefinition, ?string $settingType = null): Constraint
    {
        $type = $constraintDefinition[ConfigurationSchemaConstants::CONSTRAINT_KEY_TYPE] ?? '';
        $message = $constraintDefinition[ConfigurationSchemaConstants::CONSTRAINT_KEY_MESSAGE] ?? null;
        $options = $constraintDefinition[ConfigurationSchemaConstants::CONSTRAINT_KEY_OPTIONS] ?? [];

        if (str_contains($type, '\\')) {
            return $this->createConstraintFromClassName($type, $message, $options);
        }

        return $this->createConstraintFromShortName($type, $message, $options, $settingType);
    }

    /**
     * @param string $className
     * @param string|null $message
     * @param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     *
     * @return \Symfony\Component\Validator\Constraint
     */
    protected function createConstraintFromClassName(string $className, ?string $message, array $options): Constraint
    {
        $constraintOptions = $options;

        if ($message !== null) {
            $constraintOptions['message'] = $message;
        }

        try {
            /** @var \Symfony\Component\Validator\Constraint $constraintObject */
            $constraintObject = new $className($constraintOptions ?: null);
        } catch (Exception $e) {
            throw new InvalidArgumentException(sprintf('Unknown constraint class: %s', $className));
        }

        return $constraintObject;
    }

    /**
     * @param string $shortName
     * @param string|null $message
     * @param array<string, mixed> $options
     * @param string|null $settingType
     *
     * @throws \InvalidArgumentException
     *
     * @return \Symfony\Component\Validator\Constraint
     */
    protected function createConstraintFromShortName(string $shortName, ?string $message, array $options, ?string $settingType = null): Constraint
    {
        if ($this->shouldUseLengthConstraint($shortName, $settingType)) {
            return $this->createLengthConstraintForMinMax($shortName, $message, $options);
        }

        if (!isset(static::SHORT_NAME_TO_CLASS_MAP[$shortName])) {
            throw new InvalidArgumentException(sprintf('Unknown constraint type: %s', $shortName));
        }

        $constraintOptions = $this->buildConstraintOptions($shortName, $message, $options);
        $className = static::SHORT_NAME_TO_CLASS_MAP[$shortName];

        return new $className($constraintOptions ?: null);
    }

    protected function shouldUseLengthConstraint(string $shortName, ?string $settingType): bool
    {
        if ($shortName !== ConfigurationSchemaConstants::CONSTRAINT_TYPE_MIN && $shortName !== ConfigurationSchemaConstants::CONSTRAINT_TYPE_MAX) {
            return false;
        }

        if ($settingType === null) {
            return false;
        }

        return !isset(static::NUMERIC_SETTING_TYPES[$settingType]);
    }

    /**
     * @param string $shortName
     * @param string|null $message
     * @param array<string, mixed> $options
     *
     * @return \Symfony\Component\Validator\Constraints\Length
     */
    protected function createLengthConstraintForMinMax(string $shortName, ?string $message, array $options): Length
    {
        $constraintOptions = [];

        if ($shortName === ConfigurationSchemaConstants::CONSTRAINT_TYPE_MIN) {
            $constraintOptions['min'] = $options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MIN];

            if ($message !== null) {
                $constraintOptions['minMessage'] = $message;
            }
        }

        if ($shortName === ConfigurationSchemaConstants::CONSTRAINT_TYPE_MAX) {
            $constraintOptions['max'] = $options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MAX];

            if ($message !== null) {
                $constraintOptions['maxMessage'] = $message;
            }
        }

        return new Length($constraintOptions);
    }

    /**
     * @param string $shortName
     * @param string|null $message
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    protected function buildConstraintOptions(string $shortName, ?string $message, array $options): array
    {
        $constraintOptions = [];

        if ($message !== null) {
            $constraintOptions['message'] = $message;
        }

        return match ($shortName) {
            ConfigurationSchemaConstants::CONSTRAINT_TYPE_REQUIRED,
            ConfigurationSchemaConstants::CONSTRAINT_TYPE_EMAIL,
            ConfigurationSchemaConstants::CONSTRAINT_TYPE_URL => $constraintOptions,

            ConfigurationSchemaConstants::CONSTRAINT_TYPE_MIN => array_merge($constraintOptions, [
                'value' => $options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MIN],
            ]),

            ConfigurationSchemaConstants::CONSTRAINT_TYPE_MAX => array_merge($constraintOptions, [
                'value' => $options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MAX],
            ]),

            ConfigurationSchemaConstants::CONSTRAINT_TYPE_REGEX => array_merge($constraintOptions, [
                'pattern' => sprintf('/%s/', $options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_PATTERN]),
            ]),

            ConfigurationSchemaConstants::CONSTRAINT_TYPE_CHOICE => array_merge($constraintOptions, [
                'choices' => $options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_CHOICES],
            ]),

            ConfigurationSchemaConstants::CONSTRAINT_TYPE_LENGTH => $this->buildLengthOptions($constraintOptions, $options),

            ConfigurationSchemaConstants::CONSTRAINT_TYPE_RANGE => $this->buildRangeOptions($constraintOptions, $options),

            default => $constraintOptions,
        };
    }

    /**
     * @param array<string, mixed> $constraintOptions
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    protected function buildLengthOptions(array $constraintOptions, array $options): array
    {
        if (isset($options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MIN])) {
            $constraintOptions['min'] = $options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MIN];
        }

        if (isset($options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MAX])) {
            $constraintOptions['max'] = $options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MAX];
        }

        return $constraintOptions;
    }

    /**
     * @param array<string, mixed> $constraintOptions
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    protected function buildRangeOptions(array $constraintOptions, array $options): array
    {
        if (isset($constraintOptions['message'])) {
            $constraintOptions['invalidMessage'] = $constraintOptions['message'];
            unset($constraintOptions['message']);
        }

        if (isset($options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MIN])) {
            $constraintOptions['min'] = $options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MIN];
        }

        if (isset($options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MAX])) {
            $constraintOptions['max'] = $options[ConfigurationSchemaConstants::CONSTRAINT_OPTION_MAX];
        }

        return $constraintOptions;
    }
}
