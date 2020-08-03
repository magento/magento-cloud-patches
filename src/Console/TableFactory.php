<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Console;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Factory method for Table.
 */
class TableFactory
{
    /**
     * Creates Table instance.
     *
     * @param OutputInterface $output
     * @return Table
     */
    public function create(OutputInterface $output)
    {
        return new Table($output);
    }
}
