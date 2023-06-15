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
            ['templateVersion' => '2.4.4', 'magentoVersion' => '2.4.4'],
            ['templateVersion' => '2.4.4', 'magentoVersion' => '2.4.4-p1'],
            ['templateVersion' => '2.4.4', 'magentoVersion' => '2.4.4-p2'],
            ['templateVersion' => '2.4.4', 'magentoVersion' => '2.4.4-p3'],
            ['templateVersion' => '2.4.4', 'magentoVersion' => '2.4.4-p4'],
            ['templateVersion' => '2.4.5', 'magentoVersion' => '2.4.5'],
            ['templateVersion' => '2.4.5', 'magentoVersion' => '2.4.5-p1'],
            ['templateVersion' => '2.4.5', 'magentoVersion' => '2.4.5-p2'],
        ];
    }
}
