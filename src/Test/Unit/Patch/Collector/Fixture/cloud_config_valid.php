<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector\Fixture;

return [
    'magento/magento2-base' => [
        'Fix asset locker race condition when using Redis' => [
            '2.1.4 - 2.1.14' => 'MDVA-2470__fix_asset_locking_race_condition__2.1.4.patch',
            '2.2.0 - 2.2.5' => 'MDVA-2470__fix_asset_locking_race_condition__2.2.0.patch'
        ],
    ],
    'magento/magento2-ee-base' => [
        'Fix asset locker race condition when using Redis EE' => [
            '2.2.0 - 2.2.5' => 'MDVA-2470__fix_asset_locking_race_condition__2.2.0_ee.patch'
        ],
        'Allow DB dumps done with the support module to complete' => [
            '2.2.0 - 2.2.5' => 'MAGECLOUD-2033__prevent_deadlock_during_db_dump__2.2.0.patch'
        ]
    ]
];
