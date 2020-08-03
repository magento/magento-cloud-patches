<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches;

use Composer\Composer;
use Magento\CloudPatches\Command;
use Psr\Container\ContainerInterface;

/**
 * @inheritdoc
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct(
            $container->get(Composer::class)->getPackage()->getPrettyName(),
            $container->get(Composer::class)->getPackage()->getPrettyVersion()
        );
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultCommands()
    {
        return array_merge(parent::getDefaultCommands(), [
            $this->container->get(Command\Apply::class),
            $this->container->get(Command\Revert::class),
            $this->container->get(Command\Status::class)
        ]);
    }
}
