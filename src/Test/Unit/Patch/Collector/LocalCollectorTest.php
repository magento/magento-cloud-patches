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
use Magento\CloudPatches\Patch\PatchFactory;
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
     * @var PatchFactory|MockObject
     */
    private $patchFactory;

    /**
     * @var SourceProvider|MockObject
     */
    private $sourceProvider;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->patchFactory = $this->createMock(PatchFactory::class);
        $this->sourceProvider = $this->createMock(SourceProvider::class);

        $this->collector = new LocalCollector(
            $this->patchFactory,
            $this->sourceProvider
        );
    }

    /**
     * Tests collecting local patches.
     */
    public function testCollect()
    {
        $file1 =  __DIR__ . SourceProvider::HOT_FIXES_DIR . '/patch1.patch';
        $file2 =  __DIR__ . SourceProvider::HOT_FIXES_DIR . '/patch2.patch';

        $this->sourceProvider->expects($this->once())
            ->method('getLocalPatches')
            ->willReturn([$file1, $file2]);

        $this->patchFactory->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [
                    md5($file1),
                    '../' . SourceProvider::HOT_FIXES_DIR . '/patch1.patch',
                    $file1,
                    $file1,
                    PatchInterface::TYPE_CUSTOM,
                    '',
                    '',
                    [],
                    '',
                    false
                ],
                [
                    md5($file2),
                    '../' . SourceProvider::HOT_FIXES_DIR . '/patch2.patch',
                    $file2,
                    $file2,
                    PatchInterface::TYPE_CUSTOM,
                    '',
                    '',
                    [],
                    '',
                    false
                ]
            )->willReturn(
                $this->createMock(Patch::class)
            );

        $this->assertTrue(is_array($this->collector->collect()));
    }
}
