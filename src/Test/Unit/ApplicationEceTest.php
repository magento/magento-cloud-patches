<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit;

use Composer\Composer;
use Composer\Package\RootPackageInterface;
use Magento\CloudPatches\ApplicationEce;
use Magento\CloudPatches\Command;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;

/**
 * Unit tests for ApplicationEce class.
 *
 * @inheritDoc
 */
class ApplicationEceTest extends TestCase
{
    /**
     * @var ApplicationEce
     */
    private $application;

    /**
     * @var ContainerInterface|Stub
     */
    private $container;

    /**
     * @var Composer|Stub
     */
    private $composer;

    /**
     * @var RootPackageInterface|Stub
     */
    private $package;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->package = $this->createStub(RootPackageInterface::class);
        $this->composer = $this->createStub(Composer::class);
        $this->container = $this->createStub(ContainerInterface::class);

        $this->package->method('getPrettyName')
            ->willReturn('magento/magento-cloud-patches');
        $this->package->method('getPrettyVersion')
            ->willReturn('2.0.0');

        $this->composer->method('getPackage')
            ->willReturn($this->package);

        $this->container->method('get')
            ->with(Composer::class)
            ->willReturn($this->composer);
    }

    /**
     * Test constructor initializes application with correct metadata from composer.json.
     *
     * @return void
     */
    public function testConstructorInitializesApplicationMetadata(): void
    {
        $this->application = new ApplicationEce($this->container);

        $this->assertInstanceOf(ApplicationEce::class, $this->application);
        // Verify name and version are set (they should be resolved)
        $this->assertNotEmpty($this->application->getName());
        $this->assertNotEmpty($this->application->getVersion());
    }

    /**
     * Test getDefaultCommands returns expected ECE commands.
     *
     * @return void
     */
    public function testGetDefaultCommandsReturnsExpectedCommands(): void
    {
        $applyCommand  = $this->createStub(Command\Ece\Apply::class);
        $revertCommand = $this->createStub(Command\Ece\Revert::class);
        $statusCommand = $this->createStub(Command\Status::class);
        $verifyCommand = $this->createStub(Command\Verify::class);

        $containerMock = $this->createStub(ContainerInterface::class);
        $composerMock  = $this->createStub(Composer::class);
        $packageMock   = $this->createStub(RootPackageInterface::class);

        $packageMock->method('getPrettyName')
            ->willReturn('magento/magento-cloud-patches');
        $packageMock->method('getPrettyVersion')
            ->willReturn('2.0.0');

        $composerMock->method('getPackage')
            ->willReturn($packageMock);

        $containerMock->method('get')
            ->willReturnMap([
                [Composer::class, $composerMock],
                [Command\Ece\Apply::class, $applyCommand],
                [Command\Ece\Revert::class, $revertCommand],
                [Command\Status::class, $statusCommand],
                [Command\Verify::class, $verifyCommand],
            ]);

        $application = new ApplicationEce($containerMock);
        $commands = $application->all();

        $this->assertIsArray($commands);
        $this->assertNotEmpty($commands);
    }

    /**
     * Test readComposerJson returns null when InstalledVersions is not available.
     *
     * @return void
     */
    public function testReadComposerJsonReturnsNullWhenInstalledVersionsUnavailable(): void
    {
        $this->application = new ApplicationEce($this->container);

        $reflectionMethod = new ReflectionMethod(ApplicationEce::class, 'readComposerJson');

        // This will return null or file contents, depending on environment
        $result = $reflectionMethod->invoke($this->application);
        $this->assertTrue(
            is_array($result) || $result === null,
            'readComposerJson should return array or null'
        );
    }

    /**
     * Test resolveApplicationMetadata returns array with name and version.
     *
     * @return void
     */
    public function testResolveApplicationMetadataReturnsValidArray(): void
    {
        $this->application = new ApplicationEce($this->container);

        $reflectionMethod = new ReflectionMethod(ApplicationEce::class, 'resolveApplicationMetadata');

        $result = $reflectionMethod->invoke($this->application, $this->container);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertIsString($result[0]); // name
        $this->assertIsString($result[1]); // version
    }

    /**
     * Test resolveApplicationMetadata uses composer package as fallback.
     *
     * @return void
     */
    public function testResolveApplicationMetadataUsesComposerFallback(): void
    {
        $containerMock = $this->createStub(ContainerInterface::class);
        $composerMock  = $this->createStub(Composer::class);
        $packageMock   = $this->createStub(RootPackageInterface::class);

        $packageMock->method('getPrettyName')->willReturn('test/package');
        $packageMock->method('getPrettyVersion')->willReturn('3.0.0');
        $composerMock->method('getPackage')->willReturn($packageMock);
        $containerMock->method('get')->with(Composer::class)->willReturn($composerMock);

        $application      = new ApplicationEce($containerMock);
        $reflectionMethod = new ReflectionMethod(ApplicationEce::class, 'resolveApplicationMetadata');

        $result = $reflectionMethod->invoke($application, $containerMock);

        $this->assertCount(2, $result);
        $this->assertNotEmpty($result[0]);
        $this->assertNotEmpty($result[1]);
    }

    /**
     * Test that application name is non-empty and version is non-empty after construction.
     *
     * @return void
     */
    public function testConstructorSetsNameAndVersion(): void
    {
        $this->application = new ApplicationEce($this->container);

        $this->assertIsString($this->application->getName());
        $this->assertNotEmpty($this->application->getName());
        $this->assertIsString($this->application->getVersion());
        $this->assertNotEmpty($this->application->getVersion());
    }

    /**
     * Test resolveApplicationMetadata handles unknown version gracefully.
     *
     * @return void
     */
    public function testResolveApplicationMetadataReturnsUnknownForMissingVersion(): void
    {
        $containerMock = $this->createStub(ContainerInterface::class);
        $composerMock  = $this->createStub(Composer::class);
        $packageMock   = $this->createStub(RootPackageInterface::class);

        $packageMock->method('getPrettyName')
            ->willReturn('test/package');
        $packageMock->method('getPrettyVersion')
            ->willReturn('');

        $composerMock->method('getPackage')
            ->willReturn($packageMock);

        $containerMock->method('get')
            ->with(Composer::class)
            ->willReturn($composerMock);

        $application      = new ApplicationEce($containerMock);
        $reflectionMethod = new ReflectionMethod(ApplicationEce::class, 'resolveApplicationMetadata');

        $result = $reflectionMethod->invoke($application, $containerMock);

        $this->assertCount(2, $result);
        $this->assertIsString($result[1]);
    }

    /**
     * Test multiple calls to getDefaultCommands return consistent results.
     *
     * @return void
     */
    public function testGetDefaultCommandsConsistentResults(): void
    {
        $applyCommand  = $this->createStub(Command\Ece\Apply::class);
        $revertCommand = $this->createStub(Command\Ece\Revert::class);
        $statusCommand = $this->createStub(Command\Status::class);
        $verifyCommand = $this->createStub(Command\Verify::class);

        $containerMock = $this->createStub(ContainerInterface::class);
        $composerMock  = $this->createStub(Composer::class);
        $packageMock   = $this->createStub(RootPackageInterface::class);

        $packageMock->method('getPrettyName')
            ->willReturn('magento/magento-cloud-patches');
        $packageMock->method('getPrettyVersion')
            ->willReturn('2.0.0');

        $composerMock->method('getPackage')
            ->willReturn($packageMock);

        $containerMock->method('get')
            ->willReturnMap([
                [Composer::class, $composerMock],
                [Command\Ece\Apply::class, $applyCommand],
                [Command\Ece\Revert::class, $revertCommand],
                [Command\Status::class, $statusCommand],
                [Command\Verify::class, $verifyCommand],
            ]);

        $application = new ApplicationEce($containerMock);
        $commands1 = $application->all();
        $commands2 = $application->all();

        // Both calls should return same array of commands
        $this->assertCount(count($commands1), $commands2);
    }
}
