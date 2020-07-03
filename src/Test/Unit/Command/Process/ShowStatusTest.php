<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process;

use Magento\CloudPatches\Command\Process\Action\ReviewAppliedAction;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Command\Process\ShowStatus;
use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Status\StatusPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ShowStatusTest extends TestCase
{
    /**
     * @var ShowStatus
     */
    private $manager;

    /**
     * @var ReviewAppliedAction|MockObject
     */
    private $reviewAppliedAction;

    /**
     * @var Renderer|MockObject
     */
    private $renderer;

    /**
     * @var Aggregator|MockObject
     */
    private $aggregator;

    /**
     * @var OptionalPool|MockObject
     */
    private $optionalPool;

    /**
     * @var StatusPool|MockObject
     */
    private $statusPool;

    /**
     * @var LocalPool|MockObject
     */
    private $localPool;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->aggregator = $this->createMock(Aggregator::class);
        $this->optionalPool = $this->createMock(OptionalPool::class);
        $this->localPool = $this->createMock(LocalPool::class);
        $this->statusPool = $this->createMock(StatusPool::class);
        $this->reviewAppliedAction = $this->createMock(ReviewAppliedAction::class);
        $this->renderer = $this->createMock(Renderer::class);

        $this->manager = new ShowStatus(
            $this->aggregator,
            $this->optionalPool,
            $this->localPool,
            $this->statusPool,
            $this->reviewAppliedAction,
            $this->renderer
        );
    }

    /**
     * Tests show status.
     *
     * Patch 1 - deprecated, applied - show warning message, show patch in the table;
     * Patch 2 - not deprecated, not applied - no warning message, show patch in the table;
     * Patch 3 - deprecated, not applied - no warning message, don't show patch in the table;
     */
    public function testShowStatus()
    {
        $patch1 = $this->createPatch('patch1', true);
        $patch2 = $this->createPatch('patch2', false);
        $patch3 = $this->createPatch('patch3', true);
        $this->statusPool->method('isApplied')
            ->willReturnMap([
                ['patch1', true],
                ['patch2', false],
                ['patch3', false],
            ]);

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $patchMock = $this->getMockForAbstractClass(PatchInterface::class);

        $this->reviewAppliedAction->expects($this->once())
            ->method('execute')
            ->withConsecutive([$inputMock, $outputMock, []]);
        $this->optionalPool->method('getList')
            ->willReturn([$patchMock]);
        $this->localPool->method('getList')
            ->willReturn([$patchMock]);

        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->willReturn([$patch1, $patch2, $patch3]);

        // Show warning message about patch deprecation
        $outputMock->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [$this->anything()],
                [$this->stringContains('Deprecated patch ' . $patch1->getId() . ' is currently applied')]
            );

        // Show patches in the table
        $this->renderer->expects($this->once())
            ->method('printTable')
            ->withConsecutive([$outputMock, [$patch1, $patch2]]);

        $this->manager->run($inputMock, $outputMock);
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     * @param bool $isDeprecated
     *
     * @return AggregatedPatchInterface|MockObject
     */
    private function createPatch(string $id, bool $isDeprecated)
    {
        $patch = $this->getMockForAbstractClass(AggregatedPatchInterface::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('isDeprecated')->willReturn($isDeprecated);

        return $patch;
    }
}
