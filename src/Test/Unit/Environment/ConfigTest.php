<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Environment;

use Magento\CloudPatches\Environment\Config;
use Magento\CloudPatches\Environment\ConfigReader;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritDoc
 */
class ConfigTest extends TestCase
{
    /**
     * @var ConfigReader|MockObject
     */
    private $configReader;

    /**
     * @var Config
     */
    private $config;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->configReader = $this->createMock(ConfigReader::class);

        $this->config = new Config($this->configReader);
    }

    /**
     * Tests Cloud environment.
     */
    public function testIsCloud()
    {
        $_ENV[Config::ENV_VAR_CLOUD]  = '';
        $this->assertFalse($this->config->isCloud());

        $_ENV[Config::ENV_VAR_CLOUD]  = '123';
        $this->assertTrue($this->config->isCloud());
    }

    /**
     * Tests retrieving QUALITY_PATCHES from env variable.
     */
    public function testGetQualityPatchesEnv()
    {
        $_ENV[Config::ENV_VAR_QUALITY_PATCHES]  = ['MC-1', 'MC-2'];

        $this->configReader->expects($this->never())
            ->method('read');

        $this->assertEquals(
            ['MC-1', 'MC-2'],
            $this->config->getQualityPatches()
        );
    }

    /**
     * Tests retrieving QUALITY_PATCHES from env config.
     */
    public function testGetQualityPatchesConfig()
    {
        unset($_ENV[Config::ENV_VAR_QUALITY_PATCHES]);
        $this->assertArrayNotHasKey(Config::ENV_VAR_QUALITY_PATCHES, $_ENV);

        $config['stage']['build'][Config::ENV_VAR_QUALITY_PATCHES] = ['MC-1', 'MC-2'];
        $this->configReader->expects($this->once())
            ->method('read')
            ->willReturn($config);

        $this->assertEquals(
            ['MC-1', 'MC-2'],
            $this->config->getQualityPatches()
        );
    }
}
