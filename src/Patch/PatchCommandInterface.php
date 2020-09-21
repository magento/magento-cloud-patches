<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

/**
 * Patch command interface
 */
interface PatchCommandInterface
{
    /**
     * Applies patch
     *
     * @param string $patch
     * @return void
     */
    public function apply(string $patch);

    /**
     * Reverts patch
     *
     * @param string $patch
     * @return void
     */
    public function revert(string $patch);

    /**
     * Checks if the patch can be applied.
     *
     * @param string $patch
     * @return void
     */
    public function applyCheck(string $patch);

    /**
     * Checks if the patch can be reversed
     *
     * @param string $patch
     * @return void
     */
    public function reverseCheck(string $patch);

    /**
     * Checks if the command is installed
     *
     * @return bool
     */
    public function isInstalled(): bool;
}
