<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Filesystem;

/**
 * List of app files.
 */
class FileList
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
    }

    /**
     * @return string
     */
    public function getPatches(): string
    {
        return $this->directoryList->getRoot() . '/patches.json';
    }

    /**
     * @return string
     */
    public function getCategoriesConfig(): string
    {
        return $this->directoryList->getRoot() . '/config/patch-categories.json';
    }

    /**
     * @return string
     */
    public function getPatchLog(): string
    {
        return $this->directoryList->getMagentoRoot() . '/var/log/patch.log';
    }

    /**
     * @return string
     */
    public function getEnvConfig(): string
    {
        return $this->directoryList->getMagentoRoot() . '/.magento.env.yaml';
    }
}
