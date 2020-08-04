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
    private $patchesConfig;

    /**
     * QualityPackage constructor
     */
    public function __construct()
    {
        if (class_exists(\Magento\QualityPatches\Info::class)) {
            $info = new \Magento\QualityPatches\Info();
            $this->patchesDirectory = $info->getPatchesDirectory();
            $this->patchesConfig = $info->getPatchesConfig();
        }
    }

    /**
     * Returns path to patches directory.
     *
     * @return string|null
     */
    public function getPatchesDirectory()
    {
        return $this->patchesDirectory;
    }

    /**
     * Returns path to patches configuration file.
     *
     * @return string|null
     */
    public function getPatchesConfig()
    {
        return $this->patchesConfig;
    }
}
