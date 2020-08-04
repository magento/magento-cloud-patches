<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process\Ece;

use Magento\CloudPatches\Command\Process\Action\ActionPool;
use Magento\CloudPatches\Command\Process\ProcessInterface;
use Magento\CloudPatches\Environment\Config;
use Magento\CloudPatches\Patch\FilterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Applies optional patches (Cloud).
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
     * @var Config
     */
    private $config;

    /**
     * @param FilterFactory $filterFactory
     * @param ActionPool $actionPool
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct(
        FilterFactory $filterFactory,
        ActionPool $actionPool,
        LoggerInterface $logger,
        Config $config
    ) {
        $this->filterFactory = $filterFactory;
        $this->actionPool = $actionPool;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $envQualityPatches = $this->config->getQualityPatches();
        $patchFilter = $this->filterFactory->createApplyFilter($envQualityPatches);
        if ($patchFilter === null) {
            return;
        }

        $this->logger->notice('Start of applying optional patches');
        $this->logger->info('QUALITY_PATCHES env variable: ' . implode(' ', $envQualityPatches));
        $this->actionPool->execute($input, $output, $patchFilter);
        $this->logger->notice('End of applying optional patches');
    }
}
