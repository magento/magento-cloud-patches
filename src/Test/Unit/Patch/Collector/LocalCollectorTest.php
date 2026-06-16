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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
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
    protected function setUp(): void
    {
        $this->patchBuilder = $this->createMock(PatchBuilder::class);
        $this->sourceProvider = $this->createMock(SourceProvider::class);

        $this->collector = new LocalCollector(
            $this->sourceProvider,
            $this->patchBuilder
        );
    }

    /**
     * Tests collecting local patches without custom-patches.json (fallback behaviour).
     *
     * When no custom config is present the id falls back to basename($file) and
     * the title falls back to the short relative path.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testCollect(): void
    {
        $file1 =  __DIR__ . SourceProvider::HOT_FIXES_DIR . '/patch1.patch';
        $file2 =  __DIR__ . SourceProvider::HOT_FIXES_DIR . '/patch2.patch';
        $shortPath1 = '../' . SourceProvider::HOT_FIXES_DIR . '/patch1.patch';
        $shortPath2 = '../' . SourceProvider::HOT_FIXES_DIR . '/patch2.patch';

        $this->sourceProvider->expects($this->once())
            ->method('getLocalPatches')
            ->willReturn([$file1, $file2]);

        // No custom config → empty array
        $this->sourceProvider->expects($this->once())
            ->method('getLocalPatchesConfig')
            ->willReturn([]);

        // Fallback id is basename($file), not the full short path
        $this->patchBuilder->expects($this->exactly(2))
            ->method('setId')
            ->with(
                $this->logicalOr($this->equalTo('patch1.patch'), $this->equalTo('patch2.patch'))
            );

        // Fallback title is still the full short path
        $this->patchBuilder->expects($this->exactly(2))
            ->method('setTitle')
            ->with(
                $this->logicalOr($this->equalTo($shortPath1), $this->equalTo($shortPath2))
            );

        $this->patchBuilder->expects($this->exactly(2))
            ->method('setFilename')
            ->willReturnCallback(function ($service) {
                static $services = [
                    'patch1.patch', 'patch2.patch'
                ];

                $expectedService = array_shift($services);
                $this->assertSame($expectedService, $service);
            });
        $this->patchBuilder->expects($this->exactly(2))
            ->method('setPath')
            ->willReturnCallback(function () use (&$callCount, $file1, $file2) {
                $callCount++;
                if ($callCount === 1) {
                    return $file1;
                } elseif ($callCount === 2) {
                    return $file2;
                }
            });

        $this->patchBuilder->expects($this->exactly(2))
            ->method('setType')
            ->willReturnCallback(function ($service) {
                static $services = [
                    PatchInterface::TYPE_CUSTOM,
                    PatchInterface::TYPE_CUSTOM
                ];

                $expectedService = array_shift($services);
                $this->assertSame($expectedService, $service);
            });
        $this->patchBuilder->expects($this->exactly(2))
            ->method('build')
            ->willReturn($this->createMock(Patch::class));

        $this->assertTrue(is_array($this->collector->collect()));
    }

    /**
     * Tests collecting local patches with a custom-patches.json config.
     *
     * When custom config is provided the id, title and categories are read from it.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testCollectWithCustomConfig(): void
    {
        $file1 = __DIR__ . SourceProvider::HOT_FIXES_DIR . '/patch1.patch';
        $shortPath1 = '../' . SourceProvider::HOT_FIXES_DIR . '/patch1.patch';

        $customConfig = [
            'patch1.patch' => [
                'id'         => 'HIB-000001',
                'title'      => 'My custom patch title',
                'categories' => ['Performance'],
            ],
        ];

        $this->sourceProvider->expects($this->once())
            ->method('getLocalPatches')
            ->willReturn([$file1]);

        $this->sourceProvider->expects($this->once())
            ->method('getLocalPatchesConfig')
            ->willReturn($customConfig);

        $this->patchBuilder->expects($this->once())
            ->method('setId')
            ->with('HIB-000001');

        $this->patchBuilder->expects($this->once())
            ->method('setTitle')
            ->with('My custom patch title');

        $this->patchBuilder->expects($this->once())
            ->method('setCategories')
            ->with(['Performance']);

        $this->patchBuilder->expects($this->once())
            ->method('setFilename')
            ->with('patch1.patch');

        $this->patchBuilder->expects($this->once())
            ->method('setPath')
            ->with($file1);

        $this->patchBuilder->expects($this->once())
            ->method('setType')
            ->with(PatchInterface::TYPE_CUSTOM);

        $this->patchBuilder->expects($this->once())
            ->method('build')
            ->willReturn($this->createMock(Patch::class));

        $result = $this->collector->collect();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }
}
