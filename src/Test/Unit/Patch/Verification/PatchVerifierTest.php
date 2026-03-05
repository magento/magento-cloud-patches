<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Verification;

use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Collector\CloudCollector;
use Magento\CloudPatches\Patch\Data\AggregatedPatch;
use Magento\CloudPatches\Patch\Data\Patch;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Magento\CloudPatches\Patch\Verification\PatchVerifier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class PatchVerifierTest extends TestCase
{
    /**
     * @var Aggregator|MockObject
     */
    private $aggregatorMock;

    /**
     * @var OptionalPool|MockObject
     */
    private $optionalPoolMock;

    /**
     * @var LocalPool|MockObject
     */
    private $localPoolMock;

    /**
     * @var StatusPool|MockObject
     */
    private $statusPoolMock;

    /**
     * @var CloudCollector|MockObject
     */
    private $cloudCollectorMock;

    /**
     * @var PatchVerifier
     */
    private $patchVerifier;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->aggregatorMock = $this->createMock(Aggregator::class);
        $this->optionalPoolMock = $this->createMock(OptionalPool::class);
        $this->localPoolMock = $this->createMock(LocalPool::class);
        $this->statusPoolMock = $this->createMock(StatusPool::class);
        $this->cloudCollectorMock = $this->createMock(CloudCollector::class);

        $this->patchVerifier = new PatchVerifier(
            $this->aggregatorMock,
            $this->optionalPoolMock,
            $this->localPoolMock,
            $this->statusPoolMock,
            $this->cloudCollectorMock
        );
    }

    /**
     * Tests successful verification when all patches are applied.
     */
    public function testVerifyAllPatchesApplied(): void
    {
        $patch1 = $this->createPatchMock('PATCH-001', 'Test Patch 1', 'Optional', 'Cloud');
        $patch2 = $this->createPatchMock('PATCH-002', 'Test Patch 2', 'Optional', 'Cloud');

        $this->optionalPoolMock->method('getList')->willReturn([]);
        $this->localPoolMock->method('getList')->willReturn([]);
        $this->aggregatorMock->method('aggregate')->willReturn([$patch1, $patch2]);

        $this->statusPoolMock->method('get')
            ->willReturnMap([
                ['PATCH-001', StatusPool::APPLIED],
                ['PATCH-002', StatusPool::APPLIED],
            ]);

        $report = $this->patchVerifier->verify();

        $this->assertTrue($report->isPassing());
        $this->assertEquals(2, $report->getTotalExpected());
        $this->assertEquals(2, $report->getTotalApplied());
        $this->assertEquals(0, count($report->getMissingPatches()));
        $this->assertEquals(100.0, $report->getCompliancePercentage());
    }

    /**
     * Tests verification when some patches are missing.
     */
    public function testVerifyWithMissingPatches(): void
    {
        $patch1 = $this->createPatchMock('PATCH-001', 'Test Patch 1', 'Optional', 'Cloud');
        $patch2 = $this->createPatchMock('PATCH-002', 'Test Patch 2', 'Optional', 'Cloud');
        $patch3 = $this->createPatchMock('PATCH-003', 'Test Patch 3', 'Optional', 'Cloud');

        $this->optionalPoolMock->method('getList')->willReturn([]);
        $this->localPoolMock->method('getList')->willReturn([]);
        $this->aggregatorMock->method('aggregate')->willReturn([$patch1, $patch2, $patch3]);

        $this->statusPoolMock->method('get')
            ->willReturnMap([
                ['PATCH-001', StatusPool::APPLIED],
                ['PATCH-002', StatusPool::NOT_APPLIED],
                ['PATCH-003', StatusPool::APPLIED],
            ]);

        $report = $this->patchVerifier->verify();

        $this->assertFalse($report->isPassing());
        $this->assertEquals(3, $report->getTotalExpected());
        $this->assertEquals(2, $report->getTotalApplied());
        $this->assertEquals(1, count($report->getMissingPatches()));
        $this->assertArrayHasKey('PATCH-002', $report->getMissingPatches());
        $this->assertEquals(66.67, round($report->getCompliancePercentage(), 2));
    }

    /**
     * Tests verification with N/A patches (excluded from verification).
     *
     * @return void
     */
    public function testVerifyExcludesNAPatches(): void
    {
        $patch1 = $this->createPatchMock('PATCH-001', 'Test Patch 1', 'Optional', 'Cloud');
        $patch2 = $this->createPatchMock('PATCH-002', 'Test Patch 2', 'Optional', 'Cloud');

        $this->optionalPoolMock->method('getList')->willReturn([]);
        $this->localPoolMock->method('getList')->willReturn([]);
        $this->aggregatorMock->method('aggregate')->willReturn([$patch1, $patch2]);

        $this->statusPoolMock->method('get')
            ->willReturnMap([
                ['PATCH-001', StatusPool::APPLIED],
                ['PATCH-002', StatusPool::NA],
            ]);

        $report = $this->patchVerifier->verify();

        $this->assertTrue($report->isPassing());
        $this->assertEquals(1, $report->getTotalExpected());
        $this->assertEquals(1, $report->getTotalApplied());
        $this->assertEquals(0, count($report->getMissingPatches()));
    }

    /**
     * Tests verification of specific patch IDs.
     *
     * @return void
     */
    public function testVerifySpecificPatches(): void
    {
        $patch1 = $this->createPatchMock('PATCH-001', 'Test Patch 1', 'Optional', 'Cloud');
        $patch2 = $this->createPatchMock('PATCH-002', 'Test Patch 2', 'Optional', 'Cloud');
        $patch3 = $this->createPatchMock('PATCH-003', 'Test Patch 3', 'Optional', 'Cloud');

        $this->optionalPoolMock->method('getList')->willReturn([]);
        $this->localPoolMock->method('getList')->willReturn([]);
        $this->aggregatorMock->method('aggregate')->willReturn([$patch1, $patch2, $patch3]);

        $this->statusPoolMock->method('get')
            ->willReturnMap([
                ['PATCH-001', StatusPool::APPLIED],
                ['PATCH-002', StatusPool::NOT_APPLIED],
            ]);

        $report = $this->patchVerifier->verifySpecific(['PATCH-001', 'PATCH-002']);

        $this->assertFalse($report->isPassing());
        $this->assertEquals(2, $report->getTotalExpected());
        $this->assertEquals(1, $report->getTotalApplied());
        $this->assertEquals(1, count($report->getMissingPatches()));
        $this->assertArrayHasKey('PATCH-002', $report->getMissingPatches());
    }

    /**
     * Tests verification with unknown patch IDs.
     *
     * @return void
     */
    public function testVerifySpecificWithUnknownPatch(): void
    {
        $patch1 = $this->createPatchMock('PATCH-001', 'Test Patch 1', 'Optional', 'Cloud');

        $this->optionalPoolMock->method('getList')->willReturn([]);
        $this->localPoolMock->method('getList')->willReturn([]);
        $this->aggregatorMock->method('aggregate')->willReturn([$patch1]);

        $this->statusPoolMock->method('get')
            ->willReturnMap([
                ['PATCH-001', StatusPool::APPLIED],
            ]);

        $report = $this->patchVerifier->verifySpecific(['PATCH-001', 'UNKNOWN-PATCH']);

        $this->assertFalse($report->isPassing());
        $this->assertEquals(1, $report->getTotalExpected());
        $this->assertEquals(1, $report->getTotalApplied());
        $this->assertEquals(1, count($report->getMissingPatches()));
        $this->assertArrayHasKey('UNKNOWN-PATCH', $report->getMissingPatches());
    }

    /**
     * Tests verification with empty patch list.
     *
     * @return void
     */
    public function testVerifyWithNoPatchesExpected(): void
    {
        $this->optionalPoolMock->method('getList')->willReturn([]);
        $this->localPoolMock->method('getList')->willReturn([]);
        $this->aggregatorMock->method('aggregate')->willReturn([]);

        $report = $this->patchVerifier->verify();

        $this->assertFalse($report->isPassing());
        $this->assertEquals(0, $report->getTotalExpected());
        $this->assertEquals(0, $report->getTotalApplied());
        $this->assertEquals(100.0, $report->getCompliancePercentage());
    }

    /**
     * Creates a mock aggregated patch.
     *
     * @param string $id
     * @param string $title
     * @param string $type
     * @param string $origin
     * @return AggregatedPatch|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createPatchMock(string $id, string $title, string $type, string $origin)
    {
        $patch = $this->createMock(AggregatedPatch::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('getTitle')->willReturn($title);
        $patch->method('getType')->willReturn($type);
        $patch->method('getOrigin')->willReturn($origin);
        $patch->method('getCategories')->willReturn(['General']);

        return $patch;
    }
}
