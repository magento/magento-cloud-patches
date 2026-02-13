<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php85
 */
class PatchApplier85Cest extends PatchApplierCest
{
    /**
     * Patches data provider.
     *
     * @return array
     */
    protected function patchesDataProvider(): array
    {
        return [
            // Magento 2.4.9-beta1 versions (PHP 8.5)
            ['templateVersion' => '2.4.9-alpha-opensearch3.0', 'magentoVersion' => '2.4.9-beta101'],
        ];
    }
}
