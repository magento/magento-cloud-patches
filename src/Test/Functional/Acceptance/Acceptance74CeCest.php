<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php74ce
 */
class Acceptance74CeCest extends Acceptance74Cest
{
    /**
     * @var string
     */
    protected $edition = 'CE';
}
