<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Integrity\Testsuite;

use Magento\CloudPatches\Test\Integrity\Lib\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests that patches cover supported Magento versions
 */
class SupportedVersionsTest extends TestCase
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->config = new Config();
    }

    /**
     * Tests that patches.json covers currently supported Magento versions
     *
     * @return void
     */
    public function testPatchesCoverSupportedVersions(): void
    {
        $patchesConfig = $this->config->get();

        // Check for magento/magento2-base (main package)
        $this->assertArrayHasKey(
            'magento/magento2-base',
            $patchesConfig,
            'patches.json must contain patches for magento/magento2-base'
        );

        // Verify we have patches for recent versions (2.4.4+)
        $hasRecentVersions = $this->checkForRecentVersions($patchesConfig['magento/magento2-base']);

        $this->assertTrue(
            $hasRecentVersions,
            'patches.json must contain patches for Magento 2.4.4+ versions'
        );
    }

    /**
     * Tests that patches.json has patches for PHP 8.1+ supported versions
     *
     * @return void
     */
    public function testPatchesCoverPhp81PlusVersions(): void
    {
        $patchesConfig = $this->config->get();
        
        if (!isset($patchesConfig['magento/magento2-base'])) {
            $this->markTestSkipped('magento/magento2-base not found in patches.json');
        }

        // PHP 8.1 support started with Magento 2.4.4
        $hasPhp81Versions = false;
        foreach ($patchesConfig['magento/magento2-base'] as $versionConstraints) {
            foreach (array_keys($versionConstraints) as $constraint) {
                // Check if constraint includes 2.4.4+ versions
                if (preg_match('/2\.4\.[4-9]|2\.4\.\d{2}/', $constraint)) {
                    $hasPhp81Versions = true;
                    break 2;
                }
            }
        }

        $this->assertTrue(
            $hasPhp81Versions,
            'patches.json must contain patches for Magento 2.4.4+ (PHP 8.1+ compatible versions)'
        );
    }

    /**
     * Tests that patches exist for major Magento 2.4.x releases
     *
     * @param string $majorVersion
     * @dataProvider majorVersionsDataProvider
     * @return void
     */
    #[DataProvider('majorVersionsDataProvider')]
    public function testPatchesExistForMajorVersions(string $majorVersion): void
    {
        $patchesConfig = $this->config->get();
        
        if (!isset($patchesConfig['magento/magento2-base'])) {
            $this->markTestSkipped('magento/magento2-base not found in patches.json');
        }

        $hasVersion = false;
        foreach ($patchesConfig['magento/magento2-base'] as $versionConstraints) {
            foreach (array_keys($versionConstraints) as $constraint) {
                if (str_contains($constraint, $majorVersion)) {
                    $hasVersion = true;
                    break 2;
                }
            }
        }

        $this->assertTrue(
            $hasVersion,
            sprintf('patches.json should contain patches for Magento %s', $majorVersion)
        );
    }

    /**
     * Data provider for major versions
     *
     * @return array
     */
    public static function majorVersionsDataProvider(): array
    {
        return [
            '2.4.4' => ['2.4.4'],
            '2.4.5' => ['2.4.5'],
            '2.4.6' => ['2.4.6'],
            '2.4.7' => ['2.4.7'],
            '2.4.8' => ['2.4.8'],
        ];
    }

    /**
     * Check if patches cover recent Magento versions
     *
     * @param array $patches
     * @return bool
     */
    private function checkForRecentVersions(array $patches): bool
    {
        foreach ($patches as $versionConstraints) {
            foreach (array_keys($versionConstraints) as $constraint) {
                // Check if any constraint includes 2.4.4+ versions
                if (preg_match('/2\.4\.[4-9]|2\.4\.\d{2}/', $constraint)) {
                    return true;
                }
            }
        }
        return false;
    }
}
