<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Collector;

use Magento\CloudPatches\Patch\Collector\LocalCollector;
use Magento\CloudPatches\Patch\Data\Patch;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\PatchBuilder;
use Magento\CloudPatches\Patch\SourceProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class LocalCollectorTest extends TestCase
{
    /**
     * @var LocalCollector
     */
    private $collector;

    /**
     * @var PatchBuilder|MockObject
     */
    private $patchBuilder;

    /**
     * @var SourceProvider|MockObject
     */
    private $sourceProvider;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->patchBuilder = $this->createMock(PatchBuilder::class);
        $this->sourceProvider = $this->createMock(SourceProvider::class);

        $this->collector = new LocalCollector(
            $this->sourceProvider,
            $this->patchBuilder
        );
    }

    /**
     * Tests collecting local patches.
     */
    public function testCollect()
    {
        $file1 =  __DIR__ . SourceProvider::HOT_FIXES_DIR . '/patch1.patch';
        $file2 =  __DIR__ . SourceProvider::HOT_FIXES_DIR . '/patch2.patch';
        $shortPath1 = '../' . SourceProvider::HOT_FIXES_DIR . '/patch1.patch';
        $shortPath2 = '../' . SourceProvider::HOT_FIXES_DIR . '/patch2.patch';

        $this->sourceProvider->expects($this->once())
            ->method('getLocalPatches')
            ->willReturn([$file1, $file2]);

        $this->patchBuilder->expects($this->exactly(2))
            ->method('setId')
            ->withConsecutive([$shortPath1], [$shortPath2]);
        $this->patchBuilder->expects($this->exactly(2))
            ->method('setTitle')
            ->withConsecutive(
                [$shortPath1],
                [$shortPath2]
            );
        $this->patchBuilder->expects($this->exactly(2))
            ->method('setFilename')
            ->withConsecutive(['patch1.patch'], ['patch2.patch']);
        $this->patchBuilder->expects($this->exactly(2))
            ->method('setPath')
            ->withConsecutive([$file1], [$file2]);
        $this->patchBuilder->expects($this->exactly(2))
            ->method('setType')
            ->withConsecutive([PatchInterface::TYPE_CUSTOM], [PatchInterface::TYPE_CUSTOM]);
        $this->patchBuilder->expects($this->exactly(2))
            ->method('build')
            ->willReturn($this->createMock(Patch::class));

        $this->assertTrue(is_array($this->collector->collect()));
    }
}
