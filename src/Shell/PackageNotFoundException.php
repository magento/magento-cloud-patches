<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Shell;

use Magento\CloudPatches\App\GenericException;

/**
 * Exception if a composer package could not be found for some reason (eg, symfony/process)
 */
class PackageNotFoundException extends GenericException
{
}
