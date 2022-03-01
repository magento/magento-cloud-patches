<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Conflict;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Conflict\Analyzer as ConflictAnalyzer;
use Magento\CloudPatches\Patch\Conflict\Processor as ConflictProcessor;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\RollbackProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class ProcessorTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ConflictAnalyzer|MockObject
     */
    private $conflictAnalyzer;

    /**
     * @var RollbackProcessor|MockObject
     */
    private $rollbackProcessor;

    /**
     * @var Renderer|MockObject
     */
    private $renderer;

    /**
     * @var ConflictProcessor
     */
    private $conflictProcessor;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->renderer = $this->createMock(Renderer::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->conflictAnalyzer = $this->createMock(ConflictAnalyzer::class);
        $this->rollbackProcessor = $this->createMock(RollbackProcessor::class);

        $this->conflictProcessor = new ConflictProcessor(
            $this->renderer,
            $this->logger,
            $this->conflictAnalyzer,
            $this->rollbackProcessor
        );
    }

    /**
     * Tests patch conflict processing.
     */
    public function testProcess()
    {
        $appliedPatch1 = $this->createPatch('MC-1', 'path1');
        $appliedPatch2 = $this->createPatch('MC-2', 'path2');
        $failedPatch = $this->createPatch('MC-3', 'path3');
        $exceptionMessage = 'exceptionMessage';
        $conflictDetails = 'Conflict details';
        $rollbackMessages = ['Patch 1 has been reverted', 'Patch 2 has been reverted'];

        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->rollbackProcessor->expects($this->once())
            ->method('process')
            ->withConsecutive([[$appliedPatch1, $appliedPatch2]])
            ->willReturn($rollbackMessages);
        $this->conflictAnalyzer->expects($this->once())
            ->method('analyze')
            ->withConsecutive([$failedPatch])
            ->willReturn($conflictDetails);
        $outputMock->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [$this->stringContains('Error: patch ' . $failedPatch->getId() . ' can\'t be applied')],
                [$rollbackMessages]
            );

        $expectedErrorMessage = sprintf(
            'Applying patch %s (%s) failed.%s%s',
            $failedPatch->getId(),
            $failedPatch->getPath(),
            PHP_EOL .$exceptionMessage,
            PHP_EOL . $conflictDetails
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedErrorMessage);

        $this->conflictProcessor->process(
            $outputMock,
            $failedPatch,
            [$appliedPatch1, $appliedPatch2],
            $exceptionMessage
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
