<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

class PatchCommandSelector implements PatchCommandInterface
{
    /**
     * @var PatchCommandInterface[]
     */
    private $commands;

    /**
     * PatchCommandSelector constructor.
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
    public function apply(string $patch): bool
    {
        return $this->getCommand()->apply($patch);
    }

    /**
     * @inheritDoc
     */
    public function revert(string $patch): bool
    {
        return $this->getCommand()->revert($patch);
    }

    /**
     * @inheritDoc
     */
    public function check(string $patch): bool
    {
        return $this->getCommand()->check($patch);
    }

    /**
     * @inheritDoc
     */
    public function status(string $patch): bool
    {
        return $this->getCommand()->status($patch);
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
     */
    private function getCommand()
    {
        foreach ($this->commands as $command) {
            if ($command->isInstalled()) {
                return $command;
            }
        }
        throw new PatchCommandNotFound();
    }
}
