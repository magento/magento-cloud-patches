<?php
declare(strict_types=1);
/**
 * Unit test for PatchDriver class.
 */

namespace Magento\CloudPatches\Test\Unit\Shell\Command;

use Magento\CloudPatches\Shell\Command\DriverException;
use Magento\CloudPatches\Shell\Command\PatchDriver;
use Magento\CloudPatches\Shell\ProcessFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class PatchDriverTest
 */
class PatchDriverTest extends TestCase
{
    /**
     * @var string
     */
    private string $baseDir;

    /**
     * @var string
     */
    private string $cwd;

    /**
     * @var ProcessFactory|MockObject
     */
    private ProcessFactory $processFactoryMock;

    /**
     * Setup test dependencies and environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = dirname(__DIR__, 5) . '/tests/unit/';
        $this->cwd = $this->baseDir . 'var/';
        $this->processFactoryMock = $this->createMock(ProcessFactory::class);
    }

    /**
     * Clean up files after tests.
     *
     * @return void
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
     * Test successful patch apply.
     *
     * @return void
     */
    public function testApply(): void
    {
        $this->copyFileToWorkingDir($this->getFixtureFile('file1.md'));
        $patchContent = $this->getFileContent($this->getFixtureFile('file1.patch'));

        $this->processFactoryMock->method('create')->willReturnCallback(
            function (array $cmd, ?string $input = null) {
                return new Process($cmd, $this->cwd, null, $input);
            }
        );

        $command = new PatchDriver($this->processFactoryMock);
        $command->apply($patchContent);

        $expected = $this->getFileContent($this->getFixtureFile('file1_applied_patch.md'));
        $actual = $this->getFileContent($this->getVarFile('file1.md'));

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test patch apply failure handling.
     *
     * @return void
     */
    public function testApplyFailure(): void
    {
        $this->copyFileToWorkingDir($this->getFixtureFile('file1.md'));
        $this->copyFileToWorkingDir($this->getFixtureFile('file2_applied_patch.md'), 'file2.md');
        $patchContent = $this->getFileContent($this->getFixtureFile('file1_and_file2.patch'));

        $processMock = $this->createMock(Process::class);
        $processMock->method('mustRun')->willThrowException(new ProcessFailedException($processMock));

        $this->processFactoryMock->method('create')->willReturn($processMock);
        $command = new PatchDriver($this->processFactoryMock);

        $this->expectException(DriverException::class);
        $command->apply($patchContent);
    }

    /**
     * Test successful patch revert.
     *
     * @return void
     */
    public function testRevert(): void
    {
        $this->copyFileToWorkingDir($this->getFixtureFile('file1_applied_patch.md'), 'file1.md');
        $patchContent = $this->getFileContent($this->getFixtureFile('file1.patch'));

        $this->processFactoryMock->method('create')->willReturnCallback(
            function (array $cmd, ?string $input = null) {
                return new Process($cmd, $this->cwd, null, $input);
            }
        );

        $command = new PatchDriver($this->processFactoryMock);
        $command->revert($patchContent);

        $expected = $this->getFileContent($this->getFixtureFile('file1.md'));
        $actual = $this->getFileContent($this->getVarFile('file1.md'));

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test patch revert failure handling
     *
     * @return void
     */
    public function testRevertFailure(): void
    {
        $this->copyFileToWorkingDir($this->getFixtureFile('file1_applied_patch.md'), 'file1.md');
        $this->copyFileToWorkingDir($this->getFixtureFile('file2.md'));
        $patchContent = $this->getFileContent($this->getFixtureFile('file1_and_file2.patch'));

        $processMock = $this->createMock(Process::class);
        $processMock->method('mustRun')->willThrowException(new ProcessFailedException($processMock));

        $this->processFactoryMock->method('create')->willReturn($processMock);
        $command = new PatchDriver($this->processFactoryMock);

        $this->expectException(DriverException::class);
        $command->revert($patchContent);
    }

    /**
     * Get full path to a file in the test working directory.
     *
     * @param  string $name
     * @return string
     */
    private function getVarFile(string $name): string
    {
        return $this->cwd . $name;
    }

    /**
     * Get full path to a fixture file.
     *
     * @param  string $name
     * @return string
     */
    private function getFixtureFile(string $name): string
    {
        return $this->baseDir . '_data/files/' . $name;
    }

    /**
     * Get content from a file.
     *
     * @param  string $path
     * @return string
     */
    private function getFileContent(string $path): string
    {
        return file_get_contents($path);
    }

    /**
     * Copy a file to the test working directory.
     *
     * @param  string      $path
     * @param  string|null $name
     * @return void
     */
    private function copyFileToWorkingDir(string $path, ?string $name = null): void
    {
        $name = $name ?? basename($path);
        copy($path, $this->getVarFile($name));
    }
}
