<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php71
 */
class Acceptance71Cest extends AcceptanceCest
{
    /**
     * @return array
     */
    public function patchesDataProvider(): array
    {
        return [
            ['magentoVersion' => '2.1.16'],
            ['magentoVersion' => '2.1.18'],
            ['magentoVersion' => '2.2.0'],
            ['magentoVersion' => '2.2.11'],
        ];
    }
}
