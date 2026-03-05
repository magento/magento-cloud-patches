<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Patch\PatchCommand;
use Magento\CloudPatches\Patch\PatchCommandException;
use Magento\CloudPatches\Patch\PatchCommandNotFound;
use Magento\CloudPatches\Shell\Command\DriverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class PatchCommandTest extends TestCase
{
    /**
     * @var DriverInterface|MockObject
     */
    private $driverMock;

    /**
     * @var PatchCommand
     */
    private PatchCommand $patchCommand;

    /**
     * Sets up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->driverMock = $this->createMock(DriverInterface::class);
        $this->patchCommand = new PatchCommand([$this->driverMock]);
    }

    /**
     * Tests applying a patch.
     *
     * @return void
     * @throws PatchCommandException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testApply(): void
    {
        $patch = 'patch.diff';
        $this->driverMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->driverMock->expects($this->once())
            ->method('apply')
            ->with($patch);

        $this->patchCommand->apply($patch);
    }

    /**
     * Tests reverting a patch.
     *
     * @return void
     * @throws PatchCommandException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testRevert(): void
    {
        $patch = 'patch.diff';
        $this->driverMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->driverMock->expects($this->once())
            ->method('revert')
            ->with($patch);

        $this->patchCommand->revert($patch);
    }

    /**
     * Tests applying a patch check.
     *
     * @return void
     * @throws PatchCommandException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testApplyCheck(): void
    {
        $patch = 'patch.diff';
        $this->driverMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->driverMock->expects($this->once())
            ->method('applyCheck')
            ->with($patch);

        $this->patchCommand->applyCheck($patch);
    }

    /**
     * Tests reverting a patch check.
     *
     * @return void
     * @throws PatchCommandException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testRevertCheck(): void
    {
        $patch = 'patch.diff';
        $this->driverMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->driverMock->expects($this->once())
            ->method('revertCheck')
            ->with($patch);

        $this->patchCommand->revertCheck($patch);
    }

    /**
     * Tests that getDriver throws exception when no driver is installed.
     *
     * @return void
     * @throws PatchCommandException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetDriverThrowsExceptionWhenNoDriverInstalled(): void
    {
        $this->driverMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);

        $this->expectException(PatchCommandNotFound::class);
        $this->patchCommand->apply('patch.diff');
    }
}
