<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Patch\FilterFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class FilterFactoryTest extends TestCase
{
    /**
     * @var FilterFactory
     */
    private $filterFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->filterFactory = new FilterFactory();
    }

    /**
     * Tests creating 'apply' filter.
     *
     * @param array $inputArgument
     * @param array|null $expectedValue
     * @dataProvider createApplyFilterDataProvider
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    #[DataProvider('createApplyFilterDataProvider')]
    public function testCreateApplyFilter(array $inputArgument, $expectedValue): void
    {
        $this->assertEquals(
            $expectedValue,
            $this->filterFactory->createApplyFilter($inputArgument)
        );
    }

    /**
     * Tests apply filter creation with various input arguments.
     *
     * @return array
     */
    public static function createApplyFilterDataProvider(): array
    {
        return [
            ['inputArgument' => [], 'expectedValue' => null],
            ['inputArgument' => ['*'], 'expectedValue' => []],
            ['inputArgument' => ['*', 'MC-1'], 'expectedValue' => []],
            ['inputArgument' => ['MC-1', 'MC-2'], 'expectedValue' => ['MC-1', 'MC-2']],
        ];
    }

    /**
     * Tests creating 'revert' filter.
     *
     * @param array $inputArgument
     * @param bool $optAll
     * @param array|null $expectedValue
     * @dataProvider createRevertFilterDataProvider
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    #[DataProvider('createRevertFilterDataProvider')]
    public function testCreateRevertFilter(array $inputArgument, bool $optAll, $expectedValue): void
    {
        $this->assertEquals(
            $expectedValue,
            $this->filterFactory->createRevertFilter($optAll, $inputArgument)
        );
    }

    /**
     * Tests revert filter creation using input arguments.
     *
     * @return array
     */
    public static function createRevertFilterDataProvider(): array
    {
        return [
            ['inputArgument' => [], 'optAll' => false, 'expectedValue' => null],
            ['inputArgument' => ['*'], 'optAll' => false,  'expectedValue' => ['*']],
            ['inputArgument' => ['MC-1', 'MC-2'], 'optAll' => false, 'expectedValue' => ['MC-1', 'MC-2']],
            ['inputArgument' => [], 'optAll' => true, 'expectedValue' => []],
            ['inputArgument' => ['MC-1', 'MC-2'], 'optAll' => true, 'expectedValue' => []]
        ];
    }
}
