<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositoryManager;
use Magento\CloudPatches\Filesystem\DirectoryList;
use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Magento\CloudPatches\Shell\ProcessFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @inheritDoc
 */
class ApplierTest extends TestCase
{
    /**
     * @var Applier
     */
    private $applier;

    /**
     * @var Composer|MockObject
     */
    private $composerMock;

    /**
     * @var ProcessFactory|MockObject
     */
    private $processFactoryMock;

    /**
     * @var RepositoryInterface|MockObject
     */
    private $localRepositoryMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystemMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->composerMock = $this->createMock(Composer::class);
        $this->localRepositoryMock = $this->getMockForAbstractClass(RepositoryInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->processFactoryMock = $this->createMock(ProcessFactory::class);

        $repositoryManagerMock = $this->createMock(RepositoryManager::class);

        $repositoryManagerMock->expects($this->once())
            ->method('getLocalRepository')
            ->willReturn($this->localRepositoryMock);
        $this->composerMock->expects($this->once())
            ->method('getRepositoryManager')
            ->willReturn($repositoryManagerMock);

        $this->applier = new Applier(
            $this->composerMock,
            $this->processFactoryMock,
            $this->directoryListMock,
            $this->filesystemMock
        );
    }

    /**
     * @param string $path
     * @param string $name
     * @param string $packageName
     * @param string $constraint
     * @param string $expectedLog
     * @dataProvider applyDataProvider
     *
     * @throws ApplierException
     */
    public function testApply(string $path, string $name, string $packageName, string $constraint, string $expectedLog)
    {
        $this->filesystemMock->expects($this->once())
            ->method('exists')
            ->with($path)
            ->willReturn(true);
        $this->localRepositoryMock->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn($this->getMockForAbstractClass(PackageInterface::class));

        $processMock = $this->createMock(Process::class);

        $this->processFactoryMock->expects($this->once())
            ->method('create')
            ->with(['git', 'apply', $path])
            ->willReturn($processMock);
        $processMock->expects($this->once())
            ->method('mustRun');

        $this->assertSame(
            $expectedLog,
            $this->applier->apply($path, $name, $packageName, $constraint, false)
        );
    }

    /**
     * @return array
     */
    public function applyDataProvider(): array
    {
        return [
            ['path/to/patch', 'patchName', 'packageName', '1.0', 'Patch "patchName (path/to/patch) 1.0" applied']
        ];
    }

    /**
     * @param string $path
     * @param string $expectedLog
     * @dataProvider applyFileDataProvider
     *
     * @throws ApplierException
     */
    public function testApplyFile(string $path, string $expectedLog)
    {
        $processMock = $this->createMock(Process::class);

        $this->processFactoryMock->expects($this->once())
            ->method('create')
            ->with(['git', 'apply', $path])
            ->willReturn($processMock);
        $processMock->expects($this->once())
            ->method('mustRun');

        $this->assertSame(
            $expectedLog,
            $this->applier->applyFile($path, false)
        );
    }

    /**
     * @return array
     */
    public function applyFileDataProvider(): array
    {
        return [
            ['path/to/patch2', 'Patch "path/to/patch2" applied'],
        ];
    }

    /**
     * @throws ApplierException
     */
    public function testApplyPathNotExists()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $packageName = 'packageName';
        $constraint = '1.0';

        $this->filesystemMock->expects($this->once())
            ->method('exists')
            ->with($path)
            ->willReturn(false);
        $this->localRepositoryMock->expects($this->once())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn($this->getMockForAbstractClass(PackageInterface::class));
        $this->directoryListMock->expects($this->once())
            ->method('getPatches')
            ->willReturn('root');

        $processMock = $this->createMock(Process::class);

        $this->processFactoryMock->expects($this->once())
            ->method('create')
            ->with(['git', 'apply', 'root/path/to/patch'])
            ->willReturn($processMock);
        $processMock->expects($this->once())
            ->method('mustRun');

        $this->applier->apply($path, $name, $packageName, $constraint, false);
    }

    /**
     * @throws ApplierException
     */
    public function testApplyPathNotExistsAndNotMatchedConstraints()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $packageName = 'packageName';
        $constraint = '1.0';

        $this->localRepositoryMock->expects($this->once())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn(null);

        $processMock = $this->createMock(Process::class);

        $this->processFactoryMock->expects($this->never())
            ->method('create')
            ->with(['git', 'apply', 'root/path/to/patch'])
            ->willReturn($processMock);

        $this->applier->apply($path, $name, $packageName, $constraint, false);
    }

    /**
     * @throws ApplierException
     */
    public function testApplyPatchAlreadyApplied()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $packageName = 'packageName';
        $constraint = '1.0';

        $this->filesystemMock->expects($this->once())
            ->method('exists')
            ->with($path)
            ->willReturn(true);
        $this->localRepositoryMock->expects($this->once())
            ->method('findPackage')
            ->with($packageName, $constraint)
            ->willReturn($this->getMockForAbstractClass(PackageInterface::class));

        $this->processFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [['git', 'apply', 'path/to/patch']],
                [['git', 'apply', 'path/to/patch', '--revert']]
            ])->willReturnCallback([$this, 'shellMockReverseCallback']);

        $this->assertSame(
            'Patch "patchName (path/to/patch) 1.0" was already applied',
            $this->applier->apply($path, $name, $packageName, $constraint, false)
        );
    }

    /**
     * @param array $command
     * @return Process
     *
     * @throws ProcessFailedException when the command isn't a reverse
     */
    public function shellMockReverseCallback(array $command): Process
    {
        if (in_array('--reverse', $command, true) && in_array('--check', $command, true)) {
            // Command was the reverse check, it's all good.
            /** @var Process|MockObject $result */
            $result = $this->createMock(Process::class);
            $result->expects($this->once())
                ->method('mustRun');

            return $result;
        }

        /** @var Process|MockObject $result */
        $result = $this->createMock(Process::class);
        $result->expects($this->once())
            ->method('mustRun')
            ->willThrowException(new ProcessFailedException($result));

        return $result;
    }
}
