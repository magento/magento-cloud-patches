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
    protected function patchesDataProvider(): array
    {
        return [
            ['templateVersion' => '2.1.16', 'magentoVersion' => '>= 2.1.16 <2.1.17'],
            ['templateVersion' => '2.1.17', 'magentoVersion' => '>= 2.1.17 <2.1.18'],
            ['templateVersion' => '2.1.18', 'magentoVersion' => '>= 2.1.18 <2.1.19'],
            ['templateVersion' => '2.2.0', 'magentoVersion' => '>= 2.2.0 <2.2.1'],
            ['templateVersion' => '2.2.1', 'magentoVersion' => '>= 2.2.1 <2.2.2'],
            ['templateVersion' => '2.2.2', 'magentoVersion' => '>= 2.2.2 <2.2.3'],
            ['templateVersion' => '2.2.3', 'magentoVersion' => '>= 2.2.3 <2.2.4'],
            ['templateVersion' => '2.2.4', 'magentoVersion' => '>= 2.2.4 <2.2.5'],
            ['templateVersion' => '2.2.5', 'magentoVersion' => '>= 2.2.5 <2.2.6'],
            ['templateVersion' => '2.2.6', 'magentoVersion' => '>= 2.2.6 <2.2.7'],
            ['templateVersion' => '2.2.7', 'magentoVersion' => '>= 2.2.7 <2.2.8'],
            ['templateVersion' => '2.2.8', 'magentoVersion' => '>= 2.2.8 <2.2.9'],
            ['templateVersion' => '2.2.9', 'magentoVersion' => '>= 2.2.9 <2.2.10'],
            ['templateVersion' => '2.2.10', 'magentoVersion' => '>= 2.2.10 <2.2.11'],
            ['templateVersion' => '2.2.11', 'magentoVersion' => '>= 2.2.11 <2.2.12'],
        ];
    }
}
