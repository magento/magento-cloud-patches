<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Action\RevertAction;
use Magento\CloudPatches\Command\Process\Revert;
use Magento\CloudPatches\Command\Revert as RevertCommand;
use Magento\CloudPatches\Patch\FilterFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class RevertTest extends TestCase
{
    /**
     * @var Revert
     */
    private $manager;

    /**
     * @var RevertAction|MockObject
     */
    private $revertAction;

    /**
     * @var FilterFactory|MockObject
     */
    private $filterFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->filterFactory = $this->createMock(FilterFactory::class);
        $this->revertAction = $this->createMock(RevertAction::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->manager = new Revert(
            $this->filterFactory,
            $this->revertAction,
            $this->logger
        );
    }

    /**
     * Tests optional patches reverting when CLI patch argument provided.
     *
     * @throws RuntimeException
     */
    public function testRevertWithPatchArgumentProvided()
    {
        $cliPatchArgument = ['MC-1111', 'MC-22222'];
        $cliOptAll = false;

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $inputMock->expects($this->once())
            ->method('getArgument')
            ->with(RevertCommand::ARG_LIST_OF_PATCHES)
            ->willReturn($cliPatchArgument);
        $inputMock->expects($this->once())
            ->method('getOption')
            ->with(RevertCommand::OPT_ALL)
            ->willReturn($cliOptAll);
        $this->filterFactory->method('createRevertFilter')
            ->withConsecutive([$cliOptAll, $cliPatchArgument])
            ->willReturn($cliPatchArgument);

        $this->revertAction->expects($this->once())
            ->method('execute')
            ->withConsecutive([$inputMock, $outputMock, $cliPatchArgument]);

        $this->manager->run($inputMock, $outputMock);
    }

    /**
     * Tests optional patches reverting when CLI patch argument is empty.
     *
     * @throws RuntimeException
     */
    public function testRevertWithEmptyPatchArgument()
    {
        $cliPatchArgument = [];
        $cliOptAll = false;

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $inputMock->expects($this->once())
            ->method('getArgument')
            ->with(RevertCommand::ARG_LIST_OF_PATCHES)
            ->willReturn($cliPatchArgument);
        $inputMock->expects($this->once())
            ->method('getOption')
            ->with(RevertCommand::OPT_ALL)
            ->willReturn($cliOptAll);
        $this->filterFactory->method('createRevertFilter')
            ->withConsecutive([$cliOptAll, $cliPatchArgument])
            ->willReturn(null);

        $this->revertAction->expects($this->never())
            ->method('execute');

        $this->manager->run($inputMock, $outputMock);
    }
}
