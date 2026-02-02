<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * Tests for patch verification command on PHP 8.3.
 *
 * @group php83
 */
class VerifyPatches83Cest extends VerifyPatchesCest
{
    /**
     * @return array
     */
    protected function patchDataProvider(): array
    {
        return [
            [
                'variables' => [
                    'ADMIN_EMAIL' => 'admin@example.com',
                ],
            ],
        ];
    }
}
