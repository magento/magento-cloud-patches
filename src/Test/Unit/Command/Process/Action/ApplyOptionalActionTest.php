<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Action\ApplyOptionalAction;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Magento\CloudPatches\Patch\Conflict\Processor as ConflictProcessor;
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
class ApplyOptionalActionTest extends TestCase
{
    /**
     * @var ApplyOptionalAction
     */
    private $action;

    /**
     * @var Applier|MockObject
     */
    private $applier;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

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
     * @var ConflictProcessor|MockObject
     */
    private $conflictProcessor;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->applier = $this->createMock(Applier::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->statusPool = $this->createMock(StatusPool::class);
        $this->optionalPool = $this->createMock(OptionalPool::class);
        $this->renderer = $this->createMock(Renderer::class);
        $this->conflictProcessor = $this->createMock(ConflictProcessor::class);

        $this->action = new ApplyOptionalAction(
            $this->applier,
            $this->optionalPool,
            $this->statusPool,
            $this->renderer,
            $this->logger,
            $this->conflictProcessor
        );
    }

    /**
     * Tests successful optional patches applying.
     *
     * Case: applying 3 optional non-deprecated patches that wasn't applied previously.
     */
    public function testExecuteSuccessful()
    {
        $patchFilter = ['MC-11111', 'MC-22222', 'MC-33333'];
        $patch1 = $this->createPatch('/path/patch1.patch', 'MC-11111');
        $patch2 = $this->createPatch('/path/patch2.patch', 'MC-22222');
        $patch3 = $this->createPatch('/path/patch3.patch', 'MC-33333');
        $this->statusPool->method('isApplied')
            ->willReturnMap([
                ['MC-11111', false],
                ['MC-22222', false],
                ['MC-33333', false],
            ]);

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->withConsecutive([$patchFilter])
            ->willReturn([$patch1, $patch2, $patch3]);

        $this->applier->method('apply')
            ->willReturnMap([
                [$patch1->getPath(), $patch1->getId(), 'Patch ' . $patch1->getId() .' has been applied'],
                [$patch2->getPath(), $patch2->getId(), 'Patch ' . $patch2->getId() .' has been applied'],
                [$patch3->getPath(), $patch3->getId(), 'Patch ' . $patch3->getId() .' has been applied'],
            ]);

        $this->renderer->expects($this->exactly(3))
            ->method('printPatchInfo')
            ->withConsecutive(
                [$outputMock, $patch1, 'Patch ' . $patch1->getId() .' has been applied'],
                [$outputMock, $patch2, 'Patch ' . $patch2->getId() .' has been applied'],
                [$outputMock, $patch3, 'Patch ' . $patch3->getId() .' has been applied']
            );

        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests successful optional patches applying.
     *
     * Case: applying optional patch that was applied previously.
     */
    public function testApplyAlreadyAppliedPatch()
    {
        $patchFilter = ['MC-11111'];
        $patch1 = $this->createPatch('/path/patch1.patch', 'MC-11111');
        $this->statusPool->method('isApplied')
            ->willReturnMap([
                ['MC-11111', true]
            ]);

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->withConsecutive([$patchFilter])
            ->willReturn([$patch1]);

        $this->applier->expects($this->never())
            ->method('apply');
        $this->renderer->expects($this->never())
            ->method('printPatchInfo');

        $outputMock->expects($this->once())
            ->method('writeln')
            ->withConsecutive(
                [
                    $this->stringContains(
                        'Patch ' . $patch1->getId() .' (' . $patch1->getFilename() . ') was already applied'
                    )
                ]
            );

        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests successful optional patches applying.
     *
     * Case: patch filter is empty (should apply all patches from the pool). Pool contains deprecated patch that
     * shouldn't be applied.
     */
    public function testApplyingAllPatchesAndSkipDeprecated()
    {
        $patchFilter = [];
        $patch1 = $this->createPatch('/path/patch1.patch', 'MC-11111', false);
        $patch2 = $this->createPatch('/path/patch2.patch', 'MC-22222', true);
        $this->statusPool->method('isApplied')
            ->willReturnMap([
                ['MC-11111', false],
                ['MC-22222', false]
            ]);

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->optionalPool->expects($this->once())
            ->method('getOptionalListByOrigin')
            ->with(['Adobe Commerce Support'])
            ->willReturn([$patch1, $patch2]);

        $this->applier->method('apply')
            ->willReturnMap([
                [$patch1->getPath(), $patch1->getId(), 'Patch ' . $patch1->getId() .' has been applied']
            ]);

        $this->renderer->expects($this->once())
            ->method('printPatchInfo')
            ->withConsecutive(
                [$outputMock, $patch1, 'Patch ' . $patch1->getId() .' has been applied']
            );

        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests optional patches applying with exception.
     *
     * Case: first patch is applied successfully, exception is thrown during applying second patch,
     * rollback starts, first patch should be reverted.
     *
     * @throws RuntimeException
     */
    public function testApplyWithException()
    {
        $patch1 = $this->createPatch('/path/patch1.patch', 'MC-11111');
        $patch2 = $this->createPatch('/path/patch2.patch', 'MC-22222');
        $patchFilter = [$patch1->getId(), $patch2->getId()];

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->optionalPool->method('getList')
            ->willReturn([$patch1, $patch2]);

        $this->applier->method('apply')
            ->willReturnMap([
                [$patch1->getPath(), $patch1->getId()],
                [$patch2->getPath(), $patch2->getId()]
            ])->willReturnCallback(
                function ($path, $id) {
                    if ($id === 'MC-22222') {
                        throw new ApplierException('Applier error message');
                    }

                    return "Patch {$path} {$id} has been applied";
                }
            );

        $this->conflictProcessor->expects($this->once())
            ->method('process')
            ->withConsecutive([$outputMock, $patch2, [$patch1], 'Applier error message'])
            ->willThrowException(new RuntimeException('Error message'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error message');

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
