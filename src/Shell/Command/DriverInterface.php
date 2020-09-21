<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Shell\Command;

use Magento\CloudPatches\Patch\PatchCommandInterface;

/**
 * Patch command driver interface
 */
interface DriverInterface extends PatchCommandInterface
{
    /**
     * Checks if the driver is installed
     *
     * @return bool
     */
    public function isInstalled(): bool;
}
