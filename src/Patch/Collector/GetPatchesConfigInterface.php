<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CloudPatches\Patch\Collector;

interface GetPatchesConfigInterface
{
    /**
     * @return array
     * @throws CollectorException
     */
    public function execute(): array;
}
