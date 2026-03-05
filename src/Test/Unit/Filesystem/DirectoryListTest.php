<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Filesystem;

use Magento\CloudPatches\Filesystem\DirectoryList;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class DirectoryListTest extends TestCase
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var string
     */
    private $root = __DIR__;

    /**
     * @var string
     */
    private $magentoRoot = __DIR__ . '/_files';

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->directoryList = new DirectoryList(
            $this->root,
            $this->magentoRoot
        );
    }

    /**
     * Tests retrieving root directory.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetRoot(): void
    {
        $this->assertSame(
            $this->root,
            $this->directoryList->getRoot()
        );
    }

    /**
     * Tests retrieving Magento root directory.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetMagentoRoot(): void
    {
        $this->assertSame(
            $this->magentoRoot,
            $this->directoryList->getMagentoRoot()
        );
    }

    /**
     * Tests retrieving the patches directory path.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetPatches(): void
    {
        $this->assertSame(
            $this->root . '/patches',
            $this->directoryList->getPatches()
        );
    }
}
