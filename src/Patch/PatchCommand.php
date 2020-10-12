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
    private $drivers;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @param DriverInterface[] $drivers
     */
    public function __construct(
        array $drivers
    ) {
        $this->drivers = $drivers;
    }

    /**
     * @inheritDoc
     */
    public function apply(string $patch)
    {
        $this->getDriver()->apply($patch);
    }

    /**
     * @inheritDoc
     */
    public function revert(string $patch)
    {
        $this->getDriver()->revert($patch);
    }

    /**
     * @inheritDoc
     */
    public function applyCheck(string $patch)
    {
        $this->getDriver()->applyCheck($patch);
    }

    /**
     * @inheritDoc
     */
    public function revertCheck(string $patch)
    {
        $this->getDriver()->revertCheck($patch);
    }

    /**
     * Returns first available driver
     *
     * @return DriverInterface
     * @throws PatchCommandNotFound
     */
    private function getDriver(): DriverInterface
    {
        if ($this->driver === null) {
            foreach ($this->drivers as $driver) {
                if ($driver->isInstalled()) {
                    $this->driver = $driver;
                    break;
                }
            }
            if ($this->driver === null) {
                throw new PatchCommandNotFound();
            }
        }
        return $this->driver;
    }
}
