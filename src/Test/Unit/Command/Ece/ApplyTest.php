<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Ece;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\AbstractCommand;
use Magento\CloudPatches\Command\Ece\Apply;
use Magento\CloudPatches\Command\Process\ApplyLocal;
use Magento\CloudPatches\Command\Process\Ece\ApplyOptional;
use Magento\CloudPatches\Command\Process\ApplyRequired;
use Magento\CloudPatches\Composer\MagentoVersion;
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
    private $applyOptionalEce;

    /**
     * @var ApplyRequired|MockObject
     */
    private $applyRequired;

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
        $this->applyOptionalEce = $this->createMock(ApplyOptional::class);
        $this->applyRequired = $this->createMock(ApplyRequired::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->magentoVersion = $this->createMock(MagentoVersion::class);

        $this->command = new Apply(
            $this->applyRequired,
            $this->applyOptionalEce,
            $this->applyLocal,
            $this->logger,
            $this->magentoVersion
        );
    }

    /**
     * Tests successful command execution - Cloud environment.
     */
    public function testExecute()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->applyLocal->expects($this->once())
            ->method('run');
        $this->applyOptionalEce->expects($this->once())
            ->method('run');
        $this->applyRequired->expects($this->once())
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

        $this->applyOptionalEce->expects($this->once())
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

        $this->applyOptionalEce->expects($this->once())
            ->method('run')
            ->willThrowException(new \InvalidArgumentException('Critical error!'));
        $this->logger->expects($this->once())
            ->method('critical');

        $this->expectException(\InvalidArgumentException::class);
        $this->command->execute($inputMock, $outputMock);
    }
}
