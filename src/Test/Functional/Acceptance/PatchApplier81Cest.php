<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php81
 */
class PatchApplier81Cest extends PatchApplierCest
{
    /**
     * @return array
     */
    protected function patchesDataProvider(): array
    {
        return [
            // Magento 2.4.4 versions (PHP 8.1)
            // Note: 2.4.4-p1 has a known bug with ReflectionUnionType::getName() - use p2 or later
            // Ref: https://experienceleague.adobe.com/en/docs/commerce-operations/tools/quality-patches-tool/release-notes#v1-1-50
            ['templateVersion' => '2.4.4-p1-p8', 'magentoVersion' => '2.4.4-p2'],
            ['templateVersion' => '2.4.4', 'magentoVersion' => '2.4.4-p16'],
            // Magento 2.4.5 versions (PHP 8.1)
            ['templateVersion' => '2.4.5-p1-p7', 'magentoVersion' => '2.4.5-p1'],
            ['templateVersion' => '2.4.5', 'magentoVersion' => '2.4.5-p15'],
        ];
    }
}
