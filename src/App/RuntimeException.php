<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\App;

/**
 * Exception thrown if an error which can only be found on runtime occurs.
 */
class RuntimeException extends GenericException
{
}
