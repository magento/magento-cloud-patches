<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\CategoriesList;

use Magento\CloudPatches\Composer\QualityPackage;
use Magento\CloudPatches\Filesystem\JsonConfigReader;
use Magento\CloudPatches\Patch\CategoriesList\GetSupportPatchCategories;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class GetSupportPatchCategoriesTest extends TestCase
{
    /**
     * @var QualityPackage|MockObject
     */
    private $qualityPackageMock;

    /**
     * @var JsonConfigReader|MockObject
     */
    private $jsonConfigReaderMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->qualityPackageMock = $this->createMock(QualityPackage::class);
        $this->jsonConfigReaderMock = $this->createMock(JsonConfigReader::class);
    }

    /**
     * Tests that execute returns categories when config path is available.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteReturnsCategories(): void
    {
        $categories = ['Performance', 'Security', 'Other'];
        $configPath = '/path/to/categories.json';

        // getCategoriesConfigPath is called twice: once for null check, once for read()
        $this->qualityPackageMock->expects($this->exactly(2))
            ->method('getCategoriesConfigPath')
            ->willReturn($configPath);

        $this->jsonConfigReaderMock->expects($this->once())
            ->method('read')
            ->with($configPath)
            ->willReturn($categories);

        $getSupportPatchCategories = new GetSupportPatchCategories(
            $this->qualityPackageMock,
            $this->jsonConfigReaderMock
        );

        $this->assertEquals($categories, $getSupportPatchCategories->execute());
    }

    /**
     * Tests that execute returns empty array when config path is null.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteReturnsEmptyArrayWhenConfigPathIsNull(): void
    {
        $this->qualityPackageMock->expects($this->once())
            ->method('getCategoriesConfigPath')
            ->willReturn(null);

        $this->jsonConfigReaderMock->expects($this->never())
            ->method('read');

        $getSupportPatchCategories = new GetSupportPatchCategories(
            $this->qualityPackageMock,
            $this->jsonConfigReaderMock
        );

        $this->assertEquals([], $getSupportPatchCategories->execute());
    }

    /**
     * Tests that execute returns empty array when config file contains empty array.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteReturnsEmptyArrayWhenConfigFileIsEmpty(): void
    {
        $configPath = '/path/to/categories.json';

        // getCategoriesConfigPath is called twice: once for null check, once for read()
        $this->qualityPackageMock->expects($this->exactly(2))
            ->method('getCategoriesConfigPath')
            ->willReturn($configPath);

        $this->jsonConfigReaderMock->expects($this->once())
            ->method('read')
            ->with($configPath)
            ->willReturn([]);

        $getSupportPatchCategories = new GetSupportPatchCategories(
            $this->qualityPackageMock,
            $this->jsonConfigReaderMock
        );

        $this->assertEquals([], $getSupportPatchCategories->execute());
    }

    /**
     * Tests that execute returns categories with special characters.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteReturnsCategoriesWithSpecialCharacters(): void
    {
        $categories = ['Performance & Speed', 'Security/Auth', 'UI/UX Improvements'];
        $configPath = '/path/to/categories.json';

        // getCategoriesConfigPath is called twice: once for null check, once for read()
        $this->qualityPackageMock->expects($this->exactly(2))
            ->method('getCategoriesConfigPath')
            ->willReturn($configPath);

        $this->jsonConfigReaderMock->expects($this->once())
            ->method('read')
            ->with($configPath)
            ->willReturn($categories);

        $getSupportPatchCategories = new GetSupportPatchCategories(
            $this->qualityPackageMock,
            $this->jsonConfigReaderMock
        );

        $this->assertEquals($categories, $getSupportPatchCategories->execute());
    }
}
