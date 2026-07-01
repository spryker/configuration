<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationFileUploadCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationFileUploadTransfer;
use Generated\Shared\Transfer\FileInfoTransfer;
use Generated\Shared\Transfer\FileManagerDataTransfer;
use Generated\Shared\Transfer\FileTransfer;
use RuntimeException;
use Spryker\Service\FileSystem\FileSystemServiceInterface;
use Spryker\Zed\Configuration\ConfigurationDependencyProvider;
use Spryker\Zed\FileManager\Business\FileManagerFacadeInterface;
use SprykerTest\Zed\Configuration\ConfigurationBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Facade
 * @group CreateConfigurationFileUploadCollectionFacadeTest
 * Add your own group annotations below this line
 */
class CreateConfigurationFileUploadCollectionFacadeTest extends Unit
{
    protected const string TEST_FILE_NAME = 'test-file.pdf';

    protected const string TEST_STORAGE_NAME = 'files';

    protected const string TEST_PUBLIC_URL = 'https://cdn.example.com/test-file.pdf';

    protected ConfigurationBusinessTester $tester;

    public function testSavesFileAndResolvesPublicUrl(): void
    {
        // Arrange
        $this->tester->setDependency(
            ConfigurationDependencyProvider::FACADE_FILE_MANAGER,
            $this->createFileManagerFacadeMock($this->createFileManagerDataTransfer()),
        );
        $this->tester->setDependency(
            ConfigurationDependencyProvider::SERVICE_FILE_SYSTEM,
            $this->createFileSystemServiceMock(static::TEST_PUBLIC_URL),
        );

        $configurationFileUploadCollectionRequestTransfer = $this->createRequestTransfer(true, [
            $this->createFileUploadTransfer(),
        ]);

        // Act
        $configurationFileUploadCollectionResponseTransfer = $this->tester->getFacade()
            ->createConfigurationFileUploadCollection($configurationFileUploadCollectionRequestTransfer);

        // Assert
        $this->assertCount(0, $configurationFileUploadCollectionResponseTransfer->getErrors());
        $this->assertCount(1, $configurationFileUploadCollectionResponseTransfer->getFileUploads());
        $this->assertSame(static::TEST_PUBLIC_URL, $configurationFileUploadCollectionResponseTransfer->getFileUploads()->offsetGet(0)->getUrl());
    }

    public function testAddsErrorOnSaveFailure(): void
    {
        // Arrange
        $fileManagerFacadeMock = $this->createMock(FileManagerFacadeInterface::class);
        $fileManagerFacadeMock->method('saveFile')
            ->willThrowException(new RuntimeException('Save failed'));

        $this->tester->setDependency(ConfigurationDependencyProvider::FACADE_FILE_MANAGER, $fileManagerFacadeMock);
        $this->tester->setDependency(
            ConfigurationDependencyProvider::SERVICE_FILE_SYSTEM,
            $this->createMock(FileSystemServiceInterface::class),
        );

        $configurationFileUploadCollectionRequestTransfer = $this->createRequestTransfer(true, [
            $this->createFileUploadTransfer(),
        ]);

        // Act
        $configurationFileUploadCollectionResponseTransfer = $this->tester->getFacade()
            ->createConfigurationFileUploadCollection($configurationFileUploadCollectionRequestTransfer);

        // Assert
        $this->assertCount(1, $configurationFileUploadCollectionResponseTransfer->getErrors());
        $this->assertCount(1, $configurationFileUploadCollectionResponseTransfer->getFileUploads());
        $this->assertNull($configurationFileUploadCollectionResponseTransfer->getFileUploads()->offsetGet(0)->getUrl());
    }

    public function testStopsOnFirstErrorInTransactionalMode(): void
    {
        // Arrange
        $fileManagerFacadeMock = $this->createMock(FileManagerFacadeInterface::class);
        $fileManagerFacadeMock->expects($this->once())
            ->method('saveFile')
            ->willThrowException(new RuntimeException('Save failed'));

        $this->tester->setDependency(ConfigurationDependencyProvider::FACADE_FILE_MANAGER, $fileManagerFacadeMock);
        $this->tester->setDependency(
            ConfigurationDependencyProvider::SERVICE_FILE_SYSTEM,
            $this->createMock(FileSystemServiceInterface::class),
        );

        $configurationFileUploadCollectionRequestTransfer = $this->createRequestTransfer(true, [
            $this->createFileUploadTransfer(),
            $this->createFileUploadTransfer(),
        ]);

        // Act
        $configurationFileUploadCollectionResponseTransfer = $this->tester->getFacade()
            ->createConfigurationFileUploadCollection($configurationFileUploadCollectionRequestTransfer);

        // Assert
        $this->assertCount(1, $configurationFileUploadCollectionResponseTransfer->getErrors());
        $this->assertCount(1, $configurationFileUploadCollectionResponseTransfer->getFileUploads());
    }

