<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Composer;

use Magento\QualityPatches\Info;

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
     *
     * @param string|null $infoClass Class name to check for existence, defaults to Info::class
     */
    public function __construct(?string $infoClass = null)
    {
        $infoClass = $infoClass ?? Info::class;
        if (class_exists($infoClass)) {
            $info = new $infoClass();

            $this->patchesDirectory       = $info->getPatchesDirectory();
            $this->supportPatchesConfig   = $info->getSupportPatchesConfig();
            $this->communityPatchesConfig = $info->getCommunityPatchesConfig();
            $this->categoriesConfig       = $info->getCategoriesConfig();
        }
    }

    /**
     * Returns path to patches directory.
     *
     * @return string|null
     */
    public function getPatchesDirectoryPath(): ?string
    {
        return $this->patchesDirectory;
    }

    /**
     * Returns path to support patches configuration file.
     *
     * @return string|null
     */
    public function getSupportPatchesConfigPath(): ?string
    {
        return $this->supportPatchesConfig;
    }

    /**
     * Returns path to community patches configuration file.
     *
     * @return string|null
     */
    public function getCommunityPatchesConfigPath(): ?string
    {
        return $this->communityPatchesConfig;
    }

    /**
     * Returns path to the categories configuration file.
     *
     * @return string|null
     */
    public function getCategoriesConfigPath(): ?string
    {
        return $this->categoriesConfig;
    }
}
