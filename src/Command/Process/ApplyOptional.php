<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process;

use Magento\CloudPatches\Command\Apply;
use Magento\CloudPatches\Command\Process\Action\ActionPool;
use Magento\CloudPatches\Patch\FilterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Applies optional patches (OnPrem).
 */
class ApplyOptional implements ProcessInterface
{
    /**
     * @var FilterFactory
     */
    private $filterFactory;

    /**
     * @var ActionPool
     */
    private $actionPool;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param FilterFactory $filterFactory
     * @param ActionPool $actionPool
     * @param LoggerInterface $logger
     */
    public function __construct(
        FilterFactory $filterFactory,
        ActionPool $actionPool,
        LoggerInterface $logger
    ) {
        $this->filterFactory = $filterFactory;
        $this->actionPool = $actionPool;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $argPatches = $input->getArgument(Apply::ARG_LIST_OF_PATCHES);
        $patchFilter = $this->filterFactory->createApplyFilter($argPatches);
        if ($patchFilter === null) {
            return;
        }

        $this->logger->notice('Start of applying optional patches');
        $this->logger->info('Command argument: ' . implode(' ', $argPatches));
        $this->actionPool->execute($input, $output, $patchFilter);
        $this->logger->notice('End of applying optional patches');
    }
}
