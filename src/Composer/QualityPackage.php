<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Composer;

/**
 * Provides info from magento/quality-patches package.
 */
class QualityPackage
{
    /**
     * @var string|null
     */
    private $patchesDirectory;

    /**
     * @var string|null
     */
    private $supportPatchesConfig;

    /**
     * @var string|null
     */
    private $communityPatchesConfig;

    /**
     * @var string|null
     */
    private $categoriesConfig;

    /**
     * QualityPackage constructor
     */
    public function __construct()
    {
        if (class_exists(\Magento\QualityPatches\Info::class)) {
            $info = new \Magento\QualityPatches\Info();
            $this->patchesDirectory = $info->getPatchesDirectory();
            $this->supportPatchesConfig = $info->getSupportPatchesConfig();
            $this->communityPatchesConfig = $info->getCommunityPatchesConfig();
            $this->categoriesConfig = $info->getCategoriesConfig();
        }
    }

    /**
     * Returns path to patches directory.
     *
     * @return string|null
     */
    public function getPatchesDirectoryPath()
    {
        return $this->patchesDirectory;
    }

    /**
     * Returns path to support patches configuration file.
     *
     * @return string|null
     */
    public function getSupportPatchesConfigPath()
    {
        return $this->supportPatchesConfig;
    }

    /**
     * Returns path to community patches configuration file.
     *
     * @return string|null
     */
    public function getCommunityPatchesConfigPath()
    {
        return $this->communityPatchesConfig;
    }

    /**
     * Returns path to the categories configuration file.
     *
     * @return string|null
     */
    public function getCategoriesConfigPath()
    {
        return $this->categoriesConfig;
    }
}
