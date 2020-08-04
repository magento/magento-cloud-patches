<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Data;

/**
 * Patch data class.
 */
class Patch implements PatchInterface
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
    private $packageName;

    /**
     * @var string
     */
    private $packageConstraint;

    /**
     * @var array
     */
    private $affectedComponents;

    /**
     * @var array
     */
    private $require;
    /**
     * @var string
     */
    private $replacedWith;

    /**
     * @var boolean
     */
    private $isDeprecated;

    /**
     * @param string $id
     * @param string $type
     * @param string $title
     * @param string $filename
     * @param string $path
     * @param string $packageName
     * @param string $packageConstraint
     * @param string[] $affectedComponents
     * @param string[] $require
     * @param string $replacedWith
     * @param bool $isDeprecated
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        string $id,
        string $type,
        string $title,
        string $filename,
        string $path,
        string $packageName,
        string $packageConstraint,
        array $affectedComponents,
        array $require,
        string $replacedWith,
        bool $isDeprecated
    ) {

        $this->id = $id;
        $this->type = $type;
        $this->title = $title;
        $this->filename = $filename;
        $this->path = $path;
        $this->packageName = $packageName;
        $this->packageConstraint = $packageConstraint;
        $this->affectedComponents = $affectedComponents;
        $this->require = $require;
        $this->replacedWith = $replacedWith;
        $this->isDeprecated = $isDeprecated;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->id . $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @inheritDoc
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getPackageName(): string
    {
        return $this->packageName;
    }

    /**
     * @inheritDoc
     */
    public function getPackageConstraint(): string
    {
        return $this->packageConstraint;
    }

    /**
     * @inheritDoc
     */
    public function getAffectedComponents(): array
    {
        return $this->affectedComponents;
    }

    /**
     * @inheritDoc
     */
    public function getRequire(): array
    {
        return $this->require;
    }

    /**
     * @inheritDoc
     */
    public function getReplacedWith(): string
    {
        return $this->replacedWith;
    }

    /**
     * @inheritDoc
     */
    public function isDeprecated(): bool
    {
        return $this->isDeprecated;
    }
}
