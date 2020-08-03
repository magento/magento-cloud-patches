<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Status;

/**
 * Patch status resolver interface.
 */
interface ResolverInterface
{
    /**
     * Resolves patch statuses.
     *
     * @return string[]
     * @throws StatusResolverException
     */
    public function resolve(): array;
}
