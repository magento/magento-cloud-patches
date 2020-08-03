<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Pool;

use Magento\CloudPatches\App\GenericException;

/**
 * Exception if a patch could not be found in a pool.
 */
class PatchNotFoundException extends GenericException
{
}
