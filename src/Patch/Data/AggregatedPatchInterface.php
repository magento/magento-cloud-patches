<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Data;

/**
 * Aggregated patch data interface.
 */
interface AggregatedPatchInterface
{
    /**
     * Aggregated patch ID
     *
     * Patch unique identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Aggregated patch type.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Aggregated patch title.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Aggregated patch category.
     *
     * @return array
     */
    public function getCategories(): array;

    /**
     * Aggregated patch origin.
     *
     * @return string
     */
    public function getOrigin(): string;

    /**
     * List of affected components.
     *
     * @return string[]
     */
    public function getAffectedComponents(): array;

    /**
     * Required patches.
     *
     * @return string[]
     */
    public function getRequire(): array;

    /**
     * ID of the patch, which is recommended to replace the current patch.
     *
     * @return string
     */
    public function getReplacedWith(): string;

    /**
     * Is patch deprecated.
     *
     * @return bool
     */
    public function isDeprecated(): bool;

    /**
     * Patch items.
     *
     * @return PatchInterface[]
     */
    public function getItems(): array;
}
