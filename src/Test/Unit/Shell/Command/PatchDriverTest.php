<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Shell\Command;

use Magento\CloudPatches\Patch\PatchCommandException;
use Magento\CloudPatches\Shell\Command\PatchDriver;
use Magento\CloudPatches\Shell\ProcessFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Tests unix patch driver
 */
class PatchDriverTest extends TestCase
{
    /**
     * @var PatchDriver
     */
    private $command;
    /**
     * @var string
     */
    private $baseDir;
    /**
     * @var string
     */
    private $cwd;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->baseDir = dirname(__DIR__, 5) . '/tests/unit/';
        $this->cwd = $this->baseDir . 'var/';
        $processFactory = $this->createMock(ProcessFactory::class);
        $processFactory->method('create')
            ->willReturnCallback(
                function (array $cmd, string $input = null) {
                    return new Process(
                        $cmd,
                        $this->cwd,
                        null,
                        $input
                    );
                }
            );
        $this->command = new PatchDriver(
            $processFactory
        );
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        foreach (glob($this->cwd . '*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    /**
     * Tests that patch is applied
     */
    public function testApply()
    {
        $this->copyFileToWorkingDir($this->getFixtureFile('file1.md'));
        $patchContent = $this->getFileContent($this->getFixtureFile('file1.patch'));
        $this->command->apply($patchContent);
        $expected = $this->getFileContent($this->getFixtureFile('file1_applied_patch.md'));
        $actual = $this->getFileContent($this->getVarFile('file1.md'));
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that patch is not applied to any target files if an error occurs
     */
    public function testApplyFailure()
    {
        $this->copyFileToWorkingDir($this->getFixtureFile('file1.md'));
        $this->copyFileToWorkingDir($this->getFixtureFile('file2_applied_patch.md'), 'file2.md');
        $patchContent = $this->getFileContent($this->getFixtureFile('file1_and_file2.patch'));
        $exception = null;
        try {
            $this->command->apply($patchContent);
        } catch (PatchCommandException $e) {
            $exception = $e;
        }
        $this->assertNotNull($exception);
        $expected = $this->getFileContent($this->getFixtureFile('file1.md'));
        $actual = $this->getFileContent($this->getVarFile('file1.md'));
        $this->assertEquals($expected, $actual);
        $expected = $this->getFileContent($this->getFixtureFile('file2_applied_patch.md'));
        $actual = $this->getFileContent($this->getVarFile('file2.md'));
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that patch is reverted
     */
    public function testRevert()
    {
        $this->copyFileToWorkingDir($this->getFixtureFile('file1_applied_patch.md'), 'file1.md');
        $patchContent = $this->getFileContent($this->getFixtureFile('file1.patch'));
        $this->command->revert($patchContent);
        $expected = $this->getFileContent($this->getFixtureFile('file1.md'));
        $actual = $this->getFileContent($this->getVarFile('file1.md'));
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that patch is not reverted in any target files if an error occurs
     */
    public function testRevertFailure()
    {
        $this->copyFileToWorkingDir($this->getFixtureFile('file1_applied_patch.md'), 'file1.md');
        $this->copyFileToWorkingDir($this->getFixtureFile('file2.md'));
        $patchContent = $this->getFileContent($this->getFixtureFile('file1_and_file2.patch'));
        $exception = null;
        try {
            $this->command->revert($patchContent);
        } catch (PatchCommandException $e) {
            $exception = $e;
        }
        $this->assertNotNull($exception);
        $expected = $this->getFileContent($this->getFixtureFile('file1_applied_patch.md'));
        $actual = $this->getFileContent($this->getVarFile('file1.md'));
        $this->assertEquals($expected, $actual);
        $expected = $this->getFileContent($this->getFixtureFile('file2.md'));
        $actual = $this->getFileContent($this->getVarFile('file2.md'));
        $this->assertEquals($expected, $actual);
    }

    /**
     * Get file path in var directory
     *
     * @param string $name
     * @return string
     */
    private function getVarFile(string $name): string
    {
        return $this->cwd . $name;
    }

    /**
     * Get file path in files directory
     *
     * @param string $name
     * @return string
     */
    private function getFixtureFile(string $name): string
    {
        return $this->baseDir . '_data/files/' . $name;
    }

    /**
     * Get the file content
     *
     * @param string $path
     * @return string
     */
    private function getFileContent(string $path): string
    {
        return file_get_contents($path);
    }

    /**
     * Copy file to working directory
     *
     * @param string $path
     * @param string|null $name
     */
    private function copyFileToWorkingDir(string $path, string $name = null)
    {
        $name = $name ?? basename($path);
        copy($path, $this->getVarFile($name));
    }
}
