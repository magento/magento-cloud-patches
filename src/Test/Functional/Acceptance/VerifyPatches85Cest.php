<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * Tests for patch verification command on PHP 8.5.
 *
 * @group php85
 */
class VerifyPatches85Cest extends VerifyPatchesCest
{
    /**
     * @return array
     */
    protected function patchDataProvider(): array
    {
        return [
            [
                'templateVersion' => '2.4.9',
                'magentoVersion' => '2.4.9',
                'variables' => [
                    'ADMIN_EMAIL' => 'admin@example.com',
                ],
            ],
        ];
    }
}
