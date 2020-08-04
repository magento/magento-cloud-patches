<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Status;

use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Filesystem\FileSystemException;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Applier;

/**
 * Resolves statuses of local patches.
 */
class LocalResolver implements ResolverInterface
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
     * @var LocalPool
     */
    private $localPool;

    /**
     * @param Filesystem $filesystem
     * @param Applier $applier
     * @param LocalPool $localPool
     */
    public function __construct(
        Filesystem $filesystem,
        Applier $applier,
        LocalPool $localPool
    ) {
        $this->filesystem = $filesystem;
        $this->applier = $applier;
        $this->localPool = $localPool;
    }

    /**
     * @inheritDoc
     */
    public function resolve(): array
    {
        $result = [];
        foreach ($this->localPool->getList() as $patch) {
            try {
                $content = $this->filesystem->get($patch->getPath());
            } catch (FileSystemException $e) {
                throw new StatusResolverException($e->getMessage(), $e->getCode(), $e);
            }

            $result[$patch->getId()] = $this->applier->status($content);
        }

        return $result;
    }
}
