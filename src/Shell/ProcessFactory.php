<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Shell;

use Magento\CloudPatches\Filesystem\DirectoryList;
use Symfony\Component\Process\Process;

/**
 * Factory method for Process.
 *
 * @see Process
 */
class ProcessFactory
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
    }

    /**
     * @param array $cmd
     * @return Process
     */
    public function create(array $cmd): Process
    {
        return new Process(
            implode(' ', $cmd),
            $this->directoryList->getMagentoRoot()
        );
    }
}