    public function testContinuesProcessingInNonTransactionalMode(): void
    {
        // Arrange
        $successTransfer = $this->createFileManagerDataTransfer();
        $callCount = 0;

        $fileManagerFacadeMock = $this->createMock(FileManagerFacadeInterface::class);
        $fileManagerFacadeMock->expects($this->exactly(2))
            ->method('saveFile')
            ->willReturnCallback(function () use (&$callCount, $successTransfer): FileManagerDataTransfer {
                $callCount++;

                if ($callCount === 1) {
                    throw new RuntimeException('Save failed');
                }

                return $successTransfer;
            });

        $this->tester->setDependency(ConfigurationDependencyProvider::FACADE_FILE_MANAGER, $fileManagerFacadeMock);
        $this->tester->setDependency(
            ConfigurationDependencyProvider::SERVICE_FILE_SYSTEM,
            $this->createFileSystemServiceMock(static::TEST_PUBLIC_URL),
        );

        $configurationFileUploadCollectionRequestTransfer = $this->createRequestTransfer(false, [
            $this->createFileUploadTransfer(),
            $this->createFileUploadTransfer(),
        ]);

        // Act
        $configurationFileUploadCollectionResponseTransfer = $this->tester->getFacade()
            ->createConfigurationFileUploadCollection($configurationFileUploadCollectionRequestTransfer);

        // Assert
        $this->assertCount(1, $configurationFileUploadCollectionResponseTransfer->getErrors());
        $this->assertCount(2, $configurationFileUploadCollectionResponseTransfer->getFileUploads());
        $this->assertNull($configurationFileUploadCollectionResponseTransfer->getFileUploads()->offsetGet(0)->getUrl());
        $this->assertSame(static::TEST_PUBLIC_URL, $configurationFileUploadCollectionResponseTransfer->getFileUploads()->offsetGet(1)->getUrl());
    }

    public function testProcessesMultipleFilesSuccessfully(): void
    {
        // Arrange
        $this->tester->setDependency(
            ConfigurationDependencyProvider::FACADE_FILE_MANAGER,
            $this->createFileManagerFacadeMock($this->createFileManagerDataTransfer()),
        );
        $this->tester->setDependency(
            ConfigurationDependencyProvider::SERVICE_FILE_SYSTEM,
            $this->createFileSystemServiceMock(static::TEST_PUBLIC_URL),
        );

        $configurationFileUploadCollectionRequestTransfer = $this->createRequestTransfer(true, [
            $this->createFileUploadTransfer(),
            $this->createFileUploadTransfer(),
        ]);

        // Act
        $configurationFileUploadCollectionResponseTransfer = $this->tester->getFacade()
            ->createConfigurationFileUploadCollection($configurationFileUploadCollectionRequestTransfer);

        // Assert
        $this->assertCount(0, $configurationFileUploadCollectionResponseTransfer->getErrors());
        $this->assertCount(2, $configurationFileUploadCollectionResponseTransfer->getFileUploads());
        $this->assertSame(static::TEST_PUBLIC_URL, $configurationFileUploadCollectionResponseTransfer->getFileUploads()->offsetGet(0)->getUrl());
        $this->assertSame(static::TEST_PUBLIC_URL, $configurationFileUploadCollectionResponseTransfer->getFileUploads()->offsetGet(1)->getUrl());
    }

    protected function createFileManagerDataTransfer(): FileManagerDataTransfer
    {
        return (new FileManagerDataTransfer())
            ->setFile((new FileTransfer())->setFileName(static::TEST_FILE_NAME))
            ->setFileInfo((new FileInfoTransfer())->setStorageName(static::TEST_STORAGE_NAME));
    }

    protected function createFileManagerFacadeMock(FileManagerDataTransfer $returnTransfer): FileManagerFacadeInterface
    {
        $mock = $this->createMock(FileManagerFacadeInterface::class);
        $mock->method('saveFile')->willReturn($returnTransfer);

        return $mock;
    }

    protected function createFileSystemServiceMock(string $publicUrl): FileSystemServiceInterface
    {
        $mock = $this->createMock(FileSystemServiceInterface::class);
        $mock->method('getPublicUrl')->willReturn($publicUrl);

        return $mock;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ConfigurationFileUploadTransfer> $configurationFileUploadTransfers
     */
    protected function createRequestTransfer(bool $isTransactional, array $configurationFileUploadTransfers): ConfigurationFileUploadCollectionRequestTransfer
    {
        $configurationFileUploadCollectionRequestTransfer = (new ConfigurationFileUploadCollectionRequestTransfer())
            ->setIsTransactional($isTransactional);

        foreach ($configurationFileUploadTransfers as $configurationFileUploadTransfer) {
            $configurationFileUploadCollectionRequestTransfer->addFileUpload($configurationFileUploadTransfer);
        }

        return $configurationFileUploadCollectionRequestTransfer;
    }

    protected function createFileUploadTransfer(): ConfigurationFileUploadTransfer
    {
        return (new ConfigurationFileUploadTransfer())
            ->setFileManagerData(new FileManagerDataTransfer());
    }
}
