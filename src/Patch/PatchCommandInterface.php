<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

interface PatchCommandInterface
{
    public function apply(string $patch): bool;
    public function revert(string $patch): bool;
    public function check(string $patch): bool;
    public function status(string $patch): bool;
    public function isInstalled(): bool;
}