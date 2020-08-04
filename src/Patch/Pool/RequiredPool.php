<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Pool;

use Magento\CloudPatches\Patch\Collector\CloudCollector;
use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\Data\PatchInterface;

/**
 * Contains required patches.
 */
class RequiredPool
{
    /**
     * @var PatchInterface[]
     */
    private $items;

    /**
     * @param CloudCollector $cloudCollector
     * @throws CollectorException
     */
    public function __construct(
        CloudCollector $cloudCollector
    ) {
        $this->items = $cloudCollector->collect();
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
