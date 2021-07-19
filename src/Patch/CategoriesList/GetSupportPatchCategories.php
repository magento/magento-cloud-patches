<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\CategoriesList;

use Magento\CloudPatches\Composer\QualityPackage;
use Magento\CloudPatches\Filesystem\JsonConfigReader;
use Magento\CloudPatches\Patch\GetCategoriesListInterface;

/**
 * Return a list of support patch categories.
 */
class GetSupportPatchCategories implements GetCategoriesListInterface
{
    /**
     * @var QualityPackage
     */
    private $qualityPackage;

    /**
     * @var JsonConfigReader
     */
    private $jsonConfigReader;

    /**

     * @param QualityPackage $qualityPackage
     * @param JsonConfigReader $jsonConfigReader
     */
    public function __construct(QualityPackage $qualityPackage, JsonConfigReader $jsonConfigReader)
    {
        $this->qualityPackage = $qualityPackage;
        $this->jsonConfigReader = $jsonConfigReader;
    }

    /**
     * @inheritDoc
     */
    public function execute(): array
    {
        return $this->qualityPackage->getCategoriesConfigPath()
            ? $this->jsonConfigReader->read($this->qualityPackage->getCategoriesConfigPath())
            : [];
    }
}
