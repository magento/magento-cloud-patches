<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process;

use Magento\CloudPatches\Patch\Conflict\Processor as ConflictProcessor;
use Magento\CloudPatches\Patch\Pool\RequiredPool;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Applies required patches (Cloud only).
 *
 * Patches are applying from top to bottom of config list.
 */
class ApplyRequired implements ProcessInterface
{
    /**
     * @var Applier
     */
    private $applier;

    /**
     * @var RequiredPool
     */
    private $requiredPool;

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
     * @param RequiredPool $requiredPool
     * @param Renderer $renderer
     * @param LoggerInterface $logger
     * @param ConflictProcessor $conflictProcessor
     */
    public function __construct(
        Applier $applier,
        RequiredPool $requiredPool,
        Renderer $renderer,
        LoggerInterface $logger,
        ConflictProcessor $conflictProcessor
    ) {
        $this->applier = $applier;
        $this->requiredPool = $requiredPool;
        $this->renderer = $renderer;
        $this->logger = $logger;
        $this->conflictProcessor = $conflictProcessor;
    }

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->logger->notice('Start of applying required patches');

        $appliedPatches = [];
        $patches = $this->requiredPool->getList();
        foreach ($patches as $patch) {
            try {
                $message = $this->applier->apply($patch->getPath(), $patch->getId());
                $this->renderer->printPatchInfo($output, $patch, $message);
                $this->logger->info($message, ['file' => $patch->getPath()]);
                array_push($appliedPatches, $patch);
            } catch (ApplierException $exception) {
                $this->conflictProcessor->process($output, $patch, $appliedPatches, $exception->getMessage());
            }
        }

        $this->logger->notice('End of applying required patches');
    }
}
