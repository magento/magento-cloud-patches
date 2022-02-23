<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Status;

use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Filesystem\FileSystemException;
use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\Data\AggregatedPatch;
use Magento\CloudPatches\Patch\Data\Patch;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Status\OptionalResolver;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Magento\CloudPatches\Patch\Status\StatusResolverException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class OptionalResolverTest extends TestCase
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
     * @var Aggregator|MockObject
     */
    private $aggregator;

    /**
     * @var OptionalPool|MockObject
     */
    private $optionalPool;

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
        $this->aggregator = $this->createMock(Aggregator::class);
        $this->optionalPool = $this->createMock(OptionalPool::class);

        $this->resolver = new OptionalResolver(
            $this->filesystem,
            $this->applier,
            $this->aggregator,
            $this->optionalPool
        );
    }

    /**
     * Tests resolving patch statuses for patches without dependencies.
     */
    public function testResolveForIndependentPatches()
    {
        $patch1 = $this->createPatch('MC-1');
        $patch2 = $this->createPatch('MC-2');
        $patch3 = $this->createPatch('MC-3');

        $patchMock = $this->getMockForAbstractClass(PatchInterface::class);
        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->willReturn([$patchMock]);

        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->willReturn([$patch1, $patch2, $patch3]);

        $this->filesystem->method('get')
            ->willReturnMap([
                ['path/' . $patch1->getId(), 'Content '. $patch1->getId()],
                ['path/' . $patch2->getId(), 'Content '. $patch2->getId()],
                ['path/' . $patch3->getId(), 'Content '. $patch3->getId()],
            ]);

        $this->applier->expects($this->exactly(3))
            ->method('status')
            ->willReturnMap([
                ['Content ' . $patch1->getId(), StatusPool::APPLIED],
                ['Content ' . $patch2->getId(), StatusPool::NOT_APPLIED],
                ['Content ' . $patch3->getId(), StatusPool::NA],
            ]);

        $expectedResult = [
            $patch1->getId() => StatusPool::APPLIED,
            $patch2->getId() => StatusPool::NOT_APPLIED,
            $patch3->getId() => StatusPool::NA,
        ];

        $this->assertEquals($expectedResult, $this->resolver->resolve());
    }

    /**
     * Tests resolving patch statuses for patches with dependencies.
     *
     * Status is defined using combined patch that contains all not applied dependencies.
     */
    public function testResolveForDependentPatches()
    {
        $patch1 = $this->createPatch('MC-1');
        $patch2 = $this->createPatch('MC-2', ['MC-1']);
        $patch3 = $this->createPatch('MC-3', ['MC-2']);

        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->willReturn([$patch1, $patch2, $patch3]);

        $this->filesystem->method('get')
            ->willReturnMap([
                ['path/' . $patch1->getId(), 'Content '. $patch1->getId()],
                ['path/' . $patch2->getId(), 'Content '. $patch2->getId()],
                ['path/' . $patch3->getId(), 'Content '. $patch3->getId()],
            ]);

        $this->optionalPool->method('getList')
            ->willReturnMap([
                [[], true, []],
                [[$patch2->getId()], true, [$patch1->getItems()[0], $patch2->getItems()[0]]],
                [[$patch3->getId()], true, [$patch1->getItems()[0], $patch2->getItems()[0], $patch3->getItems()[0]]],
            ]);

        $contentPatch1 = 'Content ' . $patch1->getId();
        $contentPatch2 = 'Content ' . $patch2->getId();
        $contentPatch3 = 'Content ' . $patch3->getId();
        $this->applier->expects($this->exactly(3))
            ->method('status')
            ->willReturnMap([
                [$contentPatch1, StatusPool::NOT_APPLIED],
                [$contentPatch1 . $contentPatch2, StatusPool::NOT_APPLIED],
                [$contentPatch1 . $contentPatch2 . $contentPatch3, StatusPool::APPLIED],
            ]);

        $expectedResult = [
            $patch1->getId() => StatusPool::NOT_APPLIED,
            $patch2->getId() => StatusPool::NOT_APPLIED,
            $patch3->getId() => StatusPool::APPLIED,
        ];

        $this->assertEquals($expectedResult, $this->resolver->resolve());
    }

    /**
     * Tests resolving patch statuses for conflicting cases,
     * when status can be defined only after analysis of applied dependencies.
     */
    public function testResolveForDependentPatchesWithConflicts()
    {
        $patch1 = $this->createPatch('MC-1');
        $patch2 = $this->createPatch('MC-2', ['MC-1']);
        $patch3 = $this->createPatch('MC-3', ['MC-2']);

        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->willReturn([$patch1, $patch2, $patch3]);

        $this->filesystem->method('get')
            ->willReturnMap([
                ['path/' . $patch1->getId(), 'Content '. $patch1->getId()],
                ['path/' . $patch2->getId(), 'Content '. $patch2->getId()],
                ['path/' . $patch3->getId(), 'Content '. $patch3->getId()],
            ]);

        $this->optionalPool->method('getList')
            ->willReturnMap([
                [[], true, []],
                [[$patch2->getId()], true, [$patch1->getItems()[0], $patch2->getItems()[0]]],
                [[$patch3->getId()], true, [$patch1->getItems()[0], $patch2->getItems()[0], $patch3->getItems()[0]]],
            ]);

        $contentPatch1 = 'Content ' . $patch1->getId();
        $contentPatch2 = 'Content ' . $patch2->getId();
        $contentPatch3 = 'Content ' . $patch3->getId();
        $this->applier->expects($this->exactly(3))
            ->method('status')
            ->willReturnMap([
                [$contentPatch1, StatusPool::NA],
                [$contentPatch2, StatusPool::NA],
                [$contentPatch3, StatusPool::APPLIED],
            ]);

        $expectedResult = [
            $patch1->getId() => StatusPool::APPLIED,
            $patch2->getId() => StatusPool::APPLIED,
            $patch3->getId() => StatusPool::APPLIED,
        ];

        $this->assertEquals($expectedResult, $this->resolver->resolve());
    }

    /**
     * Tests a case when exception happens during reading patch content.
     */
    public function testResolveWithException()
    {
        $patch1 = $this->createPatch('MC-1');

        $patchMock = $this->getMockForAbstractClass(PatchInterface::class);
        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->willReturn([$patchMock]);

        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->willReturn([$patch1]);

        $this->filesystem->method('get')
            ->willThrowException(new FileSystemException(''));

        $this->expectException(StatusResolverException::class);
        $this->resolver->resolve();
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     * @param array $require
     * @return AggregatedPatch|MockObject
     */
    private function createPatch(string $id, array $require = [])
    {
        $patch = $this->createMock(Patch::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('getPath')->willReturn('path/' . $id);
        $aggregatedPatch = $this->createMock(AggregatedPatch::class);
        $aggregatedPatch->method('getId')->willReturn($id);
        $aggregatedPatch->method('getRequire')->willReturn($require);
        $aggregatedPatch->method('getItems')->willReturn([$patch]);

        // To make mock object unique for assertions and array operations.
        $aggregatedPatch->id = microtime();

        return $aggregatedPatch;
    }
}
