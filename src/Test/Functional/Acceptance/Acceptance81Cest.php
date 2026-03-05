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
class Acceptance81Cest extends AcceptanceCest
{
    /**
     * @return array
     */
    protected function patchesDataProvider(): array
    {
        return [
            // Magento 2.4.4 versions (PHP 8.1)
            ['templateVersion' => '2.4.4-p1-p8', 'magentoVersion' => '2.4.4'],
            ['templateVersion' => '2.4.4-p1-p8', 'magentoVersion' => '2.4.4-p1'],
            ['templateVersion' => '2.4.4-p1-p8', 'magentoVersion' => '2.4.4-p2'],
            ['templateVersion' => '2.4.4-p1-p8', 'magentoVersion' => '2.4.4-p3'],
            ['templateVersion' => '2.4.4-p1-p8', 'magentoVersion' => '2.4.4-p4'],
            ['templateVersion' => '2.4.4-p1-p8', 'magentoVersion' => '2.4.4-p5'],
            ['templateVersion' => '2.4.4-p1-p8', 'magentoVersion' => '2.4.4-p6'],
            ['templateVersion' => '2.4.4-p1-p8', 'magentoVersion' => '2.4.4-p7'],
            ['templateVersion' => '2.4.4-p1-p8', 'magentoVersion' => '2.4.4-p8'],
            ['templateVersion' => '2.4.4-p9-p11', 'magentoVersion' => '2.4.4-p9'],
            ['templateVersion' => '2.4.4-p9-p11', 'magentoVersion' => '2.4.4-p10'],
            ['templateVersion' => '2.4.4-p9-p11', 'magentoVersion' => '2.4.4-p11'],
            ['templateVersion' => '2.4.4', 'magentoVersion' => '2.4.4-p12'],
            ['templateVersion' => '2.4.4', 'magentoVersion' => '2.4.4-p13'],
            ['templateVersion' => '2.4.4', 'magentoVersion' => '2.4.4-p14'],
            ['templateVersion' => '2.4.4', 'magentoVersion' => '2.4.4-p15'],
            ['templateVersion' => '2.4.4', 'magentoVersion' => '2.4.4-p16'],
            // Magento 2.4.5 versions (PHP 8.1)
            ['templateVersion' => '2.4.5-p1-p7', 'magentoVersion' => '2.4.5'],
            ['templateVersion' => '2.4.5-p1-p7', 'magentoVersion' => '2.4.5-p1'],
            ['templateVersion' => '2.4.5-p1-p7', 'magentoVersion' => '2.4.5-p2'],
            ['templateVersion' => '2.4.5-p1-p7', 'magentoVersion' => '2.4.5-p3'],
            ['templateVersion' => '2.4.5-p1-p7', 'magentoVersion' => '2.4.5-p4'],
            ['templateVersion' => '2.4.5-p1-p7', 'magentoVersion' => '2.4.5-p5'],
            ['templateVersion' => '2.4.5-p1-p7', 'magentoVersion' => '2.4.5-p6'],
            ['templateVersion' => '2.4.5-p1-p7', 'magentoVersion' => '2.4.5-p7'],
            ['templateVersion' => '2.4.5-p8-p10', 'magentoVersion' => '2.4.5-p8'],
            ['templateVersion' => '2.4.5-p8-p10', 'magentoVersion' => '2.4.5-p9'],
            ['templateVersion' => '2.4.5-p8-p10', 'magentoVersion' => '2.4.5-p10'],
            ['templateVersion' => '2.4.5', 'magentoVersion' => '2.4.5-p11'],
            ['templateVersion' => '2.4.5', 'magentoVersion' => '2.4.5-p12'],
            ['templateVersion' => '2.4.5', 'magentoVersion' => '2.4.5-p13'],
            ['templateVersion' => '2.4.5', 'magentoVersion' => '2.4.5-p14'],
            ['templateVersion' => '2.4.5', 'magentoVersion' => '2.4.5-p15'],
        ];
    }
}
