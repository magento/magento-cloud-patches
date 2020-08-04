<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Conflict;

use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\Conflict\ApplyChecker;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ApplyCheckerTest extends TestCase
{
    /**
     * @var OptionalPool|MockObject
     */
    private $optionalPool;

    /**
     * @var Applier|MockObject
     */
    private $applier;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystem;

    /**
     * @var ApplyChecker
     */
    private $applyChecker;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->optionalPool = $this->createMock(OptionalPool::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->applier = $this->createMock(Applier::class);

        $this->applyChecker = new ApplyChecker(
            $this->applier,
            $this->optionalPool,
            $this->filesystem
        );
    }

    /**
     * Tests patch apply checker.
     */
    public function testCheck()
    {
        $patchIds = ['MC-1', 'MC-2', 'MC-3'];
        $patch1 = $this->createPatch('MC-1', 'path1');
        $patch2 = $this->createPatch('MC-2', 'path2');
        $patch3 = $this->createPatch('MC-3', 'path3');

        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->withConsecutive([$patchIds])
            ->willReturn([$patch1, $patch2, $patch3]);
        $this->filesystem->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                [$patch1->getPath(), 'content1'],
                [$patch2->getPath(), 'content2'],
                [$patch3->getPath(), 'content3'],
            ]);
        $this->applier->expects($this->once())
            ->method('checkApply')
            ->with('content1content2content3')
            ->willReturn(true);

        $this->assertTrue(
            $this->applyChecker->check($patchIds)
        );
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     * @param string $path
     * @return PatchInterface|MockObject
     */
    private function createPatch(string $id, string $path)
    {
        $patch = $this->getMockForAbstractClass(PatchInterface::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('getPath')->willReturn($path);

        return $patch;
    }
}
