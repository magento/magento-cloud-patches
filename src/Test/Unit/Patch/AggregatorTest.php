<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Patch\AggregatedPatchFactory;
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
    protected function setUp()
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

        $this->aggregatedPatchFactory->expects($this->exactly(3))
            ->method('create')
            ->withConsecutive(
                [[$patch1CE, $patch1EE, $patch1B2B]],
                [[$patch2CE, $patch2EE]],
                [[$patch3]]
            );

        $this->assertTrue(
            is_array(
                $this->aggregator->aggregate(
                    [$patch1CE, $patch1EE, $patch1B2B, $patch2CE, $patch2EE, $patch3]
                )
            )
        );
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

        // To make mock object unique for assertions and array operations.
        $patch->id = microtime();
        $patch->method('__toString')->willReturn($patch->id);

        return $patch;
    }
}
