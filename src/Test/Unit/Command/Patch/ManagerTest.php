<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Patch;

use Composer\Package\RootPackageInterface;
use Magento\CloudPatches\Command\Apply;
use Magento\CloudPatches\Command\Patch\Manager;
use Magento\CloudPatches\Command\Patch\ManagerException;
use Magento\CloudPatches\Filesystem\DirectoryList;
use Magento\CloudPatches\Filesystem\FileList;
use Magento\CloudPatches\Filesystem\FileNotFoundException;
use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ManagerTest extends TestCase
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var Applier|MockObject
     */
    private $applierMock;

    /**
     * @var RootPackageInterface|MockObject
     */
    private $composerPackageMock;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystemMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->applierMock = $this->createMock(Applier::class);
        $this->composerPackageMock = $this->getMockForAbstractClass(RootPackageInterface::class);
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->manager = new Manager(
            $this->applierMock,
            $this->filesystemMock,
            $this->fileListMock,
            $this->directoryListMock
        );
    }

    /**
     * @throws ApplierException
     * @throws ManagerException
     */
    public function testApplyComposerPatches()
    {
        $this->filesystemMock->expects($this->once())
            ->method('get')
            ->willReturn(json_encode(
                [
                    'package1' => [
                        'patchName1' => [
                            '100' => 'patchPath1',
                        ],
                    ],
                    'package2' => [
                        'patchName2' => [
                            '101.*' => 'patchPath2',
                        ],
                        'patchName3' => [
                            '102.*' => 'patchPath3',
                        ],
                    ]
                ]
            ));
        $this->applierMock->expects($this->exactly(3))
            ->method('apply')
            ->withConsecutive(
                ['patchPath1', 'patchName1', 'package1', '100'],
                ['patchPath2', 'patchName2', 'package2', '101.*'],
                ['patchPath3', 'patchName3', 'package2', '102.*']
            );

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $inputMock->method('getOption')
            ->with(Apply::OPT_GIT_INSTALLATION)
            ->willReturn(false);

        $this->manager->applyComposerPatches($inputMock, $outputMock);
    }

    /**
     * @expectedException \Magento\CloudPatches\Command\Patch\ManagerException
     * @expectedExceptionMessage Not Found
     *
     * @throws ApplierException
     * @throws ManagerException
     */
    public function testApplyComposerPatchesWithFSException()
    {
        $this->filesystemMock->expects($this->once())
            ->method('get')
            ->willThrowException(new FileNotFoundException('Not Found'));

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $inputMock->method('getOption')
            ->with(Apply::OPT_GIT_INSTALLATION)
            ->willReturn(false);

        $this->manager->applyComposerPatches($inputMock, $outputMock);
    }

    /**
     * @throws ApplierException
     */
    public function testExecuteApplyHotFixes()
    {
        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/_files');
        $this->filesystemMock->expects($this->once())
            ->method('isDirectory')
            ->willReturn(true);
        $this->applierMock->expects($this->exactly(2))
            ->method('applyFile')
            ->willReturnMap([
                [__DIR__ . '/_files/' . Manager::HOT_FIXES_DIR . '/patch1.patch', false, 'Patch 1 applied'],
                [__DIR__ . '/_files/' . Manager::HOT_FIXES_DIR . '/patch2.patch', false, 'Patch 2 applied']
            ]);

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $inputMock->expects($this->once())
            ->method('getOption')
            ->with(Apply::OPT_GIT_INSTALLATION)
            ->willReturn(false);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $outputMock->expects($this->exactly(3))
            ->method('writeln')
            ->withConsecutive(
                ['Applying hot-fixes', 0],
                ['Patch 1 applied', 0],
                ['Patch 2 applied', 0]
            );

        $this->manager->applyHotFixes($inputMock, $outputMock);
    }

    /**
     * @throws ApplierException
     */
    public function testExecuteApplyHotFixesNotFound()
    {
        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/_files');
        $this->filesystemMock->expects($this->once())
            ->method('isDirectory')
            ->willReturn(false);
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $outputMock->expects($this->once())
            ->method('writeln')
            ->withConsecutive(
                ['Hot-fixes directory was not found. Skipping', 0]
            );

        $this->manager->applyHotFixes($inputMock, $outputMock);
    }
}
