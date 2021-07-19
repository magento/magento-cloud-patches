<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Data;

/**
 * Aggregated patch data class.
 */
class AggregatedPatch implements AggregatedPatchInterface
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
    private $categories;

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
     * @var array
     */
    private $items;

    /**
     * @var string
     */
    private $origin;

    /**
     * @param string $id
     * @param string $type
     * @param string $title
     * @param array $categories
     * @param string $origin
     * @param string[] $affectedComponents
     * @param string[] $require
     * @param string $replacedWith
     * @param bool $isDeprecated
     * @param PatchInterface[] $items
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        string $id,
        string $type,
        string $title,
        array $categories,
        string $origin,
        array $affectedComponents,
        array $require,
        string $replacedWith,
        bool $isDeprecated,
        array $items
    ) {

        $this->id = $id;
        $this->type = $type;
        $this->title = $title;
        $this->categories = $categories;
        $this->origin = $origin;
        $this->affectedComponents = $affectedComponents;
        $this->require = $require;
        $this->replacedWith = $replacedWith;
        $this->isDeprecated = $isDeprecated;
        $this->items = $items;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->id;
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
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @inheritDoc
     */
    public function getOrigin(): string
    {
        return $this->origin;
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

    /**
     * @inheritDoc
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
