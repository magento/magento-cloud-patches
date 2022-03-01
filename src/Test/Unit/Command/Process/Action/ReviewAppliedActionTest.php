<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Action\RevertAction;
use Magento\CloudPatches\Command\Process\Action\ReviewAppliedAction;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Pool\PatchNotFoundException;
use Magento\CloudPatches\Patch\RevertValidator;
use Magento\CloudPatches\Patch\Status\StatusPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ReviewAppliedActionTest extends TestCase
{
    /**
     * @var ReviewAppliedAction
     */
    private $action;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var StatusPool|MockObject
     */
    private $statusPool;

    /**
     * @var OptionalPool|MockObject
     */
    private $optionalPool;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->statusPool = $this->createMock(StatusPool::class);
        $this->optionalPool = $this->createMock(OptionalPool::class);

        $this->action = new ReviewAppliedAction(
            $this->optionalPool,
            $this->statusPool,
            $this->logger
        );
    }

    /**
     * Tests that warning message is shown when number of patches (filter + already applied) exceeds limit.
     */
    public function testAppliedPatchesExceedsLimit()
    {
        $filterSize = round(ReviewAppliedAction::UPGRADE_THRESHOLD / 2);
        $patchFilter = [];
        for ($i = 1; $i <= $filterSize; $i++) {
            $patchFilter[] = 'MC-' . $i;
        }

        $appliedPatches = [];
        for ($i = 1; $i <= (ReviewAppliedAction::UPGRADE_THRESHOLD - $filterSize); $i++) {
            $appliedPatches[] = $this->createPatch('MDVA-' . $i);
        }

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->statusPool->method('isApplied')
            ->willReturn(true);

        $this->optionalPool->expects($this->once())
            ->method('getOptionalListByOrigin')
            ->willReturn($appliedPatches);

        $outputMock->expects($this->once())
            ->method('writeln')
            ->withConsecutive([$this->stringContains('error')]);

        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests that warning message is not shown when number of applied patches doesn't exceed the limit.
     */
    public function testAppliedPatchesNotExceedLimit()
    {
        $appliedPatches = [];
        for ($i = 1; $i < ReviewAppliedAction::UPGRADE_THRESHOLD; $i++) {
            $appliedPatches[] = $this->createPatch('MDVA-' . $i);
        }

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->statusPool->method('isApplied')
            ->willReturn(true);

        $this->optionalPool->expects($this->once())
            ->method('getOptionalListByOrigin')
            ->willReturn($appliedPatches);

        $outputMock->expects($this->never())
            ->method('writeln');

        $this->action->execute($inputMock, $outputMock, []);
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     *
     * @return PatchInterface|MockObject
     */
    private function createPatch(string $id)
    {
        $patch = $this->getMockForAbstractClass(PatchInterface::class);
        $patch->method('getId')->willReturn($id);

        return $patch;
    }
}
