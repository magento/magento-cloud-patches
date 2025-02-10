<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Patch\AggregatedPatchFactory;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Data\Patch;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class AggregatorTest extends TestCase
{
    /**
     * @var Aggregator
     */
    private $aggregator;

    /**
     * @var AggregatedPatchFactory|MockObject
     */
    private $aggregatedPatchFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->aggregatedPatchFactory = $this->createMock(AggregatedPatchFactory::class);
        $this->aggregator = new Aggregator($this->aggregatedPatchFactory);
    }

  /**
   * Tests patch aggregation.
   */
    public function testAggregate()
    {
        $patch1CE = $this->createPatch('MC-1', 'Patch1 CE');
        $patch1EE = $this->createPatch('MC-1', 'Patch1 EE');
        $patch1B2B = $this->createPatch('MC-1', 'Patch1 B2B');
        $patch2CE = $this->createPatch('MC-2', 'Patch2 CE');
        $patch2EE = $this->createPatch('MC-2', 'Patch2 EE');
        $patch3 = $this->createPatch('MC-3', 'Patch3');

        // Mock AggregatedPatchInterface to return the patches when getPatches is called
        $aggregatedPatchMock1 = $this->createMock(AggregatedPatchInterface::class);
        $aggregatedPatchMock1->method('getRequire')->willReturn([$patch1CE, $patch1EE, $patch1B2B]);

        $aggregatedPatchMock2 = $this->createMock(AggregatedPatchInterface::class);
        $aggregatedPatchMock2->method('getRequire')->willReturn([$patch2CE, $patch2EE]);

        $aggregatedPatchMock3 = $this->createMock(AggregatedPatchInterface::class);
        $aggregatedPatchMock3->method('getRequire')->willReturn([$patch3]);

        // Setting up the factory mock to return AggregatedPatchInterface mocks
        $this->aggregatedPatchFactory->expects($this->exactly(3))
        ->method('create')
        ->willReturnOnConsecutiveCalls(
            $aggregatedPatchMock1,  // First call returns this AggregatedPatchInterface mock
            $aggregatedPatchMock2,  // Second call returns this AggregatedPatchInterface mock
            $aggregatedPatchMock3   // Third call returns this AggregatedPatchInterface mock
        );

        $result = $this->aggregator->aggregate(
            [$patch1CE, $patch1EE, $patch1B2B, $patch2CE, $patch2EE, $patch3]
        );

        $this->assertTrue(is_array($result));
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     * @param string $title
     * @return Patch|MockObject
     */
    private function createPatch(string $id, string $title)
    {
        $patch = $this->createMock(Patch::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('getTitle')->willReturn($title);
        $patch->method('__toString')->willReturn(microtime());

        return $patch;
    }
}
