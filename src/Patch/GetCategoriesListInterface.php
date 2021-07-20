<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

/**
 * Categories list provider.
 */
interface GetCategoriesListInterface
{
    /**
     * @return array
     */
    public function execute(): array;
}
