<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Form;

use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class FileUploadForm extends AbstractType
{
    public const string FIELD_FILE = 'file';

    public const string FIELD_SETTING_KEY = 'settingKey';

    public const string OPTION_SETTING_KEY = 'setting_key';

    public const string OPTION_VALID_SETTING_KEYS = 'valid_setting_keys';

    public const string OPTION_MAX_FILE_SIZE = 'max_file_size';

    public const string OPTION_ALLOWED_MIME_TYPES = 'allowed_mime_types';

    public const string OPTION_ALLOWED_EXTENSIONS = 'allowed_extensions';

    protected const string ERROR_FILE_REQUIRED = 'Please select a file to upload.';

    protected const string ERROR_INVALID_MIME_TYPE = 'The uploaded file type is not allowed.';

    protected const string ERROR_INVALID_SETTING_KEY = 'Invalid setting key.';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this
            ->addFileField($builder, $options)
            ->addSettingKeyField($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            static::OPTION_SETTING_KEY => '',
            static::OPTION_VALID_SETTING_KEYS => [],
            static::OPTION_MAX_FILE_SIZE => '',
            static::OPTION_ALLOWED_MIME_TYPES => [],
            static::OPTION_ALLOWED_EXTENSIONS => [],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array<string, mixed> $options
     *
     * @return static
     */
    public function addFileField(FormBuilderInterface $builder, array $options): static
    {
        $constraints = [new NotBlank(['message' => static::ERROR_FILE_REQUIRED])];

        if ($options[static::OPTION_ALLOWED_MIME_TYPES]) {
            $constraints[] = new File([
                'maxSize' => $options[static::OPTION_MAX_FILE_SIZE],
                'mimeTypes' => $options[static::OPTION_ALLOWED_MIME_TYPES],
                'mimeTypesMessage' => static::ERROR_INVALID_MIME_TYPE,
            ]);
        }

        $builder->add(static::FIELD_FILE, FileType::class, [
            'label' => false,
            'required' => true,
            'mapped' => false,
            'attr' => ['accept' => implode(',', $options[static::OPTION_ALLOWED_EXTENSIONS])],
            'constraints' => $constraints,
        ]);

        return $this;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array<string, mixed> $options
     *
     * @return static
     */
    public function addSettingKeyField(FormBuilderInterface $builder, array $options): static
    {
        $validSettingKeys = $options[static::OPTION_VALID_SETTING_KEYS];

        $builder->add(static::FIELD_SETTING_KEY, HiddenType::class, [
            'data' => $options[static::OPTION_SETTING_KEY],
            'mapped' => false,
            'constraints' => [
                new Callback(function (string $value, ExecutionContextInterface $context) use ($validSettingKeys): void {
                    if (in_array($value, $validSettingKeys, true)) {
                        return;
                    }

                    $context->addViolation(static::ERROR_INVALID_SETTING_KEY);
                }),
            ],
        ]);

        return $this;
    }
}
