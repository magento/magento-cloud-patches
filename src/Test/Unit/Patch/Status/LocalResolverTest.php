<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Status;

use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Filesystem\FileSystemException;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\Data\Patch;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Status\LocalResolver;
use Magento\CloudPatches\Patch\Status\OptionalResolver;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Magento\CloudPatches\Patch\Status\StatusResolverException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class LocalResolverTest extends TestCase
{
    /**
     * @var Filesystem|MockObject
     */
    private $filesystem;

    /**
     * @var Applier|MockObject
     */
    private $applier;

    /**
     * @var LocalPool|MockObject
     */
    private $localPool;

    /**
     * @var OptionalResolver
     */
    private $resolver;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->applier = $this->createMock(Applier::class);
        $this->localPool = $this->createMock(LocalPool::class);

        $this->resolver = new LocalResolver(
            $this->filesystem,
            $this->applier,
            $this->localPool
        );
    }

    /**
     * Tests resolving patch statuses.
     */
    public function testResolve()
    {
        $patch1 = $this->createPatch('/path/patch1.patch');
        $patch2 = $this->createPatch('/path/patch2.patch');
        $patch3 = $this->createPatch('/path/patch3.patch');

        $this->localPool->expects($this->once())
            ->method('getList')
            ->willReturn([$patch1, $patch2, $patch3]);

        $this->filesystem->method('get')
            ->willReturnMap([
                [$patch1->getPath(), 'Content '. $patch1->getId()],
                [$patch2->getPath(), 'Content '. $patch2->getId()],
                [$patch3->getPath(), 'Content '. $patch3->getId()]
            ]);

        $this->applier->expects($this->exactly(3))
            ->method('status')
            ->willReturnMap([
                ['Content ' . $patch1->getId(), StatusPool::NOT_APPLIED],
                ['Content ' . $patch2->getId(), StatusPool::APPLIED],
                ['Content ' . $patch3->getId(), StatusPool::NA]
            ]);

        $expectedResult = [
            $patch1->getId() => StatusPool::NOT_APPLIED,
            $patch2->getId() => StatusPool::APPLIED,
            $patch3->getId() => StatusPool::NA
        ];

        $this->assertEquals($expectedResult, $this->resolver->resolve());
    }

    /**
     * Tests a case when exception happens during reading patch content.
     */
    public function testResolveWithException()
    {
        $patch = $this->createPatch('/path/patch.patch');

        $this->localPool->expects($this->once())
            ->method('getList')
            ->willReturn([$patch]);

        $this->filesystem->method('get')
            ->willThrowException(new FileSystemException(''));

        $this->expectException(StatusResolverException::class);
        $this->resolver->resolve();
    }

    /**
     * Creates patch mock.
     *
     * @param string $path
     * @return Patch|MockObject
     */
    private function createPatch(string $path)
    {
        $patch = $this->createMock(Patch::class);
        $patch->method('getId')->willReturn(md5($path));
        $patch->method('getPath')->willReturn($path);

        return $patch;
    }
}
