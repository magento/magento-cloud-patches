<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * Tests for patch verification command on PHP 8.1.
 *
 * @group php81
 */
class VerifyPatches81Cest extends VerifyPatchesCest
{
    /**
     * @return array
     */
    protected function patchDataProvider(): array
    {
        return [
            [
                'templateVersion' => '2.4.4',
                'magentoVersion' => '2.4.4-p16',
                'variables' => [
                    'ADMIN_EMAIL' => 'admin@example.com',
                ],
            ],
            [
                'templateVersion' => '2.4.5',
                'magentoVersion' => '2.4.5-p15',
                'variables' => [
                    'ADMIN_EMAIL' => 'admin@example.com',
                ],
            ],
        ];
    }
}
