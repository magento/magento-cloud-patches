<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process\Action;

use Magento\CloudPatches\Patch\Collector\SupportCollector;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Check the number of applied optional patches and show recommendations.
 */
class ReviewAppliedAction implements ActionInterface
{
    /**
     * The threshold number of applied patches to recommend customer to upgrade to the next minor release.
     */
    const UPGRADE_THRESHOLD = 8;

    /**
     * @var OptionalPool
     */
    private $optionalPool;

    /**
     * @var StatusPool
     */
    private $statusPool;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param OptionalPool $optionalPool
     * @param StatusPool $statusPool
     * @param LoggerInterface $logger
     */
    public function __construct(
        OptionalPool $optionalPool,
        StatusPool $statusPool,
        LoggerInterface $logger
    ) {
        $this->optionalPool = $optionalPool;
        $this->statusPool = $statusPool;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output, array $patchFilter)
    {
        $appliedOptionalPatches = array_filter(
            $this->optionalPool->getOptionalListByOrigin([SupportCollector::ORIGIN]),
            function ($patch) {
                return $this->statusPool->isApplied($patch->getId());
            }
        );

        $ids = array_map(
            function ($patch) {
                return $patch->getId();
            },
            $appliedOptionalPatches
        );
        $totalNumber = count(array_unique(array_merge($ids, $patchFilter)));
        if ($totalNumber >= self::UPGRADE_THRESHOLD) {
            $message = 'Warning for those on a previous minor line! Magento recommends installing a limited'.
                ' number of quality patches to ensure a smooth upgrade to the latest line. Please begin planning'.
                ' an upgrade to the latest release line.';

            $output->writeln('<error>' . $message . '</error>');
            $this->logger->warning($message);
        }
    }
}
