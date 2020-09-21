<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\Shell\Command\DriverInterface;

/**
 * Patch command selector
 */
class PatchCommand implements PatchCommandInterface
{
    /**
     * @var DriverInterface[]
     */
    private $commands;

    /**
     * @var DriverInterface
     */
    private $command;

    /**
     * @param DriverInterface[] $commands
     */
    public function __construct(
        array $commands
    ) {
        $this->commands = $commands;
    }

    /**
     * @inheritDoc
     */
    public function apply(string $patch)
    {
        $this->getCommand()->apply($patch);
    }

    /**
     * @inheritDoc
     */
    public function revert(string $patch)
    {
        $this->getCommand()->revert($patch);
    }

    /**
     * @inheritDoc
     */
    public function applyCheck(string $patch)
    {
        $this->getCommand()->applyCheck($patch);
    }

    /**
     * @inheritDoc
     */
    public function revertCheck(string $patch)
    {
        $this->getCommand()->revertCheck($patch);
    }

    /**
     * Return first available command
     *
     * @return DriverInterface
     * @throws PatchCommandNotFound
     */
    private function getCommand(): DriverInterface
    {
        if ($this->command === null) {
            foreach ($this->commands as $command) {
                if ($command->isInstalled()) {
                    $this->command = $command;
                    break;
                }
            }
            if ($this->command === null) {
                throw new PatchCommandNotFound();
            }
        }
        return $this->command;
    }
}
