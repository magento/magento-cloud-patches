<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Ece;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\AbstractCommand;
use Magento\CloudPatches\Command\Process\Ece\Revert as RevertProcess;
use Magento\CloudPatches\Command\Ece\Revert;
use Magento\CloudPatches\Composer\MagentoVersion;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class RevertTest extends TestCase
{
    /**
     * @var Revert
     */
    private $command;

    /**
     * @var RevertProcess|MockObject
     */
    private $revertEce;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->revertEce = $this->createMock(RevertProcess::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        /** @var MagentoVersion|MockObject $magentoVersion */
        $magentoVersion = $this->createMock(MagentoVersion::class);

        $this->command = new Revert(
            $this->revertEce,
            $this->logger,
            $magentoVersion
        );
    }

    /**
     * Tests successful command execution.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testRevertSuccess(): void
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->createMock(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createMock(OutputInterface::class);

        $this->revertEce->expects($this->once())
            ->method('run');

        $this->assertEquals(
            AbstractCommand::RETURN_SUCCESS,
            $this->command->execute($inputMock, $outputMock)
        );
    }

    /**
     * Tests when runtime error happens during command execution.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testRuntimeError(): void
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->createMock(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createMock(OutputInterface::class);

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
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testCriticalError(): void
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->createMock(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createMock(OutputInterface::class);

        $this->revertEce->expects($this->once())
            ->method('run')
            ->willThrowException(new \InvalidArgumentException('Critical error!'));
        $this->logger->expects($this->once())
            ->method('critical');

        $this->expectException(\InvalidArgumentException::class);
        $this->command->execute($inputMock, $outputMock);
    }
}
