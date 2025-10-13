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
use Symfony\Component\Yaml\Tag\TaggedValue;
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
                $this->config = (array) Yaml::parse(
                    $this->filesystem->get($path),
                    $flags
                );

                $this->config = $this->normalizeYamlData($this->config);
            }
        }

        return $this->config;
    }

    /**
     * Recursively normalizes YAML data, resolving custom tags.
     *
     * @param mixed $data
     * @return mixed
     */
    private function normalizeYamlData(mixed $data): mixed
    {
        if ($data instanceof TaggedValue) {
            $tag = $data->getTag();
            $value = $data->getValue();

            switch ($tag) {
                case '!env':
                    $envValue = getenv((string)$value);
                    return $envValue !== false ? $envValue : null;

                case '!include':
                    if (file_exists((string)$value)) {
                        $included = Yaml::parseFile((string)$value);
                        return $this->normalizeYamlData($included);
                    }
                    return null;

                case '!php/const':
                    // Evaluate the PHP constant
                    return defined($value) ? constant($value) : null;

                default:
                    $val = $this->normalizeYamlData($value);
                    return is_array($val) ? $val : [$val];
            }
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->normalizeYamlData($value);
            }
            return $data;
        }

        return $data;
    }
}
