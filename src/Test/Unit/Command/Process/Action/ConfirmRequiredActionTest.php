<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Action\ConfirmRequiredAction;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Pool\PatchNotFoundException;
use Magento\CloudPatches\Patch\Status\StatusPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ConfirmRequiredActionTest extends TestCase
{
    /**
     * @var ConfirmRequiredAction
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
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->statusPool = $this->createMock(StatusPool::class);
        $this->optionalPool = $this->createMock(OptionalPool::class);
        $this->renderer = $this->createMock(Renderer::class);
        $this->aggregator = $this->createMock(Aggregator::class);

        $this->action = new ConfirmRequiredAction(
            $this->optionalPool,
            $this->statusPool,
            $this->aggregator,
            $this->renderer
        );
    }

    /**
     * Tests asking confirmation for not applied patches.
     */
    public function testAskConfirmationForNotAppliedPatches()
    {
        $patch1 = $this->createPatch('/path/patch1.patch', 'MC-11111');
        $patch2 = $this->createPatch('/path/patch2.patch', 'MC-22222');
        $patch3 = $this->createPatch('/path/patch3.patch', 'MC-33333');
        $patchFilter = [$patch1->getId(), $patch2->getId(), $patch3->getId()];
        $this->statusPool->method('isApplied')
            ->willReturnMap([
                ['MC-11111', false],
                ['MC-22222', false],
                ['MC-33333', true]
            ]);

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->optionalPool->expects($this->once())
            ->method('getAdditionalRequiredPatches')
            ->withConsecutive([$patchFilter])
            ->willReturn([$patch1, $patch2, $patch3]);

        $aggregatedPatch = $this->getMockForAbstractClass(AggregatedPatchInterface::class);
        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->with([$patch1, $patch2])
            ->willReturn([$aggregatedPatch]);

        $this->renderer->expects($this->once())
            ->method('printTable')
            ->withConsecutive([$outputMock, [$aggregatedPatch]]);

        $this->renderer->expects($this->once())
            ->method('printQuestion')
            ->willReturn(true);

        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests exception when patch from filter is not found.
     */
    public function testPatchNotFoundException()
    {
        $patchFilter = ['unknown id'];

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->optionalPool->expects($this->once())
            ->method('getAdditionalRequiredPatches')
            ->withConsecutive([$patchFilter])
            ->willThrowException(new PatchNotFoundException(''));

        $this->expectException(RuntimeException::class);
        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests exception when user refused to confirm applying additional patches.
     */
    public function testConfirmationRejected()
    {
        $patch1 = $this->createPatch('/path/patch1.patch', 'MC-11111');
        $patchFilter = [$patch1->getId()];
        $this->statusPool->method('isApplied')
            ->willReturnMap([
                [$patch1->getId(), false]
            ]);

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->optionalPool->expects($this->once())
            ->method('getAdditionalRequiredPatches')
            ->withConsecutive([$patchFilter])
            ->willReturn([$patch1]);

        $aggregatedPatch = $this->getMockForAbstractClass(AggregatedPatchInterface::class);
        $this->aggregator->expects($this->once())
            ->method('aggregate')
            ->with([$patch1])
            ->willReturn([$aggregatedPatch]);

        $this->renderer->expects($this->once())
            ->method('printTable')
            ->withConsecutive([$outputMock, [$aggregatedPatch]]);

        $this->renderer->expects($this->once())
            ->method('printQuestion')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Creates patch mock.
     *
     * @param string $path
     * @param string $id
     * @param bool $isDeprecated
     *
     * @return PatchInterface|MockObject
     */
    private function createPatch(string $path, string $id, bool $isDeprecated = false)
    {
        $patch = $this->getMockForAbstractClass(PatchInterface::class);
        $patch->method('getPath')->willReturn($path);
        $patch->method('getFilename')->willReturn('filename.patch');
        $patch->method('getId')->willReturn($id);
        $patch->method('isDeprecated')->willReturn($isDeprecated);

        return $patch;
    }
}
