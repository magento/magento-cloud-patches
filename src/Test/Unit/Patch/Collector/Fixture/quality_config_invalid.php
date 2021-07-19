<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector\Fixture;

return [
    'MDVA-2033' => [
        'categories' => [
            'Performance'
        ],
        'title' => 'Allow DB dumps done with the support module to complete',
        'packages' => [
            'magento/magento2-ee-base' => [
                '2.2.0 - 2.2.5' => [
                    'require' => 'MC-11111 MC-22222',
                    'replaced-with' => ['MC-33333'],
                    'deprecated' => 1
                ]
            ]
        ],
    ],
];
