<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * Tests for patch verification command on PHP 8.2.
 *
 * @group php82
 */
class VerifyPatches82Cest extends VerifyPatchesCest
{
    /**
     * @return array
     */
    protected function patchDataProvider(): array
    {
        return [
            [
                'templateVersion' => '2.4.6',
                'magentoVersion' => '2.4.6-p13',
                'variables' => [
                    'ADMIN_EMAIL' => 'admin@example.com',
                ],
            ],
            [
                'templateVersion' => '2.4.7',
                'magentoVersion' => '2.4.7-p8',
                'variables' => [
                    'ADMIN_EMAIL' => 'admin@example.com',
                ],
            ],
        ];
    }
}
