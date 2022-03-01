<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Action\ProcessDeprecatedAction;
use Magento\CloudPatches\Command\Process\Action\RevertAction;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Status\StatusPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ProcessDeprecatedActionTest extends TestCase
{
    /**
     * @var ProcessDeprecatedAction
     */
    private $action;

    /**
     * @var Renderer|MockObject
     */
    private $renderer;

    /**
     * @var StatusPool|MockObject
     */
    private $statusPool;

    /**
     * @var OptionalPool|MockObject
     */
    private $optionalPool;

    /**
     * @var Aggregator|MockObject
     */
    private $aggregator;

    /**
     * @var RevertAction|MockObject
     */
    private $revertAction;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->optionalPool = $this->createMock(OptionalPool::class);
        $this->statusPool = $this->createMock(StatusPool::class);
        $this->renderer = $this->createMock(Renderer::class);
        $this->aggregator = $this->createMock(Aggregator::class);
        $this->revertAction = $this->createMock(RevertAction::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->action = new ProcessDeprecatedAction(
            $this->optionalPool,
            $this->statusPool,
            $this->revertAction,
            $this->aggregator,
            $this->renderer,
            $this->logger
        );
    }

    /**
     * Tests successful processing patch list with deprecated patches.
     */
    public function testProcessDeprecationSuccessful()
    {
        $patch1 = $this->createPatch('MC-11111', true, 'MC-22222');
        $patchFilter = [$patch1->getId()];
        $expectedMessage = sprintf(
            'Warning! Deprecated patch %s is going to be applied. Please, replace it with %s',
            $patch1->getId(),
            $patch1->getReplacedWith()
        );

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $patchMock = $this->getMockForAbstractClass(PatchInterface::class);

        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->withConsecutive([$patchFilter])
            ->willReturn([$patchMock]);
        $this->optionalPool->expects($this->once())
            ->method('getReplacedBy')
            ->withConsecutive([$patch1->getId()])
            ->willReturn([]);

        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->willReturn([$patch1]);

        $outputMock->expects($this->once())
            ->method('writeln')
            ->withConsecutive([$this->stringContains($expectedMessage)]);

        $this->renderer->expects($this->once())
            ->method('printQuestion')
            ->willReturn(true);

        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests a case when user rejected to apply deprecated patches.
     */
    public function testProcessDeprecationException()
    {
        $patch1 = $this->createPatch('MC-11111', true);
        $patchFilter = [$patch1->getId()];

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $patchMock = $this->getMockForAbstractClass(PatchInterface::class);

        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->withConsecutive([$patchFilter])
            ->willReturn([$patchMock]);

        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->willReturn([$patch1]);

        $this->renderer->expects($this->once())
            ->method('printQuestion')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests successful processing patch list with applied patches that require replacement.
     */
    public function testProcessReplacementSuccessful()
    {
        $requireReplacement = ['MC-22222', 'MC-33333'];
        $patch1 = $this->createPatch('MC-11111', false);
        $this->statusPool->method('isApplied')
            ->willReturnMap([
                ['MC-22222', true],
                ['MC-33333', true]
            ]);
        $patchFilter = [$patch1->getId()];
        $expectedMessage = sprintf(
            '%s should be reverted and replaced with %s',
            implode(' ', $requireReplacement),
            $patch1->getId()
        );

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $patchMock = $this->getMockForAbstractClass(PatchInterface::class);

        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->withConsecutive([$patchFilter])
            ->willReturn([$patchMock]);

        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->willReturn([$patch1]);

        $this->optionalPool->expects($this->once())
            ->method('getReplacedBy')
            ->withConsecutive([$patch1->getId()])
            ->willReturn($requireReplacement);

        $outputMock->expects($this->once())
            ->method('writeln')
            ->withConsecutive([$this->stringContains($expectedMessage)]);

        $this->renderer->expects($this->once())
            ->method('printQuestion')
            ->willReturn(true);

        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests successful skipping of replacement check when patch is already applied.
     */
    public function testSkippingReplacementProcessForAppliedPatch()
    {
        $patch1 = $this->createPatch('MC-11111', false);
        $this->statusPool->method('isApplied')
            ->willReturnMap([
                ['MC-11111', true]
            ]);
        $patchFilter = [$patch1->getId()];

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $patchMock = $this->getMockForAbstractClass(PatchInterface::class);

        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->withConsecutive([$patchFilter])
            ->willReturn([$patchMock]);

        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->willReturn([$patch1]);

        $this->renderer->expects($this->never())
            ->method('printQuestion');

        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests a case when user rejected to revert deprecated patches before applying a new one.
     */
    public function testProcessReplacementException()
    {
        $requireReplacement = ['MC-22222', 'MC-33333'];
        $patch1 = $this->createPatch('MC-11111', false);
        $this->statusPool->method('isApplied')
            ->willReturnMap([
                ['MC-22222', true],
                ['MC-33333', true]
            ]);
        $patchFilter = [$patch1->getId()];

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $patchMock = $this->getMockForAbstractClass(PatchInterface::class);

        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->withConsecutive([$patchFilter])
            ->willReturn([$patchMock]);

        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->willReturn([$patch1]);

        $this->optionalPool->expects($this->once())
            ->method('getReplacedBy')
            ->withConsecutive([$patch1->getId()])
            ->willReturn($requireReplacement);

        $this->renderer->expects($this->once())
            ->method('printQuestion')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests with empty patch filter.
     *
     * Don't need to check patches for deprecation and replacement.
     */
    public function testWithEmptyPatchFilter()
    {
        $patchFilter = [];
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->aggregator->expects($this->never())
            ->method('aggregate');

        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     * @param bool $isDeprecated
     * @param string $replacedWith
     * @return AggregatedPatchInterface|MockObject
     */
    private function createPatch(string $id, bool $isDeprecated = false, string $replacedWith = '')
    {
        $patch = $this->getMockForAbstractClass(AggregatedPatchInterface::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('isDeprecated')->willReturn($isDeprecated);
        $patch->method('getReplacedWith')->willReturn($replacedWith);

        return $patch;
    }
}
