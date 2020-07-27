<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\AbstractCommand;
use Magento\CloudPatches\Command\Apply;
use Magento\CloudPatches\Command\Process\ApplyLocal;
use Magento\CloudPatches\Command\Process\ApplyOptional;
use Magento\CloudPatches\Command\Process\ApplyRequired;
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
class ApplyTest extends TestCase
{
    /**
     * @var Apply
     */
    private $command;

    /**
     * @var ApplyLocal|MockObject
     */
    private $applyLocal;

    /**
     * @var ApplyOptional|MockObject
     */
    private $applyOptional;

    /**
     * @var ApplyRequired|MockObject
     */
    private $applyRequired;

    /**
     * @var Environment|MockObject
     */
    private $environment;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersion;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->applyLocal = $this->createMock(ApplyLocal::class);
        $this->applyOptional = $this->createMock(ApplyOptional::class);
        $this->applyRequired = $this->createMock(ApplyRequired::class);
        $this->environment = $this->createMock(Environment::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->magentoVersion = $this->createMock(MagentoVersion::class);

        $this->command = new Apply(
            $this->applyRequired,
            $this->applyOptional,
            $this->applyLocal,
            $this->environment,
            $this->logger,
            $this->magentoVersion
        );
    }

    /**
     * Tests successful command execution on Cloud environment.
     */
    public function testCloudEnvironmentSuccess()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->environment->method('isCloud')
            ->willReturn(true);

        $this->applyLocal->expects($this->once())
            ->method('run');
        $this->applyOptional->expects($this->once())
            ->method('run');
        $this->applyRequired->expects($this->once())
            ->method('run');

        $this->assertEquals(
            AbstractCommand::RETURN_SUCCESS,
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

        $this->applyLocal->expects($this->never())
            ->method('run');
        $this->applyOptional->expects($this->once())
            ->method('run');
        $this->applyRequired->expects($this->never())
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

        $this->applyOptional->expects($this->once())
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

        $this->applyOptional->expects($this->once())
            ->method('run')
            ->willThrowException(new \InvalidArgumentException('Critical error!'));
        $this->logger->expects($this->once())
            ->method('critical');

        $this->expectException(\InvalidArgumentException::class);
        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * Tests when Magento is installed from Git.
     */
    public function testGitBasedInstallation()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $inputMock->expects($this->once())
            ->method('getOption')
            ->with(Apply::OPT_GIT_INSTALLATION)
            ->willReturn(1);

        $this->applyLocal->expects($this->never())
            ->method('run');
        $this->applyOptional->expects($this->never())
            ->method('run');
        $this->applyRequired->expects($this->never())
            ->method('run');

        $this->assertEquals(
            AbstractCommand::RETURN_SUCCESS,
            $this->command->execute($inputMock, $outputMock)
        );
    }
}
