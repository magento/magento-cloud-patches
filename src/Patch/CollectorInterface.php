<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\Data\PatchInterface;

/**
 * Patches collector interface
 */
interface CollectorInterface
{
    /**
     * Collects patches.
     *
     * @return PatchInterface[]
     * @throws CollectorException
     */
    public function collect(): array;
}
