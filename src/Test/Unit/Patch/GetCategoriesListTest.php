<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Patch\GetCategoriesList;
use Magento\CloudPatches\Patch\GetCategoriesListInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class GetCategoriesListTest extends TestCase
{
    /**
     * Tests executing get categories list with multiple providers.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecute(): void
    {
        $provider1 = $this->createMock(GetCategoriesListInterface::class);
        $provider1->method('execute')->willReturn(['cat1', 'cat2']);

        $provider2 = $this->createMock(GetCategoriesListInterface::class);
        $provider2->method('execute')->willReturn(['cat2', 'cat3']);

        $getCategoriesList = new GetCategoriesList([$provider1, $provider2]);
        $result = $getCategoriesList->execute();

        $this->assertEquals(['cat1', 'cat2', 'cat3'], array_values($result));
    }
}
