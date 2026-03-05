<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Verification;

use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Collector\CloudCollector;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Status\StatusPool;

/**
 * Verifies that expected patches are applied correctly.
 */
class PatchVerifier
{
    /**
     * @var Aggregator
     */
    private $aggregator;

    /**
     * @var OptionalPool
     */
    private $optionalPool;

    /**
     * @var LocalPool
     */
    private $localPool;

    /**
     * @var StatusPool
     */
    private $statusPool;

    /**
     * @var CloudCollector
     */
    private $cloudCollector;

    /**
     * @var boolean
     */
    private $cloudOnly = false;

    /**
     * PatchVerifier constructor.
     *
     * @param Aggregator $aggregator
     * @param OptionalPool $optionalPool
     * @param LocalPool $localPool
     * @param StatusPool $statusPool
     * @param CloudCollector $cloudCollector
     */
    public function __construct(
        Aggregator $aggregator,
        OptionalPool $optionalPool,
        LocalPool $localPool,
        StatusPool $statusPool,
        CloudCollector $cloudCollector
    ) {
        $this->aggregator = $aggregator;
        $this->optionalPool = $optionalPool;
        $this->localPool = $localPool;
        $this->statusPool = $statusPool;
        $this->cloudCollector = $cloudCollector;
    }

    /**
     * Enable cloud-only mode to verify only Cloud patches from patches.json.
     * When enabled, Quality patches from vendor/magento/quality-patches are excluded.
     *
     * @param bool $cloudOnly
     * @return void
     */
    public function setCloudOnly(bool $cloudOnly): void
    {
        $this->cloudOnly = $cloudOnly;
    }

    /**
     * Verifies patches and generates a report.
     *
     * @return VerificationReport
     */
    public function verify(): VerificationReport
    {
        // Get patches based on mode
        if ($this->cloudOnly) {
            // Cloud-only mode: only get patches from CloudCollector (from patches.json)
            $allPatches = $this->aggregator->aggregate(
                array_merge($this->cloudCollector->collect(), $this->localPool->getList())
            );
        } else {
            // Normal mode: get all available patches (optional + local)
            $allPatches = $this->aggregator->aggregate(
                array_merge($this->optionalPool->getList(), $this->localPool->getList())
            );
        }

        $expectedPatches = [];
        $appliedPatches = [];
        $missingPatches = [];
        $unexpectedPatches = [];

        /**
         * Identify EXPECTED patches
         * Patches that match current environment constraints and are NOT marked as N/A
         */
        foreach ($allPatches as $patch) {
            $patchId = $patch->getId();
            
            // Collect info for every patch
            $patchInfo = [
                'id' => $patchId,
                'title' => $patch->getTitle(),
                'type' => $patch->getType(),
                'origin' => $patch->getOrigin(),
                'categories' => $patch->getCategories(),
            ];

            // Resolve status for the patch
            $status = $this->statusPool->get($patchId);
            
            /**
             * Verify patch relevance and applicability
             *
             * A patch is considered relevant when:
             * - It is present in the aggregated list (indicating it passed Collector constraints)
             * - Its status is not N/A (indicating it is applicable to the current environment)
             *
             * Note: Patches not found in the status pool are marked as N/A, while patches
             *       filtered by CloudCollector are excluded from the aggregated list.
             */
            if ($status !== StatusPool::NA) {
                $expectedPatches[$patchId] = $patchInfo;
                
                if ($status === StatusPool::APPLIED) {
                    $appliedPatches[$patchId] = $patchInfo;
                } elseif ($status === StatusPool::NOT_APPLIED) {
                    $missingPatches[$patchId] = $patchInfo;
                }
            } else {
                // If status is N/A but it IS applied -> UNEXPECTED
                if ($this->statusPool->isApplied($patchId)) {
                    $unexpectedPatches[$patchId] = $patchInfo;
                }
            }
        }

        // Calculate the percentage of expected patches that are applied
        $totalExpected = count($expectedPatches);
        $totalApplied = count($appliedPatches);
        
        $compliancePercentage = $totalExpected > 0
            ? ($totalApplied / $totalExpected) * 100
            : 100.0;

        /**
         * Verification passes if:
         * 1. There are no missing patches
         * 2. There are expected patches (empty expected = fail as a safety check)
         */
        $isPassing = count($missingPatches) === 0 && $totalExpected > 0;

        return new VerificationReport(
            $expectedPatches,
            $appliedPatches,
            $missingPatches,
            $unexpectedPatches,
            $compliancePercentage,
            $totalExpected,
            $totalApplied,
            $isPassing
        );
    }

    /**
     * Verifies a specific set of patch IDs.
     *
     * @param array $patchIds
     * @return VerificationReport
     */
    public function verifySpecific(array $patchIds): VerificationReport
    {
        // Get patches based on mode (same logic as verify())
        if ($this->cloudOnly) {
            // Cloud-only mode: only get patches from CloudCollector (from patches.json)
            $allPatches = $this->aggregator->aggregate(
                array_merge($this->cloudCollector->collect(), $this->localPool->getList())
            );
        } else {
            // Normal mode: get all available patches (optional + local)
            $allPatches = $this->aggregator->aggregate(
                array_merge($this->optionalPool->getList(), $this->localPool->getList())
            );
        }

        $expectedPatches = [];
        $appliedPatches = [];
        $missingPatches = [];
        $unexpectedPatches = [];

        foreach ($patchIds as $patchId) {
            // Find the patch
            $patch = $this->findPatchById($allPatches, $patchId);

            if (!$patch) {
                // Unknown patches are treated as missing (failures)
                $missingPatches[$patchId] = [
                    'id' => $patchId,
                    'title' => 'Unknown patch (not found in available patches for this Magento version)',
                    'type' => 'unknown',
                    'origin' => 'unknown',
                    'categories' => [],
                    'note' => 'Patch not found in the available patches list for this Magento version',
                ];
                continue;
            }

            $patchInfo = [
                'id' => $patch->getId(),
                'title' => $patch->getTitle(),
                'type' => $patch->getType(),
                'origin' => $patch->getOrigin(),
                'categories' => $patch->getCategories(),
            ];

            $expectedPatches[$patchId] = $patchInfo;

            $status = $this->statusPool->get($patchId);

            if ($status === StatusPool::APPLIED) {
                $appliedPatches[$patchId] = $patchInfo;
            } elseif ($status === StatusPool::NOT_APPLIED) {
                $missingPatches[$patchId] = $patchInfo;
            } elseif ($status === StatusPool::NA) {
                // N/A patches are considered missing (can't determine status)
                $missingPatches[$patchId] = array_merge($patchInfo, ['note' => 'Status cannot be determined']);
            }
        }

        $totalExpected = count($expectedPatches);
        $totalApplied = count($appliedPatches);
        
        $compliancePercentage = $totalExpected > 0
            ? ($totalApplied / $totalExpected) * 100
            : 100.0;

        $isPassing = count($missingPatches) === 0;

        return new VerificationReport(
            $expectedPatches,
            $appliedPatches,
            $missingPatches,
            $unexpectedPatches,
            $compliancePercentage,
            $totalExpected,
            $totalApplied,
            $isPassing
        );
    }

    /**
     * Finds a patch by its ID.
     *
     * @param AggregatedPatchInterface[] $patches
     * @param string $patchId
     * @return AggregatedPatchInterface|null
     */
    private function findPatchById(array $patches, string $patchId): ?AggregatedPatchInterface
    {
        foreach ($patches as $patch) {
            if ($patch->getId() === $patchId) {
                return $patch;
            }
        }
        return null;
    }
}
