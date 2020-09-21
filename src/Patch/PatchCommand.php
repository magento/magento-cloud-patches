<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

/**
 * Patch command selector
 */
class PatchCommand implements PatchCommandInterface
{
    /**
     * @var PatchCommandInterface[]
     */
    private $commands;

    /**
     * @var PatchCommandInterface
     */
    private $command;

    /**
     * @param PatchCommandInterface[] $commands
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
     * @inheritDoc
     */
    public function isInstalled(): bool
    {
        return $this->getCommand()->isInstalled();
    }

    /**
     * Return first available command
     *
     * @return PatchCommandInterface
     * @throws PatchCommandNotFound
     */
    private function getCommand(): PatchCommandInterface
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
