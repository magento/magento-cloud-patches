<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Pool;

use Magento\CloudPatches\Patch\Collector\LocalCollector;
use Magento\CloudPatches\Patch\Data\PatchInterface;

/**
 * Contains local patches.
 */
class LocalPool
{
    /**
     * @var PatchInterface[]
     */
    private $items;

    /**
     * @param LocalCollector $localCollector
     */
    public function __construct(
        LocalCollector $localCollector
    ) {
        $this->items = $localCollector->collect();
    }

    /**
     * Returns list of patches.
     *
     * @return PatchInterface[]
     */
    public function getList()
    {
        return $this->items;
    }
}
