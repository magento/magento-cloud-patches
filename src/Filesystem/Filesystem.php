<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Filesystem;

/**
 * Filesystem utils.
 *
 * @codeCoverageIgnore
 */
class Filesystem
{
    /**
     * Determine if directory
     *
     * @param string $directory
     * @return bool
     */
    public function isDirectory(string $directory): bool
    {
        return is_dir($directory);
    }

    /**
     * Determine if a file or directory exists
     *
     * @param string $path
     * @return bool
     */
    public function exists($path): bool
    {
        return file_exists($path);
    }

    /**
     * Get the contents of a file
     *
     * @param string $path
     * @return string
     *
     * @throws FileNotFoundException
     */
    public function get(string $path): string
    {
        if ($this->isFile($path)) {
            return file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    /**
     * Determine if the given path is a file.
     *
     * @param string $file
     * @return bool
     */
    public function isFile(string $file): bool
    {
        return is_file($file);
    }
}
