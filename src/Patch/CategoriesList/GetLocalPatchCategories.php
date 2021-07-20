<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\CategoriesList;

use Magento\CloudPatches\Filesystem\FileList;
use Magento\CloudPatches\Filesystem\JsonConfigReader;
use Magento\CloudPatches\Patch\GetCategoriesListInterface;

/**
 * Returns array of local patch categories.
 */
class GetLocalPatchCategories implements GetCategoriesListInterface
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var JsonConfigReader
     */
    private $jsonConfigReader;

    /**
     * @param FileList $fileList
     * @param JsonConfigReader $jsonConfigReader
     */
    public function __construct(FileList $fileList, JsonConfigReader $jsonConfigReader)
    {
        $this->fileList = $fileList;
        $this->jsonConfigReader = $jsonConfigReader;
    }

    /**
     * @inheritDoc
     */
    public function execute(): array
    {
        return $this->jsonConfigReader->read($this->fileList->getCategoriesConfig());
    }
}
