<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Environment;

use Magento\CloudPatches\Filesystem\FileSystemException;

/**
 * Environment configuration.
 */
class Config
{
    /**
     * Variable to define a Cloud environment.
     */
    const ENV_VAR_CLOUD = 'MAGENTO_CLOUD_PROJECT';

    /**
     * Const is defined in ./bin/ece-patches
     */
    const CONST_IS_CLOUD = 'IS_CLOUD';

    /**
     * Quality patches environment variable.
     */
    const ENV_VAR_QUALITY_PATCHES = 'QUALITY_PATCHES';

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @param ConfigReader $configReader
     */
    public function __construct(ConfigReader $configReader)
    {
        $this->configReader = $configReader;
    }
    /**
     * Checks if it's Cloud environment.
     *
     * @return bool
     */
    public function isCloud(): bool
    {
        return (bool)$this->getEnv(self::ENV_VAR_CLOUD) || defined(self::CONST_IS_CLOUD);
    }

    /**
     * Returns quality patches env variable.
     *
     * @return array
     * @throws FileSystemException
     */
    public function getQualityPatches(): array
    {
        $result = $this->getEnv(self::ENV_VAR_QUALITY_PATCHES);
        if ($result === false) {
            $result = $this->configReader->read()['stage']['build'][self::ENV_VAR_QUALITY_PATCHES] ?? [];
        }

        return $result ?: [];
    }

    /**
     * 'getEnv' method is an abstraction for _ENV and getenv.
     * If _ENV is enabled in php.ini, use that.  If not, fall back to use getenv.
     * returns false if not found
     *
     * @param string $key
     * @return array|string|int|null|bool
     */
    private function getEnv(string $key)
    {
        return $_ENV[$key] ?? getenv($key);
    }
}
