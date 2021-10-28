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
use Magento\CloudPatches\Patch\PatchCommandException;
use Magento\CloudPatches\Patch\PatchCommandInterface;
use Magento\CloudPatches\Patch\Status\StatusPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var PatchCommandInterface|MockObject
     */
    private $patchCommand;

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
    protected function setUp(): void
    {
        $this->patchCommand = $this->createMock(PatchCommandInterface::class);
        $this->gitConverter = $this->createMock(GitConverter::class);
        $this->magentoVersion = $this->createMock(MagentoVersion::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->applier = new Applier(
            $this->patchCommand,
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

        $this->patchCommand->expects($this->once())
            ->method('apply')
            ->with('gitContent');

        $this->assertSame($expectedMessage, $this->applier->apply($path, $patchId));
    }

    /**
     * Tests apply operation, case when applying patch fails.
     */
    public function testApplyFailed()
    {
        $path = 'path/to/patch';
        $patchId = 'MC-11111';

        $this->patchCommand->expects($this->once())
            ->method('apply')
            ->willThrowException(new PatchCommandException('Patch cannot be applied'));

        $this->patchCommand->expects($this->once())
            ->method('revertCheck')
            ->willThrowException(new PatchCommandException('Patch cannot be reverted'));

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

        $this->patchCommand->expects($this->once())
            ->method('apply')
            ->with('patchContent')
            ->willThrowException(new PatchCommandException('Patch cannot be applied'));

        $this->patchCommand->expects($this->once())
            ->method('revertCheck')
            ->with('patchContent');

        $this->assertSame($expectedMessage, $this->applier->apply($path, $patchId));
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

        $this->patchCommand->expects($this->once())
            ->method('revert')
            ->with('gitContent');

        $this->assertSame($expectedMessage, $this->applier->revert($path, $patchId));
    }

    /**
     * Tests revert operation, case when patch revert fails.
     */
    public function testRevertFailed()
    {
        $path = 'path/to/patch';
        $patchId = 'MC-11111';

        $this->patchCommand->expects($this->once())
            ->method('revert')
            ->willThrowException(new PatchCommandException('Patch cannot be reverted'));

        $this->patchCommand->expects($this->once())
            ->method('applyCheck')
            ->willThrowException(new PatchCommandException('Patch cannot be applied'));

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

        $this->patchCommand->expects($this->once())
            ->method('revert')
            ->with($patchContent)
            ->willThrowException(new PatchCommandException('Patch cannot be reverted'));

        $this->patchCommand->expects($this->once())
            ->method('applyCheck')
            ->with($patchContent);

        $this->assertSame($expectedMessage, $this->applier->revert($path, $patchId));
    }

    /**
     * Tests status operation, case when patch is not applied.
     */
    public function testStatusNotApplied()
    {
        $patchContent = 'patch content';

        $this->patchCommand->expects($this->once())
            ->method('applyCheck')
            ->with($patchContent);

        $this->assertSame(StatusPool::NOT_APPLIED, $this->applier->status($patchContent));
    }

    /**
     * Tests status operation, case when patch status can't be defined.
     */
    public function testStatusNotAvailable()
    {
        $patchContent = 'patch content';

        $this->patchCommand->expects($this->once())
            ->method('applyCheck')
            ->with($patchContent)
            ->willThrowException(new PatchCommandException('Patch cannot be applied'));

        $this->patchCommand->expects($this->once())
            ->method('revertCheck')
            ->with($patchContent)
            ->willThrowException(new PatchCommandException('Patch cannot be reverted'));

        $this->assertSame(StatusPool::NA, $this->applier->status($patchContent));
    }

    /**
     * Tests status operation, case when patch is applied.
     */
    public function testStatusApplied()
    {
        $patchContent = 'patch content';

        $this->patchCommand->expects($this->once())
            ->method('applyCheck')
            ->with($patchContent)
            ->willThrowException(new PatchCommandException('Patch cannot be applied'));

        $this->patchCommand->expects($this->once())
            ->method('revertCheck')
            ->with($patchContent);

        $this->assertSame(StatusPool::APPLIED, $this->applier->status($patchContent));
    }

    /**
     * Tests checkApply operation.
     *
     * Verifies that 'diff --git' is replaced with 'diff -Nuar'
     */
    public function testCheckApply()
    {
        $patchContent = 'diff --git a/vendor/module-deploy/Queue.php b/vendor/module-deploy/Queue.php
--- a/vendor/magento/module-deploy/Process/Queue.php
+++ b/vendor/magento/module-deploy/Process/Queue.php
diff --git a/vendor/magento/module-email/Model/Transport.php b/vendor/magento/module-email/Model/Transport.php
--- a/vendor/magento/module-email/Model/Transport.php
+++ b/vendor/magento/module-email/Model/Transport.php
-        echo "diff --git";
+        echo "diff --Nuar";
diff -Nuar a/vendor/magento/module-email/Model/Transport.php b/vendor/magento/module-email/Model/Transport.php
';
        $expectedPatchContent = 'diff -Nuar a/vendor/module-deploy/Queue.php b/vendor/module-deploy/Queue.php
--- a/vendor/magento/module-deploy/Process/Queue.php
+++ b/vendor/magento/module-deploy/Process/Queue.php
diff -Nuar a/vendor/magento/module-email/Model/Transport.php b/vendor/magento/module-email/Model/Transport.php
--- a/vendor/magento/module-email/Model/Transport.php
+++ b/vendor/magento/module-email/Model/Transport.php
-        echo "diff --git";
+        echo "diff --Nuar";
diff -Nuar a/vendor/magento/module-email/Model/Transport.php b/vendor/magento/module-email/Model/Transport.php
';

        $this->magentoVersion->expects($this->once())
            ->method('isGitBased')
            ->willReturn(false);

        $this->patchCommand->expects($this->once())
            ->method('applyCheck')
            ->with($expectedPatchContent);

        $this->assertTrue($this->applier->checkApply($patchContent));
    }
}
