<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\VerifyPatches;
use Magento\CloudPatches\Command\Verify;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class VerifyTest extends TestCase
{
    /**
     * @var VerifyPatches|MockObject
     */
    private $verifyPatchesMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Verify
     */
    private $command;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->verifyPatchesMock = $this->createMock(VerifyPatches::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->command = new Verify(
            $this->verifyPatchesMock,
            $this->loggerMock
        );
    }

    /**
     * Tests successful verification execution.
     *
     * @return void
     */
    public function testExecuteSuccess(): void
    {
        $inputMock = $this->createMock(InputInterface::class);
        $outputMock = $this->createMock(OutputInterface::class);

        $this->verifyPatchesMock->expects($this->once())
            ->method('run')
            ->with($inputMock, $outputMock)
            ->willReturn(0);

        $this->loggerMock->expects($this->never())
            ->method('error');

        $result = $this->command->execute($inputMock, $outputMock);

        $this->assertEquals(0, $result);
    }

    /**
     * Tests verification execution with failures.
     *
     * @return void
     */
    public function testExecuteWithFailures(): void
    {
        $inputMock = $this->createMock(InputInterface::class);
        $outputMock = $this->createMock(OutputInterface::class);

        $this->verifyPatchesMock->expects($this->once())
            ->method('run')
            ->with($inputMock, $outputMock)
            ->willReturn(1);

        $result = $this->command->execute($inputMock, $outputMock);

        $this->assertEquals(1, $result);
    }

    /**
     * Tests verification execution with RuntimeException.
     *
     * @return void
     */
    public function testExecuteWithRuntimeException(): void
    {
        $inputMock = $this->createMock(InputInterface::class);
        $outputMock = $this->createMock(OutputInterface::class);

        $exception = new RuntimeException('Test error');

        $this->verifyPatchesMock->expects($this->once())
            ->method('run')
            ->with($inputMock, $outputMock)
            ->willThrowException($exception);

        $outputMock->expects($this->once())
            ->method('writeln')
            ->with('<error>Test error</error>');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Test error');

        $result = $this->command->execute($inputMock, $outputMock);

        $this->assertEquals(Verify::RETURN_FAILURE, $result);
    }

    /**
     * Tests command name and description.
     *
     * @return void
     */
    public function testCommandConfiguration(): void
    {
        $this->assertEquals('verify', $this->command->getName());
        $this->assertStringContainsString('Verifies', $this->command->getDescription());
    }
}
