<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Apply;
use Magento\CloudPatches\Command\Process\Action\ActionPool;
use Magento\CloudPatches\Command\Process\ApplyOptional;
use Magento\CloudPatches\Patch\FilterFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ApplyOptionalTest extends TestCase
{
    /**
     * @var ApplyOptional
     */
    private $applyOptional;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ActionPool|MockObject
     */
    private $actionPool;

    /**
     * @var FilterFactory|MockObject
     */
    private $filterFactory;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->filterFactory = $this->createMock(FilterFactory::class);
        $this->actionPool = $this->createMock(ActionPool::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->applyOptional = new ApplyOptional(
            $this->filterFactory,
            $this->actionPool,
            $this->logger
        );
    }

    /**
     * Tests successful optional patches applying.
     *
     * @throws RuntimeException
     */
    public function testApplyWithPatchArgumentProvided()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $cliPatchArgument = ['MC-1111', 'MC-22222'];
        $inputMock->expects($this->once())
            ->method('getArgument')
            ->with(Apply::ARG_LIST_OF_PATCHES)
            ->willReturn($cliPatchArgument);
        $this->filterFactory->method('createApplyFilter')
            ->with($cliPatchArgument)
            ->willReturn($cliPatchArgument);

        $this->actionPool->expects($this->once())
            ->method('execute')
            ->withConsecutive([$inputMock, $outputMock, $cliPatchArgument]);

        $this->applyOptional->run($inputMock, $outputMock);
    }

    /**
     * Tests optional patches applying when CLI patch argument is empty.
     *
     * @throws RuntimeException
     */
    public function testApplyWithEmptyPatchArgument()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $cliPatchArgument = [];
        $inputMock->expects($this->once())
            ->method('getArgument')
            ->with(Apply::ARG_LIST_OF_PATCHES)
            ->willReturn($cliPatchArgument);
        $this->filterFactory->method('createApplyFilter')
            ->with($cliPatchArgument)
            ->willReturn(null);

        $this->actionPool->expects($this->never())
            ->method('execute');

        $this->applyOptional->run($inputMock, $outputMock);
    }
}
