<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector;

use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\PatchBuilder;
use Magento\CloudPatches\Patch\SourceProvider;

/**
 * Collects local patches.
 */
class LocalCollector
{
    const ORIGIN = 'Local';

    /**
     * @var SourceProvider
     */
    private $sourceProvider;

    /**
     * @var PatchBuilder
     */
    private $patchBuilder;

    /**
     * @param SourceProvider $sourceProvider
     * @param PatchBuilder $patchBuilder
     */
    public function __construct(
        SourceProvider $sourceProvider,
        PatchBuilder $patchBuilder
    ) {
        $this->sourceProvider = $sourceProvider;
        $this->patchBuilder = $patchBuilder;
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
            $shortPath = '../' . SourceProvider::HOT_FIXES_DIR . '/' . basename($file);
            $this->patchBuilder->setId($shortPath);
            $this->patchBuilder->setTitle($shortPath);
            $this->patchBuilder->setFilename(basename($file));
            $this->patchBuilder->setPath($file);
            $this->patchBuilder->setType(PatchInterface::TYPE_CUSTOM);
            $this->patchBuilder->setOrigin(self::ORIGIN);
            $this->patchBuilder->setCategories(['Other']);
            $result[] = $this->patchBuilder->build();
        }

        return $result;
    }
}
