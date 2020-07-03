<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector;

use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\PatchFactory;
use Magento\CloudPatches\Patch\SourceProvider;

/**
 * Collects local patches.
 */
class LocalCollector
{
    /**
     * @var PatchFactory
     */
    private $patchFactory;

    /**
     * @var SourceProvider
     */
    private $sourceProvider;

    /**
     * @param PatchFactory $patchFactory
     * @param SourceProvider $sourceProvider
     */
    public function __construct(
        PatchFactory $patchFactory,
        SourceProvider $sourceProvider
    ) {
        $this->patchFactory = $patchFactory;
        $this->sourceProvider = $sourceProvider;
    }

    /**
     * Collects local patches.
     *
     * @return PatchInterface[]
     */
    public function collect(): array
    {
        $files = $this->sourceProvider->getLocalPatches();
        $result = [];
        foreach ($files as $file) {
            $result[] = $this->patchFactory->create(
                md5($file),
                '../' . SourceProvider::HOT_FIXES_DIR . '/' . basename($file),
                $file,
                $file,
                PatchInterface::TYPE_CUSTOM,
                '',
                '',
                [],
                '',
                false
            );
        }

        return $result;
    }
}
