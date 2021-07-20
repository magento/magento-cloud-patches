<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector;

/**
 * Provides config for patches.
 */
interface GetPatchesConfigInterface
{
    /**
     * @return array
     * @throws CollectorException
     */
    public function execute(): array;
}
