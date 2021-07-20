<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector\Fixture;

return [
    'MDVA-2470' => [
        'categories' => [
            'Performance'
        ],
        'title' => 'Fix asset locker race condition when using Redis',
        'packages' => [
            'magento/magento2-base' => [
                '2.1.4 - 2.1.14' => [
                    'file' => 'MDVA-2470__fix_asset_locking_race_condition__2.1.4.patch'
                ],
                '2.2.0 - 2.2.5' => [
                    'file' => 'MDVA-2470__fix_asset_locking_race_condition__2.2.0.patch'
                ]
            ],
            'magento/magento2-ee-base' => [
                '2.2.0 - 2.2.5' => [
                    'file' => 'MDVA-2470__fix_asset_locking_race_condition__2.2.0_ee.patch'
                ]
            ]
        ],
    ],
    'MDVA-2033' => [
        'categories' => [
            'Performance'
        ],
        'title' => 'Allow DB dumps done with the support module to complete',
        'packages' => [
            'magento/magento2-ee-base' => [
                '2.2.0 - 2.2.5' => [
                    'file' => 'MDVA-2033__prevent_deadlock_during_db_dump__2.2.0.patch',
                    'require' => ['MC-11111', 'MC-22222'],
                    'replaced-with' => 'MC-33333',
                    'deprecated' => true
                ]
            ]
        ],
    ],
];
