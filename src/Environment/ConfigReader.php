<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Environment;

use Magento\CloudPatches\Filesystem\FileList;
use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Filesystem\FileSystemException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Reads configuration from .magento.env.yaml configuration file.
 */
class ConfigReader
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Cached configuration
     *
     * @var array|null
     */
    private $config;

    /**
     * @param FileList $fileList
     * @param Filesystem $filesystem
     */
    public function __construct(FileList $fileList, Filesystem $filesystem)
    {
        $this->fileList = $fileList;
        $this->filesystem = $filesystem;
    }

    /**
     * Returns config.
     *
     * @return array
     * @throws ParseException
     * @throws FileSystemException
     */
    public function read(): array
    {
        if ($this->config === null) {
            $path = $this->fileList->getEnvConfig();

            if (!$this->filesystem->exists($path)) {
                $this->config = [];
            } else {
                $flags = 0;
                if (defined(Yaml::class . '::PARSE_CONSTANT')) {
                    $flags |= Yaml::PARSE_CONSTANT;
                }
                if (defined(Yaml::class . '::PARSE_CUSTOM_TAGS')) {
                    $flags |= Yaml::PARSE_CUSTOM_TAGS;
                }
                $this->config = (array)Yaml::parse(
                    $this->filesystem->get($path),
                    $flags
                );
            }
        }

        return $this->config;
    }
}
