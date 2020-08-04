<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\App\GenericException;

/**
 * Exception if patch contains invalid data (like invalid source file or not available patches in require section).
 */
class PatchIntegrityException extends GenericException
{
}
