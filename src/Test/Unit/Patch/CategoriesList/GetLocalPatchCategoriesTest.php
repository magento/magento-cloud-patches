<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\CategoriesList;

use Magento\CloudPatches\Filesystem\FileList;
use Magento\CloudPatches\Filesystem\JsonConfigReader;
use Magento\CloudPatches\Patch\CategoriesList\GetLocalPatchCategories;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class GetLocalPatchCategoriesTest extends TestCase
{
    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var JsonConfigReader|MockObject
     */
    private $jsonConfigReaderMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->fileListMock = $this->createMock(FileList::class);
        $this->jsonConfigReaderMock = $this->createMock(JsonConfigReader::class);
    }

    /**
     * Tests that execute returns local categories.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteReturnsLocalCategories(): void
    {
        $categories = ['Custom', 'Hotfix', 'Bugfix'];
        $configPath = '/path/to/local/categories.json';

        $this->fileListMock->expects($this->once())
            ->method('getCategoriesConfig')
            ->willReturn($configPath);

        $this->jsonConfigReaderMock->expects($this->once())
            ->method('read')
            ->with($configPath)
            ->willReturn($categories);

        $getLocalPatchCategories = new GetLocalPatchCategories(
            $this->fileListMock,
            $this->jsonConfigReaderMock
        );

        $this->assertEquals($categories, $getLocalPatchCategories->execute());
    }

    /**
     * Tests that execute returns empty array when config file is empty.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteReturnsEmptyArrayWhenConfigIsEmpty(): void
    {
        $configPath = '/path/to/local/categories.json';

        $this->fileListMock->expects($this->once())
            ->method('getCategoriesConfig')
            ->willReturn($configPath);

        $this->jsonConfigReaderMock->expects($this->once())
            ->method('read')
            ->with($configPath)
            ->willReturn([]);

        $getLocalPatchCategories = new GetLocalPatchCategories(
            $this->fileListMock,
            $this->jsonConfigReaderMock
        );

        $this->assertEquals([], $getLocalPatchCategories->execute());
    }

    /**
     * Tests that execute returns categories with multiple items.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteReturnsMultipleCategories(): void
    {
        $categories = [
            'Performance',
            'Security',
            'Functionality',
            'UI/UX',
            'Integration',
            'Other'
        ];
        $configPath = '/path/to/local/categories.json';

        $this->fileListMock->expects($this->once())
            ->method('getCategoriesConfig')
            ->willReturn($configPath);

        $this->jsonConfigReaderMock->expects($this->once())
            ->method('read')
            ->with($configPath)
            ->willReturn($categories);

        $getLocalPatchCategories = new GetLocalPatchCategories(
            $this->fileListMock,
            $this->jsonConfigReaderMock
        );

        $result = $getLocalPatchCategories->execute();

        $this->assertCount(6, $result);
        $this->assertEquals($categories, $result);
    }
}
