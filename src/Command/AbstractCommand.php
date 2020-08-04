<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command;

use Symfony\Component\Console\Command\Command;

/**
 * @inheritDoc
 */
class AbstractCommand extends Command
{
    /**
     * Cli exit code - success.
     */
    const RETURN_SUCCESS = 0;

    /**
     * Cli exit code - failure.
     */
    const RETURN_FAILURE = 1;
}
