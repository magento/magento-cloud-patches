<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process;

use Magento\CloudPatches\Command\Process\Action\RevertAction;
use Magento\CloudPatches\Command\Revert as RevertCommand;
use Magento\CloudPatches\Patch\FilterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reverts patches (OnPrem).
 *
 * Patches are reverting from bottom to top of config list.
 */
class Revert implements ProcessInterface
{
    /**
     * @var FilterFactory
     */
    private $filterFactory;

    /**
     * @var RevertAction
     */
    private $revertAction;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param FilterFactory $filterFactory
     * @param Action\RevertAction $revertAction
     * @param LoggerInterface $logger
     */
    public function __construct(
        FilterFactory $filterFactory,
        RevertAction $revertAction,
        LoggerInterface $logger
    ) {
        $this->filterFactory = $filterFactory;
        $this->revertAction = $revertAction;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $argPatches = $input->getArgument(RevertCommand::ARG_LIST_OF_PATCHES);
        $optAll =  $input->getOption(RevertCommand::OPT_ALL);
        $patchFilter = $this->filterFactory->createRevertFilter($optAll, $argPatches);

        if ($patchFilter === null) {
            return;
        }
        $this->logger->notice('Start of reverting optional patches');

        $this->logger->info('Command argument: ' . implode(' ', $argPatches));
        $this->logger->info('Command option: ' . $optAll ? '--all' : '');
        $this->revertAction->execute($input, $output, $patchFilter);

        $this->logger->notice('End of reverting optional patches');
    }
}
