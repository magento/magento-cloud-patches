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
 * Builder for Patch.
 *
 * @see Patch
 */
class PatchBuilder
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $title;

    /**
     * @var array
     */
    private $categories = [];

    /**
     * @var string
     */
    private $origin = '';

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $packageName = '';

    /**
     * @var string
     */
    private $packageConstraint = '';

    /**
     * @var string[]
     */
    private $require = [];

    /**
     * @var string
     */
    private $replacedWith = '';

    /**
     * @var boolean
     */
    private $deprecated = false;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Sets patch id.
     *
     * @param string $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * Sets patch type.
     *
     * @param string $type
     * @return void
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * Sets patch title.
     *
     * @param string $title
     *
     * @return void
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Sets patch categories.
     *
     * @param array $categories
     *
     * @return void
     */
    public function setCategories(array $categories)
    {
        $this->categories = $categories;
    }

    /**
     * Sets patch origin.
     *
     * @param string $origin
     *
     * @return void
     */
    public function setOrigin(string $origin)
    {
        $this->origin = trim($origin);
    }

    /**
     * Sets patch filename.
     *
     * @param string $filename
     * @return void
     */
    public function setFilename(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Sets patch path.
     *
     * @param string $path
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * Sets package name.
     *
     * @param string $packageName
     * @return void
     */
    public function setPackageName(string $packageName)
    {
        $this->packageName = $packageName;
    }

    /**
     * Sets package constraint.
     *
     * @param string $packageConstraint
     * @return void
     */
    public function setPackageConstraint(string $packageConstraint)
    {
        $this->packageConstraint = $packageConstraint;
    }

    /**
     * Sets patch require.
     *
     * @param string[] $require
     * @return void
     */
    public function setRequire(array $require)
    {
        $this->require = $require;
    }

    /**
     * Sets patch replacedWith.
     *
     * @param string $replacedWith
     * @return void
     */
    public function setReplacedWith(string $replacedWith)
    {
        $this->replacedWith = $replacedWith;
    }

    /**
     * Sets if patch is deprecated.
     *
     * @param bool $deprecated
     * @return void
     */
    public function setDeprecated(bool $deprecated)
    {
        $this->deprecated = $deprecated;
    }

    /**
     * Builds patch data object.
     *
     * @return PatchInterface
     * @throws PatchIntegrityException
     */
    public function build()
    {
        $id = strtoupper($this->id);
        $components = $this->getAffectedComponents($this->path);

        return new Patch(
            $id,
            $this->type,
            $this->title,
            $this->categories,
            $this->origin,
            $this->filename,
            $this->path,
            $this->packageName,
            $this->packageConstraint,
            $components,
            $this->require,
            $this->replacedWith,
            $this->deprecated
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
