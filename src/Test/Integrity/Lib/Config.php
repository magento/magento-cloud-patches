<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Integrity\Lib;

use InvalidArgumentException;

/**
 * Contains config for patches.json
 */
class Config
{
    /**
     * Return patch configuration.
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function get(): array
    {
        $configPath = $this->getBasePath() . '/patches.json';
        
        if (!is_file($configPath)) {
            throw new InvalidArgumentException("Patches configuration file '$configPath' does not exist.");
        }

        $content = file_get_contents($configPath);
        $result = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                "Unable to unserialize patches configuration '$configPath'. Error: " . json_last_error_msg()
            );
        }

        return $result;
    }

    /**
     * Get patches directory path
     *
     * @return string
     */
    public function getPatchesDirectory(): string
    {
        return $this->getBasePath() . '/patches';
    }

    /**
     * Get base path of the project
     *
     * @return string
     */
    private function getBasePath(): string
    {
        return dirname(__DIR__, 4);
    }
}
