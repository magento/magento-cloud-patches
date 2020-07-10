<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

/**
 * Environment configuration.
 */
class Environment
{
    /**
     * Variable to define a Cloud environment.
     */
    const ENV_VAR_CLOUD = 'MAGENTO_CLOUD_PROJECT';

    /**
     * Checks if it's Cloud environment.
     *
     * @return bool
     */
    public function isCloud()
    {
        $result = $_ENV[self::ENV_VAR_CLOUD] ?? getenv(self::ENV_VAR_CLOUD);

        return (bool)$result;
    }
}
