<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Creator;

use Generated\Shared\Transfer\ConfigurationFileUploadCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationFileUploadCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationFileUploadTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\FileSystemQueryTransfer;
use Spryker\Service\FileSystem\FileSystemServiceInterface;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\FileManager\Business\FileManagerFacadeInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Throwable;

class ConfigurationFileUploadCreator implements ConfigurationFileUploadCreatorInterface
{
    use LoggerTrait;
    use TransactionTrait;

    protected const string ERROR_MESSAGE_KEY_FILE_UPLOAD_ERROR = 'An error occurred while saving the file.';

    public function __construct(
        protected FileManagerFacadeInterface $fileManagerFacade,
        protected FileSystemServiceInterface $fileSystemService,
    ) {
    }

    public function createFileUploadCollection(
        ConfigurationFileUploadCollectionRequestTransfer $configurationFileUploadCollectionRequestTransfer,
    ): ConfigurationFileUploadCollectionResponseTransfer {
        $configurationFileUploadCollectionResponseTransfer = new ConfigurationFileUploadCollectionResponseTransfer();

        return $this->getTransactionHandler()->handleTransaction(function () use ($configurationFileUploadCollectionRequestTransfer, $configurationFileUploadCollectionResponseTransfer) {
            return $this->executeCreateFileUploadCollectionTransaction($configurationFileUploadCollectionRequestTransfer, $configurationFileUploadCollectionResponseTransfer);
        });
    }

    protected function executeCreateFileUploadCollectionTransaction(
        ConfigurationFileUploadCollectionRequestTransfer $configurationFileUploadCollectionRequestTransfer,
        ConfigurationFileUploadCollectionResponseTransfer $configurationFileUploadCollectionResponseTransfer,
    ): ConfigurationFileUploadCollectionResponseTransfer {
        foreach ($configurationFileUploadCollectionRequestTransfer->getFileUploads() as $entityIdentifier => $fileUploadTransfer) {
            $configurationFileUploadCollectionResponseTransfer->addFileUpload(
                $this->saveFile($entityIdentifier, $fileUploadTransfer, $configurationFileUploadCollectionResponseTransfer),
            );

            if ($configurationFileUploadCollectionRequestTransfer->getIsTransactional() && $configurationFileUploadCollectionResponseTransfer->getErrors()->count() > 0) {
                return $configurationFileUploadCollectionResponseTransfer;
            }
        }

        return $configurationFileUploadCollectionResponseTransfer;
    }

    protected function saveFile(
        int $entityIdentifier,
        ConfigurationFileUploadTransfer $configurationFileUploadTransfer,
        ConfigurationFileUploadCollectionResponseTransfer $configurationFileUploadCollectionResponseTransfer,
    ): ConfigurationFileUploadTransfer {
        try {
            $fileManagerDataTransfer = $this->fileManagerFacade->saveFile(
                $configurationFileUploadTransfer->getFileManagerDataOrFail(),
            );

            $url = $this->fileSystemService->getPublicUrl(
                (new FileSystemQueryTransfer())
                    ->setFileSystemName($fileManagerDataTransfer->getFileInfo()?->getStorageNameOrFail() ?? '')
                    ->setPath($fileManagerDataTransfer->getFile()?->getFileNameOrFail() ?? ''),
            );

            return $configurationFileUploadTransfer->setUrl($url);
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), ['exception' => $throwable]);

            $configurationFileUploadCollectionResponseTransfer->addError(
                (new ErrorTransfer())
                    ->setMessage(static::ERROR_MESSAGE_KEY_FILE_UPLOAD_ERROR)
                    ->setEntityIdentifier((string)$entityIdentifier),
            );

            return $configurationFileUploadTransfer;
        }
    }
}
