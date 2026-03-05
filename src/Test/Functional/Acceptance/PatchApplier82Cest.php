<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php82
 */
class PatchApplier82Cest extends PatchApplierCest
{
    /**
     * @return array
     */
    protected function patchesDataProvider(): array
    {
        return [
            // Magento 2.4.6 versions (PHP 8.1, 8.2)
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p13'],
            // Magento 2.4.7 versions (PHP 8.2, 8.3)
            ['templateVersion' => '2.4.7', 'magentoVersion' => '2.4.7'],
            ['templateVersion' => '2.4.7', 'magentoVersion' => '2.4.7-p8'],
        ];
    }
}
