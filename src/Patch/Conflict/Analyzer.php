<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Conflict;

use Magento\CloudPatches\Environment\Config;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\RollbackProcessor;

/**
 * Analyzes patch conflicts.
 */
class Analyzer
{
    /**
     * @var OptionalPool
     */
    private $optionalPool;

    /**
     * @var Config
     */
    private $envConfig;

    /**
     * @var RollbackProcessor
     */
    private $rollbackProcessor;

    /**
     * @var ApplyChecker
     */
    private $applyChecker;

    /**
     * @param OptionalPool $optionalPool
     * @param Config $envConfig
     * @param RollbackProcessor $rollbackProcessor
     * @param ApplyChecker $applyChecker
     */
    public function __construct(
        OptionalPool $optionalPool,
        Config $envConfig,
        RollbackProcessor $rollbackProcessor,
        ApplyChecker $applyChecker
    ) {
        $this->optionalPool = $optionalPool;
        $this->envConfig = $envConfig;
        $this->rollbackProcessor = $rollbackProcessor;
        $this->applyChecker = $applyChecker;
    }

    /**
     * Returns details about patch conflict.
     *
     * Identifies which particular patch(es) leads to conflict.
     * Works only on Cloud since we need to have a clean Magento instance before analyzing.
     *
     * @param PatchInterface $failedPatch
     * @param array $patchFilter
     * @return string
     */
    public function analyze(PatchInterface $failedPatch, array $patchFilter = []): string
    {
        if (!$this->envConfig->isCloud()) {
            return '';
        }

        if ($failedPatch->getType() !== PatchInterface::TYPE_REQUIRED) {
            $this->cleanupInstance();
        }
        $id = $failedPatch->getId();

        return $this->analyzeRequired($id) ?: $this->analyzeOptional($id, $patchFilter);
    }

    /**
     * Returns details about conflict with optional patches.
     *
     * @param string $failedPatchId
     * @param array $patchFilter
     * @return string
     */
    private function analyzeOptional(string $failedPatchId, array $patchFilter = []): string
    {
        $errorMessage = '';
        $optionalPatchIds = $patchFilter ?: $this->optionalPool->getIdsByType(PatchInterface::TYPE_OPTIONAL);
        $ids = $this->getIncompatiblePatches($optionalPatchIds, $failedPatchId);
        if ($ids) {
            $errorMessage = sprintf(
                'Patch %s is not compatible with optional: %s',
                $failedPatchId,
                implode(' ', $ids)
            );
        }

        return $errorMessage;
    }

    /**
     * Returns details about conflict with required patch.
     *
     * @param string $failedPatchId
     * @return string
     */
    private function analyzeRequired(string $failedPatchId): string
    {
        $requiredPatchIds = $this->optionalPool->getIdsByType(PatchInterface::TYPE_REQUIRED);
        $poolToCompare = array_diff($requiredPatchIds, [$failedPatchId]);
        if ($this->applyChecker->check(array_merge($poolToCompare, [$failedPatchId]))) {
            return '';
        }

        while (count($poolToCompare)) {
            $patchId = array_pop($poolToCompare);
            if ($this->applyChecker->check(array_merge($poolToCompare, [$failedPatchId]))) {
                return sprintf(
                    'Patch %s is not compatible with required: %s',
                    $failedPatchId,
                    $patchId
                );
            }
        }

        if (!$this->applyChecker->check([$failedPatchId])) {
            return 'Patch ' . $failedPatchId . ' can\'t be applied to clean Magento instance';
        }

        return '';
    }

    /**
     * Returns ids of incompatible patches.
     *
     * @param string[] $patchesToCompare
     * @param string $patchId
     * @return array
     */
    private function getIncompatiblePatches(array $patchesToCompare, string $patchId): array
    {
        $result = [];
        $patchesToCompare = array_diff($patchesToCompare, [$patchId]);
        foreach ($patchesToCompare as $compareId) {
            if (!$this->applyChecker->check([$compareId, $patchId])) {
                $result[] = $compareId;
            }
        }

        foreach ($result as $key => $patchId) {
            $dependencies = $this->optionalPool->getDependencies($patchId);
            if (array_intersect($result, $dependencies)) {
                unset($result[$key]);
            }
        }

        return $result;
    }

    /**
     * Cleanup instance from applied patches.
     *
     * @return void
     */
    private function cleanupInstance()
    {
        $requiredPatches = $this->getRequiredPatches();
        $this->rollbackProcessor->process($requiredPatches);
    }

    /**
     * Returns all patches of type 'Required'.
     *
     * @return PatchInterface[]
     */
    private function getRequiredPatches(): array
    {
        return array_filter(
            $this->optionalPool->getList(),
            function ($patch) {
                return $patch->getType() === PatchInterface::TYPE_REQUIRED;
            }
        );
    }
}
