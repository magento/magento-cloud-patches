<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php71cePart1
 */
class Acceptance71CeCest extends Acceptance71Cest
{
    /**
     * @var string
     */
    protected $edition = 'CE';
}
