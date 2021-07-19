<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Pool;

use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\CollectorInterface;
use Magento\CloudPatches\Patch\Data\PatchInterface;

/**
 * Contains required patches.
 */
class RequiredPool
{
    /**
     * @var PatchInterface[]
     */
    private $items = [];

    /**
     * @param array $collectors
     * @throws CollectorException
     */
    public function __construct(array $collectors = [])
    {
        /** @var CollectorInterface $collector */
        foreach ($collectors as $collector) {
            $this->items = array_merge($this->items, $collector->collect());
        }
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
