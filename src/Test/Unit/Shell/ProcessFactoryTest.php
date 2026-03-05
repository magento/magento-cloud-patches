<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Shell;

use Composer\Composer;
use Composer\Package\Locker;
use Composer\Package\PackageInterface;
use Composer\Repository\LockArrayRepository;
use Magento\CloudPatches\Filesystem\DirectoryList;
use Magento\CloudPatches\Shell\PackageNotFoundException;
use Magento\CloudPatches\Shell\ProcessFactory;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @inheritDoc
 */
class ProcessFactoryTest extends TestCase
{
    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var LockArrayRepository|MockObject
     */
    private $repositoryMock;

    /**
     * @var ProcessFactory
     */
    private ProcessFactory $processFactory;

    /**
     * Tests creating process with newer Symfony Process version.
     *
     * @return void
     * @throws PackageNotFoundException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testCreateWithNewerSymfonyProcess(): void
    {
        $cmd = ['ls', '-la'];
        $magentoRoot = '/magento/root';

        $packageMock = $this->createMock(PackageInterface::class);
        $packageMock->method('getVersion')->willReturn('3.4.0');

        $this->repositoryMock->expects($this->once())
            ->method('findPackage')
            ->with('symfony/process', '*')
            ->willReturn($packageMock);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $process = $this->processFactory->create($cmd);
        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals("'ls' '-la'", $process->getCommandLine());
    }

    /**
     * Tests that create throws exception when package is not found.
     *
     * @return void
     * @throws PackageNotFoundException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testCreateThrowsExceptionWhenPackageNotFound(): void
    {
        $this->repositoryMock->expects($this->once())
            ->method('findPackage')
            ->willReturn(null);

        $this->expectException(PackageNotFoundException::class);
        $this->processFactory->create(['ls']);
    }

    /**
     * Sets up test dependencies.
     *
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $composerMock = $this->createMock(Composer::class);
        $lockerMock = $this->createMock(Locker::class);
        $this->repositoryMock = $this->createMock(LockArrayRepository::class);

        $composerMock->method('getLocker')->willReturn($lockerMock);
        $lockerMock->method('getLockedRepository')->willReturn($this->repositoryMock);

        $this->processFactory = new ProcessFactory(
            $this->directoryListMock,
            $composerMock
        );
    }
}
