<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Represents an action that should be performed on patches.
 */
interface ActionInterface
{
    /**
     * Executes the action.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $patchFilter
     * @return void
     * @throws RuntimeException
     */
    public function execute(InputInterface $input, OutputInterface $output, array $patchFilter);
}
