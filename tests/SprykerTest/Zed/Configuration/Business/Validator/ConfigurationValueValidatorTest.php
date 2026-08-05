<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business\Validator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;
use Spryker\Zed\Configuration\Business\Validator\ConfigurationConstraintMapper;
use Spryker\Zed\Configuration\Business\Validator\ConfigurationValueValidator;
use Spryker\Zed\Configuration\Business\Validator\ConfigurationValueValidatorInterface;
use Spryker\Zed\Translator\Business\TranslatorFacadeInterface;
use SprykerTest\Zed\Configuration\ConfigurationBusinessTester;
use Symfony\Component\Validator\Validation;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Validator
 * @group ConfigurationValueValidatorTest
 * Add your own group annotations below this line
 */
class ConfigurationValueValidatorTest extends Unit
{
    protected const string SETTING_KEY = 'catalog:general:display:items_per_page';

    protected const string CONSTRAINT_MESSAGE = 'Value must not exceed {{ compared_value }}.';

    protected const string CONSTRAINT_MESSAGE_TRANSLATION = 'Der Wert darf {{ compared_value }} nicht überschreiten.';

    protected ConfigurationBusinessTester $tester;

    public function testValidateTranslatesConstraintMessage(): void
    {
        // Arrange
        $validator = $this->createConfigurationValueValidator([
            static::CONSTRAINT_MESSAGE => static::CONSTRAINT_MESSAGE_TRANSLATION,
        ]);

        $configurationValueTransfer = (new ConfigurationValueTransfer())
            ->setSettingKey(static::SETTING_KEY)
            ->setValue('42');

        // Act
        $configurationValidationResponseTransfer = $validator->validate($configurationValueTransfer);

        // Assert
        $this->assertFalse($configurationValidationResponseTransfer->getIsValid());
        $this->assertCount(1, $configurationValidationResponseTransfer->getErrors());
        $this->assertSame(
            'Der Wert darf 10 nicht überschreiten.',
            $configurationValidationResponseTransfer->getErrors()[0]->getMessage(),
        );
    }

    public function testValidateKeepsUntranslatedConstraintMessageWithSubstitutedParameters(): void
    {
        // Arrange
        $validator = $this->createConfigurationValueValidator([]);

        $configurationValueTransfer = (new ConfigurationValueTransfer())
            ->setSettingKey(static::SETTING_KEY)
            ->setValue('42');

        // Act
        $configurationValidationResponseTransfer = $validator->validate($configurationValueTransfer);

        // Assert
        $this->assertFalse($configurationValidationResponseTransfer->getIsValid());
        $this->assertSame(
            'Value must not exceed 10.',
            $configurationValidationResponseTransfer->getErrors()[0]->getMessage(),
        );
    }

    /**
     * @param array<string, string> $translations
     */
    protected function createConfigurationValueValidator(array $translations): ConfigurationValueValidatorInterface
    {
        $configurationSchemaProviderMock = $this->createMock(ConfigurationSchemaProviderInterface::class);
        $configurationSchemaProviderMock->method('getSettingsMap')->willReturn([
            static::SETTING_KEY => [
                'type' => 'integer',
                'constraints' => [
                    ['type' => 'max', 'message' => static::CONSTRAINT_MESSAGE, 'options' => ['max' => 10]],
                ],
            ],
        ]);

        return new ConfigurationValueValidator(
            $configurationSchemaProviderMock,
            new ConfigurationConstraintMapper(),
            Validation::createValidatorBuilder()
                ->setTranslator($this->createTranslatorFacadeMock($translations))
                ->getValidator(),
        );
    }

    /**
     * @param array<string, string> $translations
     */
    protected function createTranslatorFacadeMock(array $translations): TranslatorFacadeInterface
    {
        $translatorFacadeMock = $this->createMock(TranslatorFacadeInterface::class);
        $translatorFacadeMock->method('trans')->willReturnCallback(
            // Mimics the Symfony translator: an unknown message id is returned as is, with the parameters substituted.
            function (string $id, array $parameters = []) use ($translations): string {
                return strtr($translations[$id] ?? $id, $parameters);
            },
        );

        return $translatorFacadeMock;
    }
}
