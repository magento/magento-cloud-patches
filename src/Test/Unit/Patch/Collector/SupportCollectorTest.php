<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Collector;

use Magento\CloudPatches\Composer\Package;
use Magento\CloudPatches\Composer\QualityPackage;
use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\Collector\GetPatchesConfigInterface;
use Magento\CloudPatches\Patch\Collector\SupportCollector;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\PatchBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class SupportCollectorTest extends TestCase
{
    /** @var Package|MockObject */
    private $packageMock;

    /** @var QualityPackage|MockObject */
    private $qualityPackageMock;

    /** @var PatchBuilder|MockObject */
    private $patchBuilderMock;

    /** @var GetPatchesConfigInterface|MockObject */
    private $getPatchesConfigMock;

    /** @var SupportCollector */
    private SupportCollector $supportCollector;

    /**
     * Sets up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->packageMock = $this->createMock(Package::class);
        $this->qualityPackageMock = $this->createMock(QualityPackage::class);
        $this->patchBuilderMock = $this->createMock(PatchBuilder::class);
        $this->getPatchesConfigMock = $this->createMock(GetPatchesConfigInterface::class);

        $this->supportCollector = new SupportCollector(
            $this->packageMock,
            $this->qualityPackageMock,
            $this->patchBuilderMock,
            $this->getPatchesConfigMock
        );
    }

    /**
     * Tests collecting patches from configuration.
     *
     * @return void
     * @throws CollectorException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testCollect(): void
    {
        $config = [
            'patch-1' => [
                'title' => 'Patch 1',
                'packages' => [
                    'magento/module-test' => [
                        '1.0.0' => [
                            'file' => 'patch1.diff',
                            'require' => ['patch-2'],
                        ]
                    ]
                ]
            ]
        ];

        $this->getPatchesConfigMock->expects($this->once())
            ->method('execute')
            ->willReturn($config);

        $this->packageMock->expects($this->once())
            ->method('matchConstraint')
            ->with('magento/module-test', '1.0.0')
            ->willReturn(true);

        $this->qualityPackageMock->expects($this->once())
            ->method('getPatchesDirectoryPath')
            ->willReturn('/patches');

        $this->patchBuilderMock->expects($this->once())->method('setId')->with('patch-1');
        $this->patchBuilderMock->expects($this->once())->method('setTitle')->with('Patch 1');
        $this->patchBuilderMock->expects($this->once())->method('setFilename')->with('patch1.diff');
        $this->patchBuilderMock->expects($this->once())->method('setPath')->with('/patches/patch1.diff');
        $this->patchBuilderMock->expects($this->once())->method('setRequire')->with(['patch-2']);

        $patchMock = $this->createMock(PatchInterface::class);
        $this->patchBuilderMock->expects($this->once())
            ->method('build')
            ->willReturn($patchMock);

        $result = $this->supportCollector->collect();
        $this->assertCount(1, $result);
        $this->assertSame($patchMock, $result[0]);
    }
}
