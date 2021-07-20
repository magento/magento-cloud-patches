<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Pool;

use Magento\CloudPatches\Patch\Collector\CloudCollector;
use Magento\CloudPatches\Patch\Data\Patch;
use Magento\CloudPatches\Patch\Pool\RequiredPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class RequiredPoolTest extends TestCase
{
    /**
     * Tests retrieving patches.
     */
    public function testGetList()
    {
        $patch1 = $this->createPatch('MCLOUD-1');
        $patch2 = $this->createPatch('MCLOUD-2');
        $patch3 = $this->createPatch('MCLOUD-3');

        /** @var CloudCollector|MockObject $cloudCollector */
        $cloudCollector = $this->createMock(CloudCollector::class);
        $cloudCollector->expects($this->once())
            ->method('collect')
            ->willReturn([$patch1, $patch2, $patch3]);

        $pool = new RequiredPool([$cloudCollector]);

        $this->assertEquals([$patch1, $patch2, $patch3], $pool->getList());
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     * @return Patch|MockObject
     */
    private function createPatch(string $id)
    {
        $patch = $this->createMock(Patch::class);
        $patch->method('getId')->willReturn($id);

        return $patch;
    }
}
