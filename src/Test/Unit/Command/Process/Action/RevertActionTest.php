<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Action\RevertAction;
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class RevertActionTest extends TestCase
{
    /**
     * @var RevertAction
     */
    private $action;

    /**
     * @var Applier|MockObject
     */
    private $applier;

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
     * @var RevertValidator|MockObject
     */
    private $revertValidator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->applier = $this->createMock(Applier::class);
        $this->revertValidator = $this->createMock(RevertValidator::class);
        $this->statusPool = $this->createMock(StatusPool::class);
        $this->optionalPool = $this->createMock(OptionalPool::class);
        $this->renderer = $this->createMock(Renderer::class);
        $this->revertAction = $this->createMock(RevertAction::class);
        /** @var \Psr\Log\LoggerInterface|MockObject $logger */
        $logger = $this->getMockForAbstractClass('\Psr\Log\LoggerInterface');

        $this->action = new RevertAction(
            $this->applier,
            $this->revertValidator,
            $this->optionalPool,
            $this->statusPool,
            $this->renderer,
            $logger
        );
    }

    /**
     * Tests successful patches reverting.
     *
     * Case: reverting 2 applied patches. Verifies that patches are reverted in reverse order.
     */
    public function testExecuteSuccessful()
    {
        $patchFilter = ['MC-11111', 'MC-22222'];
        $patch1 = $this->createPatch('/path/patch1.patch', 'MC-11111');
        $patch2 = $this->createPatch('/path/patch2.patch', 'MC-22222');
        $this->statusPool->method('isNotApplied')
            ->willReturnMap([
                ['MC-11111', false],
                ['MC-22222', false]
            ]);

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->withConsecutive([$patchFilter, false])
            ->willReturn([$patch1, $patch2]);

        $this->applier->method('revert')
            ->willReturnMap([
                [$patch1->getPath(), $patch1->getId(), 'Patch ' . $patch1->getId() .' has been reverted'],
                [$patch2->getPath(), $patch2->getId(), 'Patch ' . $patch2->getId() .' has been reverted']
            ]);

        $this->renderer->expects($this->exactly(2))
            ->method('printPatchInfo')
            ->withConsecutive(
                [$outputMock, $patch2, 'Patch ' . $patch2->getId() .' has been reverted'],
                [$outputMock, $patch1, 'Patch ' . $patch1->getId() .' has been reverted']
            );

        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests successful patches reverting.
     *
     * Case: reverting patch that was not applied previously.
     */
    public function testRevertNotAppliedPatch()
    {
        $patchFilter = ['MC-11111'];
        $patch1 = $this->createPatch('/path/patch1.patch', 'MC-11111');
        $this->statusPool->method('isNotApplied')
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
            ->method('revert');
        $this->renderer->expects($this->never())
            ->method('printPatchInfo');

        $outputMock->expects($this->once())
            ->method('writeln')
            ->withConsecutive(
                [
                    $this->stringContains(
                        'Patch ' . $patch1->getId() . ' (' . $patch1->getFilename() . ') is not applied'
                    )
                ]
            );

        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests patch revert with exception.
     *
     * @throws RuntimeException
     */
    public function testRevertWithException()
    {
        $patch1 = $this->createPatch('/path/patch1.patch', 'MC-11111');
        $patchFilter = [$patch1->getId()];
        $errorMessage = sprintf('Reverting patch %s (%s) failed.', $patch1->getId(), $patch1->getPath());

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->optionalPool->method('getList')
            ->willReturn([$patch1]);

        $this->applier->method('revert')
            ->willThrowException(new ApplierException('Error'));

        $outputMock->expects($this->once())
            ->method('writeln')
            ->withConsecutive(
                [$this->stringContains($errorMessage)]
            );

        $this->expectException(RuntimeException::class);
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
            ->method('getList')
            ->withConsecutive([$patchFilter])
            ->willThrowException(new PatchNotFoundException(''));

        $this->expectException(RuntimeException::class);
        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests exception when revert patch validation fails.
     */
    public function testValidationFailedException()
    {
        $patchFilter = ['MC-11111'];

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->revertValidator->expects($this->once())
            ->method('validate')
            ->withConsecutive([$patchFilter])
            ->willThrowException(new RuntimeException('Error'));
        $this->optionalPool->expects($this->never())
            ->method('getList');

        $this->expectException(RuntimeException::class);
        $this->action->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Creates patch mock.
     *
     * @param string $path
     * @param string $id
     *
     * @return PatchInterface|MockObject
     */
    private function createPatch(string $path, string $id)
    {
        $patch = $this->getMockForAbstractClass(PatchInterface::class);
        $patch->method('getPath')->willReturn($path);
        $patch->method('getId')->willReturn($id);

        return $patch;
    }
}
