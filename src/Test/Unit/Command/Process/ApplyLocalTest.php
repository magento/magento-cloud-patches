<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\ApplyLocal;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\RollbackProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ApplyLocalTest extends TestCase
{
    /**
     * @var ApplyLocal
     */
    private $manager;

    /**
     * @var Applier|MockObject
     */
    private $applier;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var LocalPool|MockObject
     */
    private $localPool;

    /**
     * @var RollbackProcessor|MockObject
     */
    private $rollbackProcessor;

    /**
     * @var Renderer|MockObject
     */
    private $renderer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->applier = $this->createMock(Applier::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->localPool = $this->createMock(LocalPool::class);
        $this->renderer = $this->createMock(Renderer::class);
        $this->rollbackProcessor = $this->createMock(RollbackProcessor::class);

        $this->manager = new ApplyLocal(
            $this->applier,
            $this->localPool,
            $this->renderer,
            $this->logger,
            $this->rollbackProcessor
        );
    }

    /**
     * Tests case when there are no local patches in m2-hotfix directory.
     *
     * @throws RuntimeException
     */
    public function testExecuteLocalPatchesNotFound()
    {
        $expectedMessage = '<info>Hot-fixes were not found. Skipping</info>';

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->localPool->method('getList')
            ->willReturn([]);
        $outputMock->expects($this->once())
            ->method('writeln')
            ->with($expectedMessage);

        $this->manager->run($inputMock, $outputMock);
    }

    /**
     * Tests successful local patches applying.
     *
     * @throws RuntimeException
     */
    public function testApplySuccessful()
    {
        $patch1 = $this->createPatch('/path/patch1.patch', '../m2-hotfixes/patch1.patch');
        $patch2 = $this->createPatch('/path/patch2.patch', '../m2-hotfixes/patch2.patch');
        $patch3 = $this->createPatch('/path/patch3.patch', '../m2-hotfixes/patch3.patch');

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->localPool->method('getList')
            ->willReturn([$patch1, $patch2, $patch3]);

        $this->applier->method('apply')
            ->willReturnMap([
                [$patch1->getPath(), $patch1->getTitle(), 'Patch ' . $patch1->getTitle() .' has been applied'],
                [$patch2->getPath(), $patch2->getTitle(), 'Patch ' . $patch2->getTitle() .' has been applied'],
                [$patch3->getPath(), $patch3->getTitle(), 'Patch ' . $patch3->getTitle() .' has been applied'],
            ]);

        $outputMock->expects($this->exactly(4))
            ->method('writeln')
            ->withConsecutive(
                [$this->anything()],
                [$this->stringContains('Patch ' . $patch1->getTitle() .' has been applied')],
                [$this->stringContains('Patch ' . $patch2->getTitle() .' has been applied')],
                [$this->stringContains('Patch ' . $patch3->getTitle() .' has been applied')]
            );

        $this->manager->run($inputMock, $outputMock);
    }

    /**
     * Tests local patches applying with exception.
     *
     * @throws RuntimeException
     */
    public function testApplyWithException()
    {
        $patch1 = $this->createPatch('/path/patch1.patch', '../m2-hotfixes/patch1.patch');
        $patch2 = $this->createPatch('/path/patch2.patch', '../m2-hotfixes/patch2.patch');
        $rollbackMessages = ['Patch 1 has been reverted'];

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->localPool->method('getList')
            ->willReturn([$patch1, $patch2]);

        $this->applier->method('apply')
            ->willReturnMap([
                [$patch1->getPath(), $patch1->getTitle()],
                [$patch2->getPath(), $patch2->getTitle()]
            ])->willReturnCallback(
                function ($path, $title) {
                    if (strpos($title, 'patch2') !== false) {
                        throw new ApplierException('Applier error message');
                    }

                    return "Patch {$path} {$title} has been applied";
                }
            );

        $this->rollbackProcessor->expects($this->once())
            ->method('process')
            ->withConsecutive([[$patch1]])
            ->willReturn($rollbackMessages);

        $this->expectException(RuntimeException::class);
        $this->manager->run($inputMock, $outputMock);
    }

    /**
     * Creates patch mock.
     *
     * @param string $path
     * @param string $title
     *
     * @return PatchInterface|MockObject
     */
    private function createPatch(string $path, string $title)
    {
        $patch = $this->getMockForAbstractClass(PatchInterface::class);
        $patch->method('getPath')->willReturn($path);
        $patch->method('getTitle')->willReturn($title);

        return $patch;
    }
}
