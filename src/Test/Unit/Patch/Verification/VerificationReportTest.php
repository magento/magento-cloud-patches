<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Verification;

use Magento\CloudPatches\Patch\Verification\VerificationReport;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class VerificationReportTest extends TestCase
{
    /**
     * Tests report with successful verification.
     *
     * @return void
     */
    public function testReportWithAllPatchesApplied(): void
    {
        $expectedPatches = [
            'PATCH-001' => [
                'id' => 'PATCH-001', 'title' => 'Test 1', 'type' => 'Optional', 'origin' => 'Cloud', 'categories' => []
            ],
            'PATCH-002' => [
                'id' => 'PATCH-002', 'title' => 'Test 2', 'type' => 'Optional', 'origin' => 'Cloud', 'categories' => []
            ],
        ];
        $appliedPatches = $expectedPatches;
        $missingPatches = [];
        $unexpectedPatches = [];

        $report = new VerificationReport(
            $expectedPatches,
            $appliedPatches,
            $missingPatches,
            $unexpectedPatches,
            100.0,
            2,
            2,
            true
        );

        $this->assertTrue($report->isPassing());
        $this->assertEquals(100.0, $report->getCompliancePercentage());
        $this->assertEquals(2, $report->getTotalExpected());
        $this->assertEquals(2, $report->getTotalApplied());
        $this->assertEmpty($report->getMissingPatches());
        $this->assertEmpty($report->getUnexpectedPatches());
        $this->assertStringContainsString('PASSED', $report->getSummary());
        $this->assertStringContainsString('100.00%', $report->getSummary());
    }

    /**
     * Tests report with missing patches.
     *
     * @return void
     */
    public function testReportWithMissingPatches(): void
    {
        $expectedPatches = [
            'PATCH-001' => [
                'id' => 'PATCH-001', 'title' => 'Test 1', 'type' => 'Optional', 'origin' => 'Cloud', 'categories' => []
            ],
            'PATCH-002' => [
                'id' => 'PATCH-002', 'title' => 'Test 2', 'type' => 'Optional', 'origin' => 'Cloud', 'categories' => []
            ],
        ];
        $appliedPatches = [
            'PATCH-001' => [
                'id' => 'PATCH-001', 'title' => 'Test 1', 'type' => 'Optional', 'origin' => 'Cloud', 'categories' => []
            ],
        ];
        $missingPatches = [
            'PATCH-002' => [
                'id' => 'PATCH-002', 'title' => 'Test 2', 'type' => 'Optional', 'origin' => 'Cloud', 'categories' => []
            ],
        ];
        $unexpectedPatches = [];

        $report = new VerificationReport(
            $expectedPatches,
            $appliedPatches,
            $missingPatches,
            $unexpectedPatches,
            50.0,
            2,
            1,
            false
        );

        $this->assertFalse($report->isPassing());
        $this->assertEquals(50.0, $report->getCompliancePercentage());
        $this->assertEquals(2, $report->getTotalExpected());
        $this->assertEquals(1, $report->getTotalApplied());
        $this->assertCount(1, $report->getMissingPatches());
        $this->assertArrayHasKey('PATCH-002', $report->getMissingPatches());
        $this->assertStringContainsString('FAILED', $report->getSummary());
        $this->assertStringContainsString('50.00%', $report->getSummary());
    }

    /**
     * Tests toArray conversion.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $expectedPatches = [
            'PATCH-001' => [
                'id' => 'PATCH-001', 'title' => 'Test 1', 'type' => 'Optional', 'origin' => 'Cloud', 'categories' => []
            ],
        ];
        $appliedPatches = $expectedPatches;
        $missingPatches = [];
        $unexpectedPatches = [];

        $report = new VerificationReport(
            $expectedPatches,
            $appliedPatches,
            $missingPatches,
            $unexpectedPatches,
            100.0,
            1,
            1,
            true
        );

        $array = $report->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('compliance_percentage', $array);
        $this->assertArrayHasKey('summary', $array);
        $this->assertArrayHasKey('expected_patches', $array);
        $this->assertArrayHasKey('applied_patches', $array);
        $this->assertArrayHasKey('missing_patches', $array);
        $this->assertArrayHasKey('unexpected_patches', $array);
        $this->assertEquals('PASS', $array['status']);
        $this->assertEquals(100.0, $array['compliance_percentage']);
        $this->assertEquals(1, $array['summary']['total_expected']);
        $this->assertEquals(1, $array['summary']['total_applied']);
        $this->assertEquals(0, $array['summary']['missing_count']);
        $this->assertEquals(0, $array['summary']['unexpected_count']);
    }

    /**
     * Tests report with unexpected patches.
     *
     * @return void
     */
    public function testReportWithUnexpectedPatches(): void
    {
        $expectedPatches = [
            'PATCH-001' => [
                'id' => 'PATCH-001', 'title' => 'Test 1', 'type' => 'Optional', 'origin' => 'Cloud', 'categories' => []
            ],
        ];
        $appliedPatches = $expectedPatches;
        $missingPatches = [];
        $unexpectedPatches = [
            'UNKNOWN' => [
                'id' => 'UNKNOWN', 'title' => 'Unknown', 'type' => 'unknown', 'origin' => 'unknown', 'categories' => []
            ],
        ];

        $report = new VerificationReport(
            $expectedPatches,
            $appliedPatches,
            $missingPatches,
            $unexpectedPatches,
            100.0,
            1,
            1,
            true
        );

        $this->assertTrue($report->isPassing());
        $this->assertCount(1, $report->getUnexpectedPatches());
        $this->assertArrayHasKey('UNKNOWN', $report->getUnexpectedPatches());
    }
}
