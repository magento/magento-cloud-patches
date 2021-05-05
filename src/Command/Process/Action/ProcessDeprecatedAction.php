<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Pool\PatchNotFoundException;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Check patches for deprecation and revert replaced patches after user confirmation.
 */
class ProcessDeprecatedAction implements ActionInterface
{
    /**
     * @var OptionalPool
     */
    private $optionalPool;

    /**
     * @var StatusPool
     */
    private $statusPool;

    /**
     * @var RevertAction
     */
    private $revert;

    /**
     * @var Aggregator
     */
    private $aggregator;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param OptionalPool $optionalPool
     * @param StatusPool $statusPool
     * @param RevertAction $revert
     * @param Aggregator $aggregator
     * @param Renderer $renderer
     * @param LoggerInterface $logger
     */
    public function __construct(
        OptionalPool $optionalPool,
        StatusPool $statusPool,
        RevertAction $revert,
        Aggregator $aggregator,
        Renderer $renderer,
        LoggerInterface $logger
    ) {
        $this->optionalPool = $optionalPool;
        $this->statusPool = $statusPool;
        $this->revert = $revert;
        $this->aggregator = $aggregator;
        $this->renderer = $renderer;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output, array $patchFilter)
    {
        if (empty($patchFilter)) {
            return;
        }

        try {
            $patches = $this->aggregator->aggregate($this->optionalPool->getList($patchFilter));
        } catch (PatchNotFoundException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $patches = array_filter(
            $patches,
            function ($patch) {
                return !$this->statusPool->isApplied($patch->getId());
            }
        );

        foreach ($patches as $patch) {
            $this->processDeprecation($patch, $output, $input);
            $this->processReplacement($patch, $output, $input);
        }
    }

    /**
     * Check if patch is deprecated.
     *
     * @param AggregatedPatchInterface $patch
     * @param OutputInterface $output
     * @param InputInterface $input
     * @return void
     * @throws RuntimeException
     */
    private function processDeprecation(
        AggregatedPatchInterface $patch,
        OutputInterface $output,
        InputInterface $input
    ) {
        if ($patch->isDeprecated()) {
            $this->printDeprecatedWarning($output, $patch);
            $question = 'Do you want to continue?';
            if (!$this->renderer->printQuestion($input, $output, $question)) {
                throw new RuntimeException('Patch applying process was terminated');
            }
        }
    }

    /**
     * Check if patch must replace some other patch.
     *
     * @param AggregatedPatchInterface $patch
     * @param OutputInterface $output
     * @param InputInterface $input
     *
     * @return void
     * @throws RuntimeException
     */
    private function processReplacement(
        AggregatedPatchInterface $patch,
        OutputInterface $output,
        InputInterface $input
    ) {
        $requireRevertAndReplace = array_filter(
            $this->optionalPool->getReplacedBy($patch->getId()),
            function ($patchId) {
                return $this->statusPool->isApplied($patchId);
            }
        );

        if (empty($requireRevertAndReplace)) {
            return;
        }

        $ids = implode(' ', $requireRevertAndReplace);
        $warning = sprintf('%s should be reverted and replaced with %s', $ids, $patch->getId());
        $output->writeln('<info>' . $warning . '</info>');
        $this->logger->warning($warning);

        $question = 'Do you want to proceed with reverting?';
        if (!$this->renderer->printQuestion($input, $output, $question)) {
            $errorMessage = sprintf('%s can\'t be applied without reverting %s', $patch->getId(), $ids);

            throw new RuntimeException($errorMessage);
        }

        $this->revert->execute($input, $output, $requireRevertAndReplace);
    }

    /**
     * Prints warning message about using deprecated patch.
     *
     * @param OutputInterface $output
     * @param AggregatedPatchInterface $patch
     * @return void
     */
    private function printDeprecatedWarning(OutputInterface $output, AggregatedPatchInterface $patch)
    {
        $message = sprintf(
            'Warning! Deprecated patch %s is going to be applied.%s',
            $patch->getId(),
            $patch->getReplacedWith() ? ' Please, replace it with ' . $patch->getReplacedWith() : ''
        );
        $output->writeln('<error>' . $message . '</error>');
        $this->logger->warning($message);
    }
}
