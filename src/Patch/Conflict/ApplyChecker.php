<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Conflict;

use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\OptionalPool;

/**
 * Checks if list of patches can be applied.
 */
class ApplyChecker
{
    /**
     * @var Applier
     */
    private $applier;

    /**
     * @var OptionalPool
     */
    private $optionalPool;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Applier $applier
     * @param OptionalPool $optionalPool
     * @param Filesystem $filesystem
     */
    public function __construct(
        Applier $applier,
        OptionalPool $optionalPool,
        Filesystem $filesystem
    ) {
        $this->applier = $applier;
        $this->optionalPool = $optionalPool;
        $this->filesystem = $filesystem;
    }

    /**
     * Returns true if listed patches with all dependencies can be applied to clean Magento instance.
     *
     * @param string[] $patchIds
     * @return boolean
     */
    public function check(array $patchIds): bool
    {
        $patchItems = $this->optionalPool->getList($patchIds);
        $content = $this->getContent($patchItems);

        return $this->applier->checkApply($content);
    }

    /**
     * Returns aggregated patch content.
     *
     * @param PatchInterface[] $patches
     * @return string
     */
    private function getContent(array $patches): string
    {
        $result = '';
        foreach ($patches as $patch) {
            $result .= $this->filesystem->get($patch->getPath());
        }

        return $result;
    }
}
