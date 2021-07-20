<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

/**
 * Provides patch categories list.
 */
class GetCategoriesList implements GetCategoriesListInterface
{
    /**
     * @var GetCategoriesListInterface[]
     */
    private $providers;

    /**
     * @param GetCategoriesListInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @inheritDoc
     */
    public function execute(): array
    {
        $categories = [];
        foreach ($this->providers as $provider) {
            $categories = array_merge($categories, $provider->execute());
        }

        return array_unique($categories, SORT_REGULAR);
    }
}
