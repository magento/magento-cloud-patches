<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php84
 */
class Acceptance84Cest extends AcceptanceCest
{
    /**
     * @return array
     */
    protected function patchesDataProvider(): array
    {
        return [
            // Magento 2.4.8 versions (PHP 8.3, 8.4)
            ['templateVersion' => '2.4.8', 'magentoVersion' => '2.4.8'],
            ['templateVersion' => '2.4.8', 'magentoVersion' => '2.4.8-p1'],
            ['templateVersion' => '2.4.8', 'magentoVersion' => '2.4.8-p2'],
            ['templateVersion' => '2.4.8', 'magentoVersion' => '2.4.8-p3'],
            // Magento 2.4.9 alpha versions
            ['templateVersion' => '2.4.9-alpha', 'magentoVersion' => '2.4.9-alpha3'],
        ];
    }
}
