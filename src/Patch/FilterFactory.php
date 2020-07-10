<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

/**
 * Creates patch filter.
 */
class FilterFactory
{
    /**
     * Returns patch apply filter.
     *
     * @param string[] $argPatches 'List of patches' input argument.
     * @return string[]|null
     */
    public function createApplyFilter(array $argPatches)
    {
        $firstArgument = reset($argPatches);
        if ($firstArgument === '*') {
            return [];
        }

        return $argPatches ?: null;
    }

    /**
     * Returns patch revert filter.
     *
     * @param bool $optAll 'All patches' input option.
     * @param string[] $argPatches 'List of patches' input argument.
     * @return string[]|null
     */
    public function createRevertFilter(bool $optAll, array $argPatches)
    {
        if ($optAll) {
            return [];
        }

        return $argPatches ?: null;
    }
}
