<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector;

use Magento\CloudPatches\App\GenericException;

/**
 * Exception if there are some troubles with collecting patches from source.
 */
class CollectorException extends GenericException
{
}
