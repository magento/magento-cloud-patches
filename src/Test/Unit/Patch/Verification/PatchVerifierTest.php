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
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Magento\CloudPatches\Patch\Verification\PatchVerifier;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class PatchVerifierTest extends TestCase
{
    /**
     * @var Aggregator&Stub
     */
    private $aggregatorStub;

    /**
     * @var OptionalPool&Stub
     */
    private $optionalPoolStub;

    /**
     * @var LocalPool&Stub
     */
    private $localPoolStub;

    /**
     * @var StatusPool&Stub
     */
    private $statusPoolStub;

    /**
     * @var CloudCollector&Stub
     */
    private $cloudCollectorStub;

    /**
     * @var PatchVerifier
     */
    private $patchVerifier;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->aggregatorStub = $this->createStub(Aggregator::class);
        $this->optionalPoolStub = $this->createStub(OptionalPool::class);
        $this->localPoolStub = $this->createStub(LocalPool::class);
        $this->statusPoolStub = $this->createStub(StatusPool::class);
        $this->cloudCollectorStub = $this->createStub(CloudCollector::class);

        $this->patchVerifier = new PatchVerifier(
            $this->aggregatorStub,
            $this->optionalPoolStub,
            $this->localPoolStub,
            $this->statusPoolStub,
            $this->cloudCollectorStub
        );
    }

    /**
     * Tests successful verification when all patches are applied.
     */
    public function testVerifyAllPatchesApplied(): void
    {
        $patch1 = $this->createPatchStub('PATCH-001', 'Test Patch 1', 'Optional', 'Cloud');
        $patch2 = $this->createPatchStub('PATCH-002', 'Test Patch 2', 'Optional', 'Cloud');

        $this->optionalPoolStub->method('getList')->willReturn([]);
        $this->localPoolStub->method('getList')->willReturn([]);
        $this->aggregatorStub->method('aggregate')->willReturn([$patch1, $patch2]);

        $this->statusPoolStub->method('get')
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
        $patch1 = $this->createPatchStub('PATCH-001', 'Test Patch 1', 'Optional', 'Cloud');
        $patch2 = $this->createPatchStub('PATCH-002', 'Test Patch 2', 'Optional', 'Cloud');
        $patch3 = $this->createPatchStub('PATCH-003', 'Test Patch 3', 'Optional', 'Cloud');

        $this->optionalPoolStub->method('getList')->willReturn([]);
        $this->localPoolStub->method('getList')->willReturn([]);
        $this->aggregatorStub->method('aggregate')->willReturn([$patch1, $patch2, $patch3]);

        $this->statusPoolStub->method('get')
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
        $patch1 = $this->createPatchStub('PATCH-001', 'Test Patch 1', 'Optional', 'Cloud');
        $patch2 = $this->createPatchStub('PATCH-002', 'Test Patch 2', 'Optional', 'Cloud');

        $this->optionalPoolStub->method('getList')->willReturn([]);
        $this->localPoolStub->method('getList')->willReturn([]);
        $this->aggregatorStub->method('aggregate')->willReturn([$patch1, $patch2]);

        $this->statusPoolStub->method('get')
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
        $patch1 = $this->createPatchStub('PATCH-001', 'Test Patch 1', 'Optional', 'Cloud');
        $patch2 = $this->createPatchStub('PATCH-002', 'Test Patch 2', 'Optional', 'Cloud');
        $patch3 = $this->createPatchStub('PATCH-003', 'Test Patch 3', 'Optional', 'Cloud');

        $this->optionalPoolStub->method('getList')->willReturn([]);
        $this->localPoolStub->method('getList')->willReturn([]);
        $this->aggregatorStub->method('aggregate')->willReturn([$patch1, $patch2, $patch3]);

        $this->statusPoolStub->method('get')
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
        $patch1 = $this->createPatchStub('PATCH-001', 'Test Patch 1', 'Optional', 'Cloud');

        $this->optionalPoolStub->method('getList')->willReturn([]);
        $this->localPoolStub->method('getList')->willReturn([]);
        $this->aggregatorStub->method('aggregate')->willReturn([$patch1]);

        $this->statusPoolStub->method('get')
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
        $this->optionalPoolStub->method('getList')->willReturn([]);
        $this->localPoolStub->method('getList')->willReturn([]);
        $this->aggregatorStub->method('aggregate')->willReturn([]);

        $report = $this->patchVerifier->verify();

        $this->assertFalse($report->isPassing());
        $this->assertEquals(0, $report->getTotalExpected());
        $this->assertEquals(0, $report->getTotalApplied());
        $this->assertEquals(100.0, $report->getCompliancePercentage());
    }

    /**
     * Creates a stub aggregated patch.
     *
     * @param string $id
     * @param string $title
     * @param string $type
     * @param string $origin
     * @return AggregatedPatch&Stub
     */
    private function createPatchStub(string $id, string $title, string $type, string $origin)
    {
        $patch = $this->createStub(AggregatedPatch::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('getTitle')->willReturn($title);
        $patch->method('getType')->willReturn($type);
        $patch->method('getOrigin')->willReturn($origin);
        $patch->method('getCategories')->willReturn(['General']);

        return $patch;
    }
}
