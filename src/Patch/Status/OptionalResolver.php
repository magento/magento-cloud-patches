<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Status;

use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Filesystem\FileSystemException;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Applier;

/**
 * Resolves statuses of quality patches.
 */
class OptionalResolver implements ResolverInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Applier
     */
    private $applier;

    /**
     * @var Aggregator
     */
    private $aggregator;

    /**
     * @var OptionalPool
     */
    private $optionalPool;

    /**
     * @param Filesystem $filesystem
     * @param Applier $applier
     * @param Aggregator $aggregator
     * @param OptionalPool $optionalPool
     */
    public function __construct(
        Filesystem $filesystem,
        Applier $applier,
        Aggregator $aggregator,
        OptionalPool $optionalPool
    ) {
        $this->filesystem = $filesystem;
        $this->applier = $applier;
        $this->aggregator = $aggregator;
        $this->optionalPool = $optionalPool;
    }

    /**
     * @inheritDoc
     */
    public function resolve(): array
    {
        $patchList = $this->optionalPool->getList();
        $aggregatedPatches = $this->aggregator->aggregate($patchList);

        $statuses = [
            StatusPool::NA => [],
            StatusPool::NOT_APPLIED => [],
            StatusPool::APPLIED => []
        ];

        $statuses = $this->analyzeIndependentPatches($aggregatedPatches, $statuses);
        $statuses = $this->analyzeDependentPatches($aggregatedPatches, $statuses);
        $statuses = $this->analyzeDependenciesOfAppliedPatches($aggregatedPatches, $statuses);

        $result = [];
        foreach ($statuses as $status => $ids) {
            foreach ($ids as $id) {
                $result[$id] = $status;
            }
        }

        return $result;
    }

    /**
     * Analyzes patches without dependencies.
     *
     * @param AggregatedPatchInterface[] $aggregatedPatches
     * @param array $statuses
     *
     * @return array
     * @throws StatusResolverException
     */
    private function analyzeIndependentPatches(array $aggregatedPatches, array $statuses)
    {
        $independentPatches = array_filter(
            $aggregatedPatches,
            function (AggregatedPatchInterface $patch) {
                return empty($patch->getRequire());
            }
        );
        foreach ($independentPatches as $aggregatedPatch) {
            $content = $this->getContent($aggregatedPatch->getItems());
            $status = $this->applier->status($content);
            $statuses[$status][] = $aggregatedPatch->getId();
        }

        return $statuses;
    }

    /**
     * Analyzes patches with dependencies.
     *
     * @param AggregatedPatchInterface[] $aggregatedPatches
     * @param array $statuses
     *
     * @return array
     * @throws StatusResolverException
     */
    private function analyzeDependentPatches(
        array $aggregatedPatches,
        array $statuses
    ) {
        $dependentPatches = array_filter(
            $aggregatedPatches,
            function (AggregatedPatchInterface $patch) {
                return $patch->getRequire();
            }
        );

        foreach ($dependentPatches as $dependentPatch) {
            // filter patches that have Applied or N/A status
            $requiredPatches = array_filter(
                $this->optionalPool->getList([$dependentPatch->getId()]),
                function (PatchInterface $patch) use ($statuses, $dependentPatch) {
                    return $patch->getId() === $dependentPatch->getId() ||
                        in_array($patch->getId(), $statuses[StatusPool::NOT_APPLIED]);
                }
            );
            $content = $this->getContent($requiredPatches);
            $status = $this->applier->status($content);
            $statuses[$status][] = $dependentPatch->getId();
        }

        return $statuses;
    }

    /**
     * Analyzes dependencies of applied patches.
     *
     * If the dependent patch was applied we assume that required patch was applied as well.
     *
     * @param AggregatedPatchInterface[] $aggregatedPatches
     * @param array $statuses
     * @return array
     */
    private function analyzeDependenciesOfAppliedPatches(array $aggregatedPatches, array $statuses)
    {
        $undefinedPatches = array_reverse($statuses[StatusPool::NA]);
        foreach ($undefinedPatches as $patchId) {
            foreach ($aggregatedPatches as $aggregatedPatch) {
                $aggregatedRequire = $aggregatedPatch->getRequire();
                if (in_array($patchId, $aggregatedRequire) &&
                    in_array($aggregatedPatch->getId(), $statuses[StatusPool::APPLIED])
                ) {
                    array_push($statuses[StatusPool::APPLIED], $patchId);
                    $statuses[StatusPool::NA] = array_diff(
                        $statuses[StatusPool::NA],
                        [$patchId]
                    );
                }
            }
        }

        return $statuses;
    }

    /**
     * Returns aggregated patch content.
     *
     * @param PatchInterface[] $patches
     *
     * @return string
     * @throws StatusResolverException
     */
    private function getContent(array $patches)
    {
        $result = '';
        foreach ($patches as $patch) {
            try {
                $result .= $this->filesystem->get($patch->getPath());
            } catch (FileSystemException $e) {
                throw new StatusResolverException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $result;
    }
}
