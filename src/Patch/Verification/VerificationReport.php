<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Verification;

/**
 * Represents a patch verification report.
 */
class VerificationReport
{
    /**
     * @var array
     */
    private $expectedPatches;

    /**
     * @var array
     */
    private $appliedPatches;

    /**
     * @var array
     */
    private $missingPatches;

    /**
     * @var array
     */
    private $unexpectedPatches;

    /**
     * @var float
     */
    private $compliancePercentage;

    /**
     * @var integer
     */
    private $totalExpected;

    /**
     * @var integer
     */
    private $totalApplied;

    /**
     * @var boolean
     */
    private $isPassing;

    /**
     * VerificationReport constructor.
     *
     * @param array $expectedPatches
     * @param array $appliedPatches
     * @param array $missingPatches
     * @param array $unexpectedPatches
     * @param float $compliancePercentage
     * @param int $totalExpected
     * @param int $totalApplied
     * @param bool $isPassing
     */
    public function __construct(
        array $expectedPatches,
        array $appliedPatches,
        array $missingPatches,
        array $unexpectedPatches,
        float $compliancePercentage,
        int $totalExpected,
        int $totalApplied,
        bool $isPassing
    ) {
        $this->expectedPatches = $expectedPatches;
        $this->appliedPatches = $appliedPatches;
        $this->missingPatches = $missingPatches;
        $this->unexpectedPatches = $unexpectedPatches;
        $this->compliancePercentage = $compliancePercentage;
        $this->totalExpected = $totalExpected;
        $this->totalApplied = $totalApplied;
        $this->isPassing = $isPassing;
    }

    /**
     * Returns the list of patches that were expected based on patches.json.
     *
     * @return array
     */
    public function getExpectedPatches(): array
    {
        return $this->expectedPatches;
    }

    /**
     * Returns the list of patches that are actually applied.
     *
     * @return array
     */
    public function getAppliedPatches(): array
    {
        return $this->appliedPatches;
    }

    /**
     * Returns the list of patches that are expected but not applied.
     *
     * @return array
     */
    public function getMissingPatches(): array
    {
        return $this->missingPatches;
    }

    /**
     * Returns the list of patches that are applied but not expected.
     *
     * @return array
     */
    public function getUnexpectedPatches(): array
    {
        return $this->unexpectedPatches;
    }

    /**
     * Returns the percentage of expected patches that are actually applied (0–100).
     *
     * Compliance is calculated based on the patches defined in patches.json
     * compared with the patches detected as applied.
     *
     * @return float
     */
    public function getCompliancePercentage(): float
    {
        return $this->compliancePercentage;
    }

    /**
     * Returns the total number of expected patches.
     *
     * @return int
     */
    public function getTotalExpected(): int
    {
        return $this->totalExpected;
    }

    /**
     * Returns the total number of applied patches.
     *
     * @return int
     */
    public function getTotalApplied(): int
    {
        return $this->totalApplied;
    }

    /**
     * Returns whether the verification is passing.
     *
     * @return bool
     */
    public function isPassing(): bool
    {
        return $this->isPassing;
    }

    /**
     * Returns a summary message.
     *
     * @return string
     */
    public function getSummary(): string
    {
        return sprintf(
            'Verification %s: %.2f%% compliance (%d/%d patches applied)',
            $this->isPassing ? 'PASSED' : 'FAILED',
            $this->compliancePercentage,
            $this->totalApplied,
            $this->totalExpected
        );
    }

    /**
     * Converts the report to an array format suitable for JSON output.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'status' => $this->isPassing ? 'PASS' : 'FAIL',
            'compliance_percentage' => $this->compliancePercentage,
            'summary' => [
                'total_expected' => $this->totalExpected,
                'total_applied' => $this->totalApplied,
                'missing_count' => count($this->missingPatches),
                'unexpected_count' => count($this->unexpectedPatches),
            ],
            'expected_patches' => $this->expectedPatches,
            'applied_patches' => $this->appliedPatches,
            'missing_patches' => $this->missingPatches,
            'unexpected_patches' => $this->unexpectedPatches,
        ];
    }
}
