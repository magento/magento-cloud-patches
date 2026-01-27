<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Composer;

use Magento\CloudPatches\Composer\QualityPackage;
use Magento\QualityPatches\Info;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class QualityPackageTest extends TestCase
{
    /**
     * Tests that getPatchesDirectoryPath returns null when QualityPatches Info class is not available.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetPatchesDirectoryPathReturnsNullWhenInfoClassMissing(): void
    {
        if (class_exists(Info::class)) {
            $this->markTestSkipped('Test requires Magento\QualityPatches\Info class to be absent');
        }

        $qualityPackage = new QualityPackage();
        $this->assertNull($qualityPackage->getPatchesDirectoryPath());
    }

    /**
     * Tests that getSupportPatchesConfigPath returns null when QualityPatches Info class is not available.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetSupportPatchesConfigPathReturnsNullWhenInfoClassMissing(): void
    {
        if (class_exists(Info::class)) {
            $this->markTestSkipped('Test requires Magento\QualityPatches\Info class to be absent');
        }

        $qualityPackage = new QualityPackage();
        $this->assertNull($qualityPackage->getSupportPatchesConfigPath());
    }

    /**
     * Tests that getCommunityPatchesConfigPath returns null when QualityPatches Info class is not available.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetCommunityPatchesConfigPathReturnsNullWhenInfoClassMissing(): void
    {
        if (class_exists(Info::class)) {
            $this->markTestSkipped('Test requires Magento\QualityPatches\Info class to be absent');
        }

        $qualityPackage = new QualityPackage();
        $this->assertNull($qualityPackage->getCommunityPatchesConfigPath());
    }

    /**
     * Tests that getCategoriesConfigPath returns null when QualityPatches Info class is not available.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetCategoriesConfigPathReturnsNullWhenInfoClassMissing(): void
    {
        if (class_exists(Info::class)) {
            $this->markTestSkipped('Test requires Magento\QualityPatches\Info class to be absent');
        }

        $qualityPackage = new QualityPackage();
        $this->assertNull($qualityPackage->getCategoriesConfigPath());
    }

    /**
     * Tests that getPatchesDirectoryPath returns value when QualityPatches Info class is available.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetPatchesDirectoryPathReturnsValueWhenInfoClassExists(): void
    {
        if (!class_exists(Info::class)) {
            $this->markTestSkipped('Test requires Magento\QualityPatches\Info class to be present');
        }

        $qualityPackage = new QualityPackage();
        $result = $qualityPackage->getPatchesDirectoryPath();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Tests that getSupportPatchesConfigPath returns value when QualityPatches Info class is available.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetSupportPatchesConfigPathReturnsValueWhenInfoClassExists(): void
    {
        if (!class_exists(Info::class)) {
            $this->markTestSkipped('Test requires Magento\QualityPatches\Info class to be present');
        }

        $qualityPackage = new QualityPackage();
        $result = $qualityPackage->getSupportPatchesConfigPath();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Tests that getCommunityPatchesConfigPath returns value when QualityPatches Info class is available.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetCommunityPatchesConfigPathReturnsValueWhenInfoClassExists(): void
    {
        if (!class_exists(Info::class)) {
            $this->markTestSkipped('Test requires Magento\QualityPatches\Info class to be present');
        }

        $qualityPackage = new QualityPackage();
        $result = $qualityPackage->getCommunityPatchesConfigPath();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Tests that getCategoriesConfigPath returns value when QualityPatches Info class is available.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetCategoriesConfigPathReturnsValueWhenInfoClassExists(): void
    {
        if (!class_exists(Info::class)) {
            $this->markTestSkipped('Test requires Magento\QualityPatches\Info class to be present');
        }

        $qualityPackage = new QualityPackage();
        $result = $qualityPackage->getCategoriesConfigPath();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}
