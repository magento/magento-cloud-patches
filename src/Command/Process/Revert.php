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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reverts patches.
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
     * @param FilterFactory $filterFactory
     * @param Action\RevertAction $revertAction
     */
    public function __construct(
        FilterFactory $filterFactory,
        RevertAction $revertAction
    ) {
        $this->filterFactory = $filterFactory;
        $this->revertAction = $revertAction;
    }

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $patchFilter = $this->filterFactory->createRevertFilter(
            $input->getOption(RevertCommand::OPT_ALL),
            $input->getArgument(RevertCommand::ARG_QUALITY_PATCHES)
        );

        if ($patchFilter === null) {
            return;
        }

        $this->revertAction->execute($input, $output, $patchFilter);
    }
}
