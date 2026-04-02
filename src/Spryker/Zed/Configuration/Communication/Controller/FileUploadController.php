<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Controller;

use Generated\Shared\Transfer\ConfigurationFileUploadCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationFileUploadCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationFileUploadTransfer;
use Generated\Shared\Transfer\FileManagerDataTransfer;
use Spryker\Zed\Configuration\Communication\Form\FileUploadForm;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory getFactory()
 * @method \Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface getFacade()
 */
class FileUploadController extends AbstractController
{
    protected const string RESPONSE_KEY_SUCCESS = 'success';

    protected const string RESPONSE_KEY_ERRORS = 'errors';

    protected const string RESPONSE_KEY_URL = 'url';

    protected const string OPTION_STORAGE_NAME = 'storage_name';

    public function uploadAction(Request $request): JsonResponse
    {
        $settingKey = $request->request->getString(FileUploadForm::FIELD_SETTING_KEY);
        $fileUploadSettings = $this->getFactory()
            ->createFileUploadSettingReader()
            ->getFileUploadConfig($settingKey);

        $form = $this->getFactory()
            ->createFileUploadForm($fileUploadSettings, $settingKey)
            ->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->createFormValidationErrorResponse($form);
        }

        $configurationFileUploadCollectionResponseTransfer = $this->getFacade()->createConfigurationFileUploadCollection(
            $this->buildFileUploadCollectionRequest($form, $fileUploadSettings),
        );

        if ($configurationFileUploadCollectionResponseTransfer->getErrors()->count() > 0) {
            return $this->createUploadErrorResponse($configurationFileUploadCollectionResponseTransfer);
        }

        return $this->jsonResponse([
            static::RESPONSE_KEY_SUCCESS => true,
            static::RESPONSE_KEY_URL => $configurationFileUploadCollectionResponseTransfer->getFileUploads()->offsetGet(0)->getUrl() ?? '',
        ]);
    }

    /**
     * @param \Symfony\Component\Form\FormInterface $form
     * @param array<string, mixed> $fileUploadSettings
     *
     * @return \Generated\Shared\Transfer\ConfigurationFileUploadCollectionRequestTransfer
     */
    protected function buildFileUploadCollectionRequest(FormInterface $form, array $fileUploadSettings): ConfigurationFileUploadCollectionRequestTransfer
    {
        $uploadedFile = $form->get(FileUploadForm::FIELD_FILE)->getData();
        $storageName = $fileUploadSettings[static::OPTION_STORAGE_NAME] ?? '';

        $fileManagerDataTransfer = $this->getFactory()
            ->createFileUploadMapper()
            ->mapUploadedFileToFileManagerDataTransfer($uploadedFile, $storageName, new FileManagerDataTransfer());

        return (new ConfigurationFileUploadCollectionRequestTransfer())
            ->addFileUpload(
                (new ConfigurationFileUploadTransfer())
                    ->setFileManagerData($fileManagerDataTransfer),
            );
    }

    protected function createFormValidationErrorResponse(FormInterface $form): JsonResponse
    {
        $translatorFacade = $this->getFactory()->getTranslatorFacade();

        $errors = [];
        /** @var \Symfony\Component\Form\FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $translatorFacade->trans($error->getMessage());
        }

        return $this->jsonResponse(
            [static::RESPONSE_KEY_SUCCESS => false, static::RESPONSE_KEY_ERRORS => $errors],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    protected function createUploadErrorResponse(
        ConfigurationFileUploadCollectionResponseTransfer $configurationFileUploadCollectionResponseTransfer
    ): JsonResponse {
        $translatorFacade = $this->getFactory()->getTranslatorFacade();

        $errors = [];
        foreach ($configurationFileUploadCollectionResponseTransfer->getErrors() as $errorTransfer) {
            $errors[] = $translatorFacade->trans((string)$errorTransfer->getMessage());
        }

        return $this->jsonResponse(
            [static::RESPONSE_KEY_SUCCESS => false, static::RESPONSE_KEY_ERRORS => $errors],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }
}
