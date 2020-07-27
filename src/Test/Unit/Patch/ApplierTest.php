<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Composer\MagentoVersion;
use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Magento\CloudPatches\Patch\GitConverter;
use Magento\CloudPatches\Patch\Status\StatusPool;
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
     * @var ProcessFactory|MockObject
     */
    private $processFactory;

    /**
     * @var GitConverter|MockObject
     */
    private $gitConverter;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersion;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystem;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->processFactory = $this->createMock(ProcessFactory::class);
        $this->gitConverter = $this->createMock(GitConverter::class);
        $this->magentoVersion = $this->createMock(MagentoVersion::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->applier = new Applier(
            $this->processFactory,
            $this->gitConverter,
            $this->magentoVersion,
            $this->filesystem
        );
    }

    /**
     * Tests apply operation, case when patch applied successfully.
     *
     * @throws ApplierException
     */
    public function testApply()
    {
        $path = 'path/to/patch';
        $patchId = 'MC-11111';
        $expectedMessage = 'Patch ' . $patchId . ' has been applied';
        $this->filesystem->expects($this->once())
            ->method('get')
            ->willReturn('patchContent');
        $this->magentoVersion->expects($this->once())
            ->method('isGitBased')
            ->willReturn(true);
        $this->gitConverter->expects($this->once())
            ->method('convert')
            ->willReturn('gitContent');
        $processMock = $this->createMock(Process::class);

        $this->processFactory->expects($this->once())
            ->method('create')
            ->withConsecutive([['git', 'apply'], 'gitContent'])
            ->willReturn($processMock);
        $processMock->expects($this->once())
            ->method('mustRun');

        $this->assertSame($expectedMessage, $this->applier->apply($path, $patchId));
    }

    /**
     * Tests apply operation, case when applying patch fails.
     */
    public function testApplyFailed()
    {
        $path = 'path/to/patch';
        $patchId = 'MC-11111';

        /** @var Process|MockObject $result */
        $processMock = $this->createMock(Process::class);
        $processMock->method('mustRun')
            ->willThrowException(new ProcessFailedException($processMock));

        $this->processFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($processMock);

        $this->expectException(ApplierException::class);
        $this->applier->apply($path, $patchId);
    }

    /**
     * Tests apply operation, case when patch was already applied.
     *
     * @throws ApplierException
     */
    public function testApplyPatchAlreadyApplied()
    {
        $path = 'path/to/patch';
        $patchId = 'MC-11111';
        $expectedMessage = 'Patch ' . $patchId . ' was already applied';

        $this->filesystem->expects($this->once())
            ->method('get')
            ->willReturn('patchContent');
        $this->magentoVersion->expects($this->once())
            ->method('isGitBased')
            ->willReturn(false);
        $this->gitConverter->expects($this->never())
            ->method('convert');

        $this->processFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [['git', 'apply'], 'patchContent'],
                [['git', 'apply', '--check', '--reverse'], 'patchContent']
            ])->willReturnCallback([$this, 'shellApplyRevertCallback']);

        $this->assertSame($expectedMessage, $this->applier->apply($path, $patchId));
    }

    /**
     * Callback for 'apply' and 'revert' operations.
     *
     * @param array $command
     * @return Process
     *
     * @throws ProcessFailedException when the command isn't a reverse
     */
    public function shellApplyRevertCallback(array $command): Process
    {
        if (in_array('--reverse', $command, true) && in_array('--check', $command, true) ||
            !in_array('--reverse', $command, true) && in_array('--check', $command, true)
        ) {
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

    /**
     * Tests revert operation, case when patch reverted successfully.
     *
     * @throws ApplierException
     */
    public function testRevert()
    {
        $path = 'path/to/patch';
        $patchId = 'MC-11111';
        $expectedMessage = 'Patch ' . $patchId . ' has been reverted';

        $this->filesystem->expects($this->once())
            ->method('get')
            ->willReturn('patchContent');
        $this->magentoVersion->expects($this->once())
            ->method('isGitBased')
            ->willReturn(true);
        $this->gitConverter->expects($this->once())
            ->method('convert')
            ->willReturn('gitContent');

        $processMock = $this->createMock(Process::class);

        $this->processFactory->expects($this->once())
            ->method('create')
            ->withConsecutive([['git', 'apply', '--reverse'], 'gitContent'])
            ->willReturn($processMock);
        $processMock->expects($this->once())
            ->method('mustRun');

        $this->assertSame($expectedMessage, $this->applier->revert($path, $patchId));
    }

    /**
     * Tests revert operation, case when patch revert fails.
     */
    public function testRevertFailed()
    {
        $path = 'path/to/patch';
        $patchId = 'MC-11111';

        /** @var Process|MockObject $result */
        $processMock = $this->createMock(Process::class);
        $processMock->method('mustRun')
            ->willThrowException(new ProcessFailedException($processMock));

        $this->processFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($processMock);

        $this->expectException(ApplierException::class);
        $this->applier->revert($path, $patchId);
    }

    /**
     * Tests revert operation, case when patch wasn't applied.
     *
     * @throws ApplierException
     */
    public function testRevertPatchWasntApplied()
    {
        $path = 'path/to/patch';
        $patchId = 'MC-11111';
        $patchContent = 'patch content';
        $expectedMessage = 'Patch ' . $patchId . ' wasn\'t applied';

        $this->filesystem->expects($this->once())
            ->method('get')
            ->willReturn($patchContent);
        $this->magentoVersion->expects($this->once())
            ->method('isGitBased')
            ->willReturn(false);
        $this->gitConverter->expects($this->never())
            ->method('convert');

        $this->processFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [['git', 'apply'], $patchContent],
                [['git', 'apply', '--check'], $patchContent]
            ])->willReturnCallback([$this, 'shellApplyRevertCallback']);

        $this->assertSame($expectedMessage, $this->applier->revert($path, $patchId));
    }

    /**
     * Tests status operation, case when patch is not applied.
     */
    public function testStatusNotApplied()
    {
        $patchContent = 'patch content';
        $processMock = $this->createMock(Process::class);

        $this->processFactory->expects($this->once())
            ->method('create')
            ->withConsecutive([['git', 'apply', '--check'], $patchContent])
            ->willReturn($processMock);
        $processMock->expects($this->once())
            ->method('mustRun');

        $this->assertSame(StatusPool::NOT_APPLIED, $this->applier->status($patchContent));
    }

    /**
     * Tests status operation, case when patch status can't be defined.
     */
    public function testStatusNotAvailable()
    {
        $patchContent = 'patch content';

        /** @var Process|MockObject $result */
        $processMock = $this->createMock(Process::class);
        $processMock->method('mustRun')
            ->willThrowException(new ProcessFailedException($processMock));

        $this->processFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($processMock);

        $this->assertSame(StatusPool::NA, $this->applier->status($patchContent));
    }

    /**
     * Tests status operation, case when patch is applied.
     */
    public function testStatusApplied()
    {
        $patchContent = 'patch content';

        $this->processFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [['git', 'apply', '--check']],
                [['git', 'apply', '--check', '--reverse']]
            ])->willReturnCallback([$this, 'shellStatusCallback']);

        $this->assertSame(StatusPool::APPLIED, $this->applier->status($patchContent));
    }

    /**
     * Callback for 'status' operations.
     *
     * @param array $command
     * @return Process
     *
     * @throws ProcessFailedException when the command isn't a reverse
     */
    public function shellStatusCallback(array $command): Process
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
