<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Mapper;

use Generated\Shared\Transfer\FileManagerDataTransfer;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileUploadMapperInterface
{
    public function mapUploadedFileToFileManagerDataTransfer(
        UploadedFile $uploadedFile,
        string $storageName,
        FileManagerDataTransfer $fileManagerDataTransfer,
    ): FileManagerDataTransfer;
}
