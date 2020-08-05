<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php73ce
 */
class Acceptance73CeCest extends Acceptance73Cest
{
    /**
     * @var string
     */
    protected $edition = 'CE';
}
