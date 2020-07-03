<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Renderer;
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
     * @param Applier $applier
     * @param OptionalPool $optionalPool
     * @param StatusPool $statusPool
     * @param Renderer $renderer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Applier $applier,
        OptionalPool $optionalPool,
        StatusPool $statusPool,
        Renderer $renderer,
        LoggerInterface $logger
    ) {
        $this->applier = $applier;
        $this->optionalPool = $optionalPool;
        $this->statusPool = $statusPool;
        $this->renderer = $renderer;
        $this->logger = $logger;
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
                $this->printPatchApplyingFailed($output, $patch, $exception->getMessage());
                $this->rollback($output, $appliedPatches);

                throw new RuntimeException(
                    'Applying optional patches ' . implode(' ', $patchFilter) . ' failed.',
                    $exception->getCode()
                );
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
            '<info>Patch %s %s was already applied</info>',
            $patch->getId(),
            $patch->getFilename()
        );

        $output->writeln($message);
        $this->logger->info($message);
    }

    /**
     * Prints and logs 'applying patch failed' message.
     *
     * @param OutputInterface $output
     * @param PatchInterface $patch
     * @param string $errorOutput
     *
     * @return void
     */
    private function printPatchApplyingFailed(OutputInterface $output, PatchInterface $patch, string $errorOutput)
    {
        $errorMessage = sprintf(
            'Applying patch %s %s failed.%s',
            $patch->getId(),
            $patch->getPath(),
            $this->renderer->formatErrorOutput($errorOutput)
        );

        $output->writeln('<error>' . $errorMessage . '</error>');
        $this->logger->error($errorMessage);
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
                $this->optionalPool->getOptionalAll(),
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

    /**
     * Rollback applied patches.
     *
     * @param OutputInterface $output
     * @param PatchInterface[] $appliedPatches
     *
     * @return void
     */
    private function rollback(OutputInterface $output, array $appliedPatches)
    {
        $this->logger->info('Start rollback');

        foreach (array_reverse($appliedPatches) as $appliedPatch) {
            $message = $this->applier->revert($appliedPatch->getPath(), $appliedPatch->getId());
            $this->renderer->printPatchInfo($output, $appliedPatch, $message);
            $this->logger->info($message, ['file' => $appliedPatch->getPath()]);
        }

        $this->logger->info('End rollback');
    }
}
