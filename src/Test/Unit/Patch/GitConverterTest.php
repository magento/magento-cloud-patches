<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Patch\GitConverter;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class GitConverterTest extends TestCase
{
    /**
     * @var GitConverter
     */
    private $gitConverter;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->gitConverter = new GitConverter();
    }

    /**
     * Tests patch converting from composer-based to git-based.
     *
     * @param string $composerContent
     * @param string $expectedContent
     * @dataProvider convertDataProvider
     */
    public function testConvert(string $composerContent, string $expectedContent)
    {
        $this->assertEquals(
            $expectedContent,
            $this->gitConverter->convert($composerContent)
        );
    }

    /**
     * phpcs:disable
     * @return array
     */
    public function convertDataProvider()
    {
        return [
            [
                'composerContent' => 'diff -Naur a/vendor/magento/framework/View/Asset/File/FallbackContext.php b/vendor/magento/framework/View/Asset/File/FallbackContext.php
--- a/vendor/magento/framework/View/Asset/File/FallbackContext.php
+++ b/vendor/magento/framework/View/Asset/File/FallbackContext.php',
                'expectedContent' => 'diff -Naur a/lib/internal/Magento/Framework/View/Asset/File/FallbackContext.php b/lib/internal/Magento/Framework/View/Asset/File/FallbackContext.php
--- a/lib/internal/Magento/Framework/View/Asset/File/FallbackContext.php
+++ b/lib/internal/Magento/Framework/View/Asset/File/FallbackContext.php'
            ],

            [
                'composerContent' => 'diff -Naur a/app/etc/di.xml b/app/etc/di.xml
--- a/app/etc/di.xml
+++ b/app/etc/di.xml',
                'expectedContent' => 'diff -Naur a/app/etc/di.xml b/app/etc/di.xml
--- a/app/etc/di.xml
+++ b/app/etc/di.xml'
            ],

            [
                'composerContent' => 'diff --git a/vendor/magento/module-deploy/Process/Queue.php b/vendor/magento/module-deploy/Process/Queue.php
--- a/vendor/magento/module-deploy/Process/Queue.php
+++ b/vendor/magento/module-deploy/Process/Queue.php',
                'expectedContent' => 'diff --git a/app/code/Magento/Deploy/Process/Queue.php b/app/code/Magento/Deploy/Process/Queue.php
--- a/app/code/Magento/Deploy/Process/Queue.php
+++ b/app/code/Magento/Deploy/Process/Queue.php'
            ],

            [
                'composerContent' => 'rename from vendor/magento/module-some-module',
                'expectedContent' => 'rename from app/code/Magento/SomeModule'
            ]
        ];
    }
    /** phpcs:enable */
}
