<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php81ce
 */
class AcceptanceCeCest extends AcceptanceCest
{
    /**
     * @var string
     */
    protected $edition = 'CE';
}
