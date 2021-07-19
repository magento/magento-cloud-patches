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
use Magento\CloudPatches\Console\QuestionFactory;
use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Data\AggregatedPatch;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Status\StatusPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Console\Helper\QuestionHelper
     */
    private $questionHelper;

    /**
     * @var \Magento\CloudPatches\Console\QuestionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $questionFactory;

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
        $this->questionHelper = $this->createMock(QuestionHelper::class);
        $this->questionFactory = $this->createMock(QuestionFactory::class);

        $this->manager = new ShowStatus(
            $this->aggregator,
            $this->optionalPool,
            $this->localPool,
            $this->statusPool,
            $this->reviewAppliedAction,
            $this->renderer,
            $this->questionHelper,
            $this->questionFactory
        );
    }

    /**
     * Tests show status.
     *
     * Patch 1 - deprecated, applied - show warning message, show patch in the table;
     * Patch 2 - not deprecated, not applied - no warning message, show patch in the table;
     * Patch 3 - deprecated, not applied - no warning message, don't show patch in the table;
     * Patch 4 - deprecated, applied and replaced with applied patch4-v2 - don't show patch in the table;
     * Patch 5 - deprecated, applied and replaced with not applied patch5-v2 - show patch in the table;
     */
    public function testShowStatus()
    {
        $patch1 = $this->createPatch('patch1', true);
        $patch2 = $this->createPatch('patch2', false);
        $patch3 = $this->createPatch('patch3', true);
        $patch4 = $this->createPatch('patch4', true, 'patch4-v2');
        $patch5 = $this->createPatch('patch5', true, 'patch5-v2');
        $this->statusPool->method('isApplied')
            ->willReturnMap([
                ['patch1', true],
                ['patch2', false],
                ['patch3', false],
                ['patch4', true],
                ['patch4-v2', true],
                ['patch5', true],
                ['patch5-v2', false],
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
            ->willReturn([$patch1, $patch2, $patch3, $patch4, $patch5]);

        // Show warning message about patch deprecation
        $outputMock->expects($this->exactly(3))
            ->method('writeln')
            ->withConsecutive(
                [$this->anything()],
                [$this->stringContains('Deprecated patch ' . $patch1->getId() . ' is currently applied')]
            );

        // Show patches in the table
        $this->renderer->expects($this->once())
            ->method('printTable')
            ->withConsecutive([$outputMock, [$patch1, $patch2, $patch5]]);

        $this->manager->run($inputMock, $outputMock);
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     * @param bool $isDeprecated
     * @param string $replacedWith
     * @return AggregatedPatchInterface|MockObject
     */
    private function createPatch(string $id, bool $isDeprecated, string $replacedWith = '')
    {
        $patch = $this->createMock(AggregatedPatch::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('isDeprecated')->willReturn($isDeprecated);
        $patch->method('getReplacedWith')->willReturn($replacedWith);

        // To make mock object unique for assertions and array operations.
        $patch->id = microtime();

        return $patch;
    }
}
