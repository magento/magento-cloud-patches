<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\App\GenericException;

class PatchCommandNotFound extends GenericException
{
    public function __construct()
    {
        parent::__construct('git or patch is required to perform this operation.');
    }
}