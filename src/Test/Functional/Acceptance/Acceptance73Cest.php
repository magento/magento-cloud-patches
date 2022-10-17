<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php73
 */
class Acceptance73Cest extends AcceptanceCest
{
    /**
     * @return array
     */
    protected function patchesDataProvider(): array
    {
        return [
            ['templateVersion' => '2.3.3', 'magentoVersion' => '2.3.3'],
            ['templateVersion' => '2.3.3', 'magentoVersion' => '2.3.3-p1'],
            ['templateVersion' => '2.3.4', 'magentoVersion' => '2.3.4'],
            ['templateVersion' => '2.3.4', 'magentoVersion' => '2.3.4-p2'],
            ['templateVersion' => '2.3.5', 'magentoVersion' => '2.3.5'],
            ['templateVersion' => '2.3.5', 'magentoVersion' => '2.3.5-p1'],
            ['templateVersion' => '2.3.5', 'magentoVersion' => '2.3.5-p2'],
            ['templateVersion' => '2.3.6', 'magentoVersion' => '2.3.6'],
            ['templateVersion' => '2.3.6', 'magentoVersion' => '2.3.6-p1'],
            ['templateVersion' => '2.3.7', 'magentoVersion' => '2.3.7'],
            ['templateVersion' => '2.3.7', 'magentoVersion' => '2.3.7-p1'],
            ['templateVersion' => '2.3.7', 'magentoVersion' => '2.3.7-p2'],
            ['templateVersion' => '2.3.7', 'magentoVersion' => '2.3.7-p3'],
            ['templateVersion' => '2.3.7', 'magentoVersion' => '2.3.7-p4'],
            ['templateVersion' => '2.4.0', 'magentoVersion' => '2.4.0'],
        ];
    }
}
