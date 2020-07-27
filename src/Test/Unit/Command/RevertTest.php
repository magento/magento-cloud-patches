<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\AbstractCommand;
use Magento\CloudPatches\Command\Process\Revert as RevertProcess;
use Magento\CloudPatches\Command\Revert;
use Magento\CloudPatches\Composer\MagentoVersion;
use Magento\CloudPatches\Patch\Environment;
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
    private $revert;

    /**
     * @var Environment|MockObject
     */
    private $environment;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->revert = $this->createMock(RevertProcess::class);
        $this->environment = $this->createMock(Environment::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);

        /** @var MagentoVersion|MockObject $magentoVersion */
        $magentoVersion = $this->createMock(MagentoVersion::class);

        $this->command = new Revert(
            $this->revert,
            $this->environment,
            $this->logger,
            $magentoVersion
        );
    }

    /**
     * Tests that command is not available on Cloud environment.
     */
    public function testCloudEnvironmentNotAvailable()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->environment->method('isCloud')
            ->willReturn(true);

        $this->revert->expects($this->never())
            ->method('run');

        $this->assertEquals(
            AbstractCommand::RETURN_FAILURE,
            $this->command->execute($inputMock, $outputMock)
        );
    }

    /**
     * Tests successful command execution on OnPrem environment.
     */
    public function testOnPremEnvironmentSuccess()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->environment->method('isCloud')
            ->willReturn(false);

        $this->revert->expects($this->once())
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

        $this->revert->expects($this->once())
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

        $this->revert->expects($this->once())
            ->method('run')
            ->willThrowException(new \InvalidArgumentException('Critical error!'));
        $this->logger->expects($this->once())
            ->method('critical');

        $this->expectException(\InvalidArgumentException::class);
        $this->command->execute($inputMock, $outputMock);
    }
}
