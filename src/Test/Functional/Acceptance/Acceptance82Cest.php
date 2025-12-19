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
class Acceptance82Cest extends AcceptanceCest
{
    /**
     * @return array
     */
    protected function patchesDataProvider(): array
    {
        return [
            // Magento 2.4.6 versions (PHP 8.1, 8.2)
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p1'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p2'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p3'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p4'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p5'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p6'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p7'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p8'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p9'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p10'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p11'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p12'],
            ['templateVersion' => '2.4.6', 'magentoVersion' => '2.4.6-p13'],
            // Magento 2.4.7 versions (PHP 8.2, 8.3)
            ['templateVersion' => '2.4.7', 'magentoVersion' => '2.4.7'],
            ['templateVersion' => '2.4.7', 'magentoVersion' => '2.4.7-p1'],
            ['templateVersion' => '2.4.7', 'magentoVersion' => '2.4.7-p2'],
            ['templateVersion' => '2.4.7', 'magentoVersion' => '2.4.7-p3'],
            ['templateVersion' => '2.4.7', 'magentoVersion' => '2.4.7-p4'],
            ['templateVersion' => '2.4.7', 'magentoVersion' => '2.4.7-p5'],
            ['templateVersion' => '2.4.7', 'magentoVersion' => '2.4.7-p6'],
            ['templateVersion' => '2.4.7', 'magentoVersion' => '2.4.7-p7'],
            ['templateVersion' => '2.4.7', 'magentoVersion' => '2.4.7-p8'],
        ];
    }
}
