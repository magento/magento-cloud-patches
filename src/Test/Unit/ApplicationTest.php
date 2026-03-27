<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit;

use Composer\Composer;
use Composer\Package\RootPackageInterface;
use Magento\CloudPatches\Application;
use Magento\CloudPatches\Command;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Unit tests for Application class.
 *
 * @inheritDoc
 */
class ApplicationTest extends TestCase
{
    /**
     * @var ContainerInterface|Stub
     */
    private $container;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $package = $this->createStub(RootPackageInterface::class);
        $package->method('getPrettyName')->willReturn('magento/magento-cloud-patches');
        $package->method('getPrettyVersion')->willReturn('1.0.0');

        $composer = $this->createStub(Composer::class);
        $composer->method('getPackage')->willReturn($package);

        $this->container = $this->createStub(ContainerInterface::class);
        $this->container->method('get')
            ->with(Composer::class)
            ->willReturn($composer);
    }

    /**
     * Test constructor resolves application name and version from Composer package metadata.
     *
     * @return void
     */
    public function testConstructorResolvesNameAndVersionFromComposer(): void
    {
        $application = new Application($this->container);

        $this->assertSame('magento/magento-cloud-patches', $application->getName());
        $this->assertSame('1.0.0', $application->getVersion());
    }

    /**
     * Test getDefaultCommands includes all registered command classes.
     *
     * @return void
     */
    public function testGetDefaultCommandsIncludesAllRegisteredCommands(): void
    {
        $applyCommand  = $this->createStub(Command\Apply::class);
        $revertCommand = $this->createStub(Command\Revert::class);
        $statusCommand = $this->createStub(Command\Status::class);

        // Stubs bypass the constructor so configure() never runs; stub the
        // minimum surface that Symfony's Application::add() inspects.
        $this->configureCommandStub($applyCommand, 'apply');
        $this->configureCommandStub($revertCommand, 'revert');
        $this->configureCommandStub($statusCommand, 'status');

        $package = $this->createStub(RootPackageInterface::class);
        $package->method('getPrettyName')->willReturn('magento/magento-cloud-patches');
        $package->method('getPrettyVersion')->willReturn('1.0.0');

        $composer = $this->createStub(Composer::class);
        $composer->method('getPackage')->willReturn($package);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [Composer::class, $composer],
                [Command\Apply::class, $applyCommand],
                [Command\Revert::class, $revertCommand],
                [Command\Status::class, $statusCommand],
            ]);

        $application = new Application($container);
        $commands    = $application->all();

        // 4 built-in Symfony commands (help, list, _complete, completion) + 3 registered = 7.
        $this->assertArrayHasKey('apply', $commands);
        $this->assertArrayHasKey('revert', $commands);
        $this->assertArrayHasKey('status', $commands);
        $this->assertCount(7, $commands);
    }

    /**
     * Configure minimum Symfony Application::add() surface on a command stub.
     *
     * @param Stub&SymfonyCommand $stub
     * @param string              $name
     * @return void
     */
    private function configureCommandStub(Stub $stub, string $name): void
    {
        $stub->method('getName')->willReturn($name);
        $stub->method('getAliases')->willReturn([]);
        $stub->method('isEnabled')->willReturn(true);
        $stub->method('isHidden')->willReturn(false);
    }
}
