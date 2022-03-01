<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Patch\FilterFactory;
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
     */
    public function testCreateApplyFilter(array $inputArgument, $expectedValue)
    {
        $this->assertEquals(
            $expectedValue,
            $this->filterFactory->createApplyFilter($inputArgument)
        );
    }

    /**
     * @return array
     */
    public function createApplyFilterDataProvider(): array
    {
        return [
            ['inputArgument' => [], 'expectedValue' => null],
            ['inputArgument' => ['*'], 'expectedValue' => []],
            ['inputArgument' => ['*', 'MC-1'], 'expectedValue' => []],
            ['inputArgument' => ['MC-1', 'MC-2'], 'expectedValue' => ['MC-1', 'MC-2']],
        ];
    }

    /**
     * Tests creating 'apply' filter.
     *
     * @param array $inputArgument
     * @param bool $optAll
     * @param array|null $expectedValue
     * @dataProvider createRevertFilterDataProvider
     */
    public function testCreateRevertFilter(array $inputArgument, bool $optAll, $expectedValue)
    {
        $this->assertEquals(
            $expectedValue,
            $this->filterFactory->createRevertFilter($optAll, $inputArgument)
        );
    }

    /**
     * @return array
     */
    public function createRevertFilterDataProvider(): array
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
