<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\App\GenericException;

/**
 * Exception if there are some troubles with reading patch source configuration.
 */
class SourceProviderException extends GenericException
{
}
