<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Filesystem\FileSystemException;
use Magento\CloudPatches\Patch\Data\Patch;
use Magento\CloudPatches\Patch\Data\PatchInterface;

/**
 * Factory method for Patch.
 *
 * @see Patch
 */
class PatchFactory
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
    }

    /**
     * Creates patch instance.
     *
     * @param string $id
     * @param string $title
     * @param string $filename
     * @param string $path
     * @param string $type
     * @param string $packageName
     * @param string $packageConstraint
     * @param array $require
     * @param string $replacedWith
     * @param bool $deprecated
     *
     * @return PatchInterface
     * @throws PatchIntegrityException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function create(
        string $id,
        string $title,
        string $filename,
        string $path,
        string $type,
        string $packageName,
        string $packageConstraint,
        array $require,
        string $replacedWith,
        bool $deprecated
    ): PatchInterface {
        $id = strtoupper($id);
        $components = $this->getAffectedComponents($path);

        return new Patch(
            $id,
            $type,
            $title,
            $filename,
            $path,
            $packageName,
            $packageConstraint,
            $components,
            $require,
            $replacedWith,
            $deprecated
        );
    }

    /**
     * Returns list of affected components.
     *
     * @param string $path
     * @return array
     * @throws PatchIntegrityException
     */
    private function getAffectedComponents(string $path): array
    {
        try {
            $content = $this->filesystem->get($path);
        } catch (FileSystemException $e) {
            throw new PatchIntegrityException($e->getMessage(), $e->getCode(), $e);
        }

        $result = [];
        if (preg_match_all(
            '#^.* [ab]/vendor/(?<vendor>.*?)/(?<component>.*?)/.*$#mi',
            $content,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $result[] = $match['vendor'] . '/' . $match['component'];
            }
        }

        if (preg_match_all(
            '#^.* [ab]/(?<folder>.*?)/(?<subfolder>.*?)[/ ].*$#mi',
            $content,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                if ($match['folder'] !== 'vendor') {
                    $result[] = $match['folder'] . '/' . $match['subfolder'];
                }
            }
        }

        $result = array_unique($result);
        sort($result);

        return $result;
    }
}
