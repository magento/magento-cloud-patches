<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector;

/**
 * Collects community patches.
 */
class CommunityCollector extends SupportCollector
{
    const ORIGIN = 'Magento OS Community';
}
