<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Patch\Environment;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class EnvironmentTest extends TestCase
{
    /**
     * Tests environment.
     */
    public function testIsCloud()
    {
        $environment = new Environment();

        $_ENV[Environment::ENV_VAR_CLOUD]  = '';
        $this->assertFalse($environment->isCloud());

        $_ENV[Environment::ENV_VAR_CLOUD]  = '123';
        $this->assertTrue($environment->isCloud());
    }
}
