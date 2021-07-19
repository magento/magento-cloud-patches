<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Collector\SupportCollector;
use Magento\CloudPatches\Patch\Conflict\Processor as ConflictProcessor;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\PatchNotFoundException;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Applies optional patches.
 *
 * Patches are applying from top to bottom of config list.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApplyOptionalAction implements ActionInterface
{
    /**
     * @var Applier
     */
    private $applier;

    /**
     * @var OptionalPool
     */
    private $optionalPool;

    /**
     * @var StatusPool
     */
    private $statusPool;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConflictProcessor
     */
    private $conflictProcessor;

    /**
     * @param Applier $applier
     * @param OptionalPool $optionalPool
     * @param StatusPool $statusPool
     * @param Renderer $renderer
     * @param LoggerInterface $logger
     * @param ConflictProcessor $conflictProcessor
     */
    public function __construct(
        Applier $applier,
        OptionalPool $optionalPool,
        StatusPool $statusPool,
        Renderer $renderer,
        LoggerInterface $logger,
        ConflictProcessor $conflictProcessor
    ) {
        $this->applier = $applier;
        $this->optionalPool = $optionalPool;
        $this->statusPool = $statusPool;
        $this->renderer = $renderer;
        $this->logger = $logger;
        $this->conflictProcessor = $conflictProcessor;
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output, array $patchFilter)
    {
        $patches = $this->getPatchList($patchFilter);

        $appliedPatches = [];
        foreach ($patches as $patch) {
            if ($this->statusPool->isApplied($patch->getId())) {
                $this->printPatchWasApplied($output, $patch);

                continue;
            }

            try {
                $message = $this->applier->apply($patch->getPath(), $patch->getId());
                $this->renderer->printPatchInfo($output, $patch, $message);
                $this->logger->info($message, ['file' => $patch->getPath()]);
                array_push($appliedPatches, $patch);
            } catch (ApplierException $exception) {
                $this->conflictProcessor->process($output, $patch, $appliedPatches, $exception->getMessage());
            }
        }
    }

    /**
     * Prints and logs 'patch was applied' message.
     *
     * @param OutputInterface $output
     * @param PatchInterface $patch
     *
     * @return void
     */
    private function printPatchWasApplied(OutputInterface $output, PatchInterface $patch)
    {
        $message = sprintf(
            '<info>Patch %s (%s) was already applied</info>',
            $patch->getId(),
            $patch->getFilename()
        );

        $output->writeln($message . PHP_EOL);
        $this->logger->info($message);
    }

    /**
     * Returns a list of patches according to the filter.
     *
     * @param array $patchFilter
     *
     * @return PatchInterface[]
     * @throws RuntimeException
     */
    private function getPatchList(array $patchFilter): array
    {
        if (empty($patchFilter)) {
            return array_filter(
                $this->optionalPool->getOptionalListByOrigin([SupportCollector::ORIGIN]),
                function ($patch) {
                    return !$patch->isDeprecated();
                }
            );
        }

        try {
            return $this->optionalPool->getList($patchFilter);
        } catch (PatchNotFoundException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode());
        }
    }
}
