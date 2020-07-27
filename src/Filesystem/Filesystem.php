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
     * @throws FileSystemException
     */
    public function get(string $path): string
    {
        clearstatcache();
        $result = @file_get_contents($path);
        if (false === $result) {
            throw new FileSystemException('Cannot read contents from file "' . $path . '"');
        }

        return $result;
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

    /**
     * Determine if directory is writable.
     *
     * @param string $directory
     * @return bool
     */
    public function isWritable(string $directory): bool
    {
        return is_writable($directory);
    }

    /**
     * Returns directory component of path.
     *
     * @param string $path
     * @return string
     */
    public function getDirectory(string $path): string
    {
        return dirname($path);
    }

    /**
     * Creates directory.
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    public function createDirectory($path, $mode = 0755, $recursive = true): bool
    {
        return @mkdir($path, $mode, $recursive);
    }

    /**
     * Copy source into destination.
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function copy(string $source, string $destination): bool
    {
        return copy($source, $destination);
    }
}
