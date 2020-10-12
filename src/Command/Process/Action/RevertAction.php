<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Magento\CloudPatches\Patch\Pool\PatchNotFoundException;
use Magento\CloudPatches\Patch\RevertValidator;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reverts patches.
 *
 * Patches are reverting from bottom to top of config list.
 */
class RevertAction implements ActionInterface
{
    /**
     * @var Applier
     */
    private $applier;

    /**
     * @var RevertValidator
     */
    private $revertValidator;

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
     * @param RevertValidator $revertValidator
     * @param OptionalPool $optionalPool
     * @param StatusPool $statusPool
     * @param Renderer $renderer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Applier $applier,
        RevertValidator $revertValidator,
        OptionalPool $optionalPool,
        StatusPool $statusPool,
        Renderer $renderer,
        LoggerInterface $logger
    ) {
        $this->applier = $applier;
        $this->revertValidator = $revertValidator;
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
        $this->revertValidator->validate($patchFilter);

        try {
            $patches = array_reverse($this->optionalPool->getList($patchFilter, false));
        } catch (PatchNotFoundException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (empty($patchFilter)) {
            $patches = array_filter(
                $patches,
                function ($patch) {
                    return $this->statusPool->isApplied($patch->getId());
                }
            );
        }

        if (!$this->revert($patches, $output)) {
            throw new RuntimeException(
                'Revert operation for ' . implode(' ', $patchFilter) . ' finished with errors.'
            );
        }
    }

    /**
     * Reverts patches.
     *
     * @param PatchInterface[] $patches
     * @param OutputInterface $output
     *
     * @return bool
     */
    private function revert(array $patches, OutputInterface $output): bool
    {
        $isSuccess = true;
        foreach ($patches as $patch) {
            if ($this->statusPool->isNotApplied($patch->getId())) {
                $this->printPatchIsNotApplied($output, $patch);

                continue;
            }

            try {
                $message = $this->applier->revert($patch->getPath(), $patch->getId());
                $this->logger->info($message, ['file' => $patch->getPath()]);
                $this->renderer->printPatchInfo($output, $patch, $message);
            } catch (ApplierException $exception) {
                $this->printPatchRevertingFailed($output, $patch, $exception->getMessage());
                $isSuccess = false;
            }
        }

        return $isSuccess;
    }

    /**
     * Prints and logs 'patch is not applied' message.
     *
     * @param OutputInterface $output
     * @param PatchInterface $patch
     *
     * @return void
     */
    private function printPatchIsNotApplied(OutputInterface $output, PatchInterface $patch)
    {
        $output->writeln(
            sprintf(
                '<info>Patch %s (%s) is not applied</info>',
                $patch->getId(),
                $patch->getFilename()
            )
        );
        $this->logger->info(
            'Patch ' . $patch->getId() .' is not applied',
            ['file' => $patch->getPath()]
        );
    }

    /**
     * Prints and logs 'reverting patch failed' message.
     *
     * @param OutputInterface $output
     * @param PatchInterface $patch
     * @param string $errorOutput
     *
     * @return void
     */
    private function printPatchRevertingFailed(OutputInterface $output, PatchInterface $patch, string $errorOutput)
    {
        $errorMessage = sprintf(
            'Reverting patch %s (%s) failed.%s',
            $patch->getId(),
            $patch->getPath(),
            PHP_EOL . $errorOutput
        );

        $this->logger->error($errorMessage);
        $output->writeln('<error>' . $errorMessage . '</error>');
    }
}
