<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches;

use Composer\Composer;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * @inheritdoc
 */
class Application extends SymfonyApplication
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * Command classes registered by default in the application.
     *
     * @var class-string<SymfonyCommand>[]
     */
    private const DEFAULT_COMMAND_CLASSES = [
        Command\Apply::class,
        Command\Revert::class,
        Command\Status::class,
    ];

    /**
     * Application constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $package         = $container->get(Composer::class)->getPackage();

        parent::__construct(
            $package->getPrettyName(),
            $package->getPrettyVersion()
        );
    }

    /**
     * Get the default commands that should always be available.
     *
     * @return SymfonyCommand[]
     */
    protected function getDefaultCommands(): array
    {
        $instances = array_map(
            fn (string $commandClass): SymfonyCommand =>
                $this->container->get($commandClass),
            self::DEFAULT_COMMAND_CLASSES
        );

        return array_merge(parent::getDefaultCommands(), $instances);
    }
}
