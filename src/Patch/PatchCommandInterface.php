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
     * @throws PatchCommandException
     */
    public function apply(string $patch);

    /**
     * Reverts patch
     *
     * @param string $patch
     * @return void
     * @throws PatchCommandException
     */
    public function revert(string $patch);

    /**
     * Checks if patch can be applied.
     *
     * @param string $patch
     * @return void
     * @throws PatchCommandException
     */
    public function applyCheck(string $patch);

    /**
     * Checks if patch can be reverted
     *
     * @param string $patch
     * @return void
     * @throws PatchCommandException
     */
    public function revertCheck(string $patch);
}
