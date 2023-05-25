<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\Patch\Data\AggregatedPatch;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Data\PatchInterface;

/**
 * Factory method for AggregatedPatch.
 *
 * @see AggregatedPatch
 */
class AggregatedPatchFactory
{
    /**
     * Creates patch instance.
     *
     * @param PatchInterface[] $items
     * @return AggregatedPatchInterface
     */
    public function create(
        array $items
    ): AggregatedPatchInterface {
        $id = $this->getId($items);
        $type = $this->getType($items);
        $title = $this->getTitle($items);
        $categories = $this->getCategories($items);
        $origin = $this->getOrigin($items);
        $components = $this->getAffectedComponents($items);
        $require = $this->getRequire($items);
        $replacedWith = $this->getReplacedWith($items);
        $isDeprecated = $this->isDeprecated($items);
        $requirements = $this->getRequirements($items);

        return new AggregatedPatch(
            $id,
            $type,
            $title,
            $categories,
            $origin,
            $components,
            $require,
            $replacedWith,
            $isDeprecated,
            $items,
            $requirements
        );
    }

    /**
     * Returns aggregated patch unique identifier.
     *
     * @param PatchInterface[] $patches
     * @return string
     */
    private function getId(array $patches): string
    {
        $patch = reset($patches);

        return $patch->getId();
    }

    /**
     * Returns aggregated patch type.
     *
     * @param PatchInterface[] $patches
     * @return string
     */
    private function getType(array $patches): string
    {
        $patch = reset($patches);

        return $patch->getType();
    }

    /**
     * Returns aggregated patch title.
     *
     * @param PatchInterface[] $patches
     * @return string
     */
    private function getTitle(array $patches): string
    {
        $patch = reset($patches);

        return $patch->getTitle();
    }

    /**
     * Returns aggregated patch category.
     *
     * @param PatchInterface[] $patches
     * @return array
     */
    private function getCategories(array $patches): array
    {
        $patch = reset($patches);

        return $patch->getCategories();
    }

    /**
     * Returns aggregated patch origin.
     *
     * @param PatchInterface[] $patches
     * @return string
     */
    private function getOrigin(array $patches): string
    {
        $patch = reset($patches);

        return $patch->getOrigin();
    }

    /**
     * Returns aggregated list of affected components.
     *
     * @param PatchInterface[] $patches
     * @return string[]
     */
    private function getAffectedComponents(array $patches): array
    {
        $result = array_map(
            function (PatchInterface $patch) {
                return $patch->getAffectedComponents();
            },
            $patches
        );
        $result = array_unique(array_merge([], ...$result));
        sort($result);

        return $result;
    }

    /**
     * Returns aggregated required patches.
     *
     * @param PatchInterface[] $patches
     * @return string[]
     */
    private function getRequire(array $patches): array
    {
        $result = array_map(
            function (PatchInterface $patch) {
                return $patch->getRequire();
            },
            $patches
        );
        $result = array_unique(array_merge([], ...$result));

        return $result;
    }

    /**
     * ID of the patch, which is recommended to replace the current patch.
     *
     * @param PatchInterface[] $patches
     * @return string
     */
    private function getReplacedWith(array $patches): string
    {
        $result = '';
        foreach ($patches as $patch) {
            if ($patch->getReplacedWith()) {
                $result = $patch->getReplacedWith();
            }
        }

        return $result;
    }

    /**
     * Is patch deprecated.
     *
     * @param PatchInterface[] $patches
     * @return bool
     */
    private function isDeprecated(array $patches): bool
    {
        foreach ($patches as $patch) {
            if ($patch->isDeprecated()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns aggregated patch requirements.
     *
     * @param PatchInterface[] $patches
     * @return string
     */
    private function getRequirements(array $patches): string
    {
        $patch = reset($patches);

        return $patch->getRequirements();
    }
}
