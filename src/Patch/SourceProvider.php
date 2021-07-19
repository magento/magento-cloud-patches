<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\Composer\QualityPackage;
use Magento\CloudPatches\Filesystem\DirectoryList;
use Magento\CloudPatches\Filesystem\FileList;
use Magento\CloudPatches\Filesystem\JsonConfigReader;

/**
 * Patches config provider.
 */
class SourceProvider
{
    /**
     * Directory for hot-fixes.
     */
    const HOT_FIXES_DIR = 'm2-hotfixes';

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var QualityPackage
     */
    private $qualityPackage;

    /**
     * @var JsonConfigReader
     */
    private $jsonConfigReader;

    /**
     * @param FileList $fileList
     * @param DirectoryList $directoryList
     * @param QualityPackage $qualityPackage
     * @param JsonConfigReader $jsonConfigReader
     */
    public function __construct(
        FileList $fileList,
        DirectoryList $directoryList,
        QualityPackage $qualityPackage,
        JsonConfigReader $jsonConfigReader
    ) {
        $this->fileList = $fileList;
        $this->directoryList = $directoryList;
        $this->qualityPackage = $qualityPackage;
        $this->jsonConfigReader = $jsonConfigReader;
    }

    /**
     * Returns configuration of Cloud patches.
     *
     * Example of configuration:
     * ```
     *  "colinmollenhour/credis" : {
     *      "Fix Redis issue": {
     *          "1.6": "MAGETWO-67097__fix_credis_pipeline_bug.patch"
     *      }
     *  }
     *
     * Each patch must have corresponding version constraint of target composer package.
     * @see https://getcomposer.org/doc/articles/versions.md
     *
     * @return array
     * @throws SourceProviderException
     */
    public function getCloudPatches(): array
    {
        $configPath = $this->fileList->getPatches();

        return $this->jsonConfigReader->read($configPath);
    }

    /**
     * Returns configuration of Quality patches.
     *
     * @return array
     * @throws SourceProviderException
     */
    public function getSupportPatches(): array
    {
        $configPath = $this->qualityPackage->getSupportPatchesConfigPath();
        return $configPath ? $this->jsonConfigReader->read($configPath) : [];
    }

    /**
     * Returns configuration of Community patches.
     *
     * @return array
     * @throws SourceProviderException
     */
    public function getCommunityPatches(): array
    {
        $configPath = $this->qualityPackage->getCommunityPatchesConfigPath();
        return $configPath ? $this->jsonConfigReader->read($configPath) : [];
    }

    /**
     * Returns list of local patches from m2-hotfixes directory.
     *
     * @return array
     */
    public function getLocalPatches(): array
    {
        $hotFixesDir = $this->directoryList->getMagentoRoot() . '/' . static::HOT_FIXES_DIR;
        $files = glob($hotFixesDir . '/*.patch');
        if ($files) {
            sort($files);
        }

        return $files ?: [];
    }
}
