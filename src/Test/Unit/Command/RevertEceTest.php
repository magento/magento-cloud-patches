<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\AbstractCommand;
use Magento\CloudPatches\Command\Process\RevertEce as RevertEceProcess;
use Magento\CloudPatches\Command\RevertEce;
use Magento\CloudPatches\Composer\MagentoVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class RevertEceTest extends TestCase
{
    /**
     * @var RevertEce
     */
    private $command;

    /**
     * @var RevertEceProcess|MockObject
     */
    private $revertEce;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->revertEce = $this->createMock(RevertEceProcess::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);

        /** @var MagentoVersion|MockObject $magentoVersion */
        $magentoVersion = $this->createMock(MagentoVersion::class);

        $this->command = new RevertEce(
            $this->revertEce,
            $this->logger,
            $magentoVersion
        );
    }

    /**
     * Tests successful command execution.
     */
    public function testRevertSuccess()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->revertEce->expects($this->once())
            ->method('run');

        $this->assertEquals(
            AbstractCommand::RETURN_SUCCESS,
            $this->command->execute($inputMock, $outputMock)
        );
    }

    /**
     * Tests when runtime error happens during command execution.
     */
    public function testRuntimeError()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->revertEce->expects($this->once())
            ->method('run')
            ->willThrowException(new RuntimeException('Error!'));
        $this->logger->expects($this->once())
            ->method('error');

        $this->assertEquals(
            AbstractCommand::RETURN_FAILURE,
            $this->command->execute($inputMock, $outputMock)
        );
    }

    /**
     * Tests when critical error happens during command execution.
     */
    public function testCriticalError()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->revertEce->expects($this->once())
            ->method('run')
            ->willThrowException(new \InvalidArgumentException('Critical error!'));
        $this->logger->expects($this->once())
            ->method('critical');

        $this->expectException(\InvalidArgumentException::class);
        $this->command->execute($inputMock, $outputMock);
    }
}
