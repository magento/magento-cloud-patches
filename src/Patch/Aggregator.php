<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Data\PatchInterface;

/**
 * PatchPool Factory.
 */
class Aggregator
{
    /**
     * @var AggregatedPatchFactory
     */
    private $aggregatedPatchFactory;

    /**
     * @param AggregatedPatchFactory $aggregatedPatchFactory
     */
    public function __construct(AggregatedPatchFactory $aggregatedPatchFactory)
    {
        $this->aggregatedPatchFactory = $aggregatedPatchFactory;
    }

    /**
     * Returns collection of aggregated patches.
     *
     * @param PatchInterface[] $patches
     * @return AggregatedPatchInterface[]
     */
    public function aggregate(array $patches): array
    {
        $patchGroups = [];
        foreach ($patches as $patch) {
            $patchGroups[$patch->getId()][] = $patch;
        }

        $result = [];
        foreach ($patchGroups as $patchGroup) {
            $result[] = $this->aggregatedPatchFactory->create($patchGroup);
        }

        return $result;
    }
}
