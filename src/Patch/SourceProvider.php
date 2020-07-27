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
use Magento\CloudPatches\Filesystem\FileSystemException;
use Magento\CloudPatches\Filesystem\Filesystem;

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
     * @var Filesystem
     */
    private $filesystem;

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
     * @param Filesystem $filesystem
     * @param FileList $fileList
     * @param DirectoryList $directoryList
     * @param QualityPackage $qualityPackage
     */
    public function __construct(
        Filesystem $filesystem,
        FileList $fileList,
        DirectoryList $directoryList,
        QualityPackage $qualityPackage
    ) {
        $this->filesystem = $filesystem;
        $this->fileList = $fileList;
        $this->directoryList = $directoryList;
        $this->qualityPackage = $qualityPackage;
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

        return $this->readConfiguration($configPath);
    }

    /**
     * Returns configuration of Quality patches.
     *
     * @return array
     * @throws SourceProviderException
     */
    public function getQualityPatches(): array
    {
        $configPath = $this->qualityPackage->getPatchesConfig();

        return $configPath ? $this->readConfiguration($configPath) : [];
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

    /**
     * Return patch configuration.
     *
     * @param string $configPath
     *
     * @return array
     * @throws SourceProviderException
     */
    private function readConfiguration(string $configPath): array
    {
        try {
            $content = $this->filesystem->get($configPath);
        } catch (FileSystemException $e) {
            throw new SourceProviderException($e->getMessage(), $e->getCode(), $e);
        }

        $result = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SourceProviderException(
                "Unable to unserialize patches configuration '{$configPath}'. Error: " . json_last_error_msg()
            );
        }

        return $result;
    }
}
