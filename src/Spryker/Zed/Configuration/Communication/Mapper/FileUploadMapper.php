<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Mapper;

use Generated\Shared\Transfer\FileInfoTransfer;
use Generated\Shared\Transfer\FileManagerDataTransfer;
use Generated\Shared\Transfer\FileTransfer;
use Generated\Shared\Transfer\FileUploadTransfer;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadMapper implements FileUploadMapperInterface
{
    public function mapUploadedFileToFileManagerDataTransfer(
        UploadedFile $uploadedFile,
        string $storageName,
        FileManagerDataTransfer $fileManagerDataTransfer,
    ): FileManagerDataTransfer {
        $fileUploadTransfer = $this->mapUploadedFileToFileUploadTransfer($uploadedFile, new FileUploadTransfer());

        $fileTransfer = (new FileTransfer())
            ->setFileName($uploadedFile->getClientOriginalName())
            ->setFileUpload($fileUploadTransfer);

        $fileInfoTransfer = (new FileInfoTransfer())
            ->setExtension($uploadedFile->getClientOriginalExtension())
            ->setSize($uploadedFile->getSize())
            ->setType($uploadedFile->getMimeType())
            ->setStorageName($storageName);

        $fileContent = file_get_contents((string)$uploadedFile->getRealPath());

        return $fileManagerDataTransfer
            ->setFile($fileTransfer)
            ->setFileInfo($fileInfoTransfer)
            ->setContent($fileContent !== false ? $fileContent : '');
    }

    public function mapUploadedFileToFileUploadTransfer(
        UploadedFile $uploadedFile,
        FileUploadTransfer $fileUploadTransfer,
    ): FileUploadTransfer {
        return $fileUploadTransfer
            ->setClientOriginalName($uploadedFile->getClientOriginalName())
            ->setClientOriginalExtension($uploadedFile->getClientOriginalExtension())
            ->setSize($uploadedFile->getSize())
            ->setMimeTypeName($uploadedFile->getMimeType())
            ->setRealPath($uploadedFile->getRealPath());
    }
}
