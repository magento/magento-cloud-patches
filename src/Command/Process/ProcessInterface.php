<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process;

use Magento\CloudPatches\App\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Represents an general process that should be performed on patches.
 */
interface ProcessInterface
{
    /**
     * Runs the process.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws RuntimeException
     */
    public function run(InputInterface $input, OutputInterface $output);
}
