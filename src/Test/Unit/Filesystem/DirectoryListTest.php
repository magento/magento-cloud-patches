<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Filesystem;

use Magento\CloudPatches\Filesystem\DirectoryList;
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
    protected function setUp()
    {
        $this->directoryList = new DirectoryList(
            $this->root,
            $this->magentoRoot
        );
    }

    public function testGetRoot()
    {
        $this->assertSame(
            $this->root,
            $this->directoryList->getRoot()
        );
    }

    public function testGetMagentoRoot()
    {
        $this->assertSame(
            $this->magentoRoot,
            $this->directoryList->getMagentoRoot()
        );
    }

    public function testGetPatches()
    {
        $this->assertSame(
            $this->root . '/patches',
            $this->directoryList->getPatches()
        );
    }
}
