<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Status;

use Magento\CloudPatches\Patch\Status\ResolverInterface;
use Magento\CloudPatches\Patch\Status\StatusPool;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class StatusPoolTest extends TestCase
{
    /**
     * Tests retrieving patch statuses.
     */
    public function testStatusGet()
    {
        $result1 = ['MC-1' => StatusPool::APPLIED, 'MC-2' => StatusPool::NOT_APPLIED];
        $resolver1 = $this->getMockForAbstractClass(ResolverInterface::class);
        $resolver1->method('resolve')->willReturn($result1);

        $result2 = ['MC-3' => StatusPool::APPLIED, 'MC-4' => StatusPool::NA];
        $resolver2 = $this->getMockForAbstractClass(ResolverInterface::class);
        $resolver2->method('resolve')->willReturn($result2);

        $statusPool = new StatusPool([$resolver1, $resolver2]);

        $this->assertEquals(StatusPool::APPLIED, $statusPool->get('MC-1'));
        $this->assertEquals(StatusPool::NOT_APPLIED, $statusPool->get('MC-2'));
        $this->assertEquals(StatusPool::APPLIED, $statusPool->get('MC-3'));
        $this->assertEquals(StatusPool::NA, $statusPool->get('MC-4'));
        $this->assertEquals(StatusPool::NA, $statusPool->get('NotExistingId'));
        $this->assertEquals(true, $statusPool->isNotApplied('MC-2'));
        $this->assertEquals(true, $statusPool->isApplied('MC-3'));
        $this->assertEquals(false, $statusPool->isNotApplied('MC-3'));
    }

    /**
     * Tests a case when exception happens after an instantiating status pool with the wrong resolver.
     */
    public function testResolveWithException()
    {
        $invalidResolver = new \stdClass();

        $this->expectException(\InvalidArgumentException::class);
        new StatusPool([$invalidResolver]);
    }
}
