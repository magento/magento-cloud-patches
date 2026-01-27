<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Filesystem;

use Magento\CloudPatches\Filesystem\DirectoryList;
use Magento\CloudPatches\Filesystem\FileList;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class FileListTest extends TestCase
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var DirectoryList
     */
    private $directoryListMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->directoryListMock->method('getRoot')
            ->willReturn('root');

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->fileList = new FileList(
            $this->directoryListMock
        );
    }

    /**
     * Tests retrieving patches.json file path.
    *
    * @return void
    */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetPatches(): void
    {
        $this->assertSame(
            'root/patches.json',
            $this->fileList->getPatches()
        );
    }

    /**
     * Tests retrieving .magento.env.yaml file path.
    *
    * @return void
    */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetPatchLog(): void
    {
        $this->assertSame(
            'magento_root/var/log/patch.log',
            $this->fileList->getPatchLog()
        );
    }
}
