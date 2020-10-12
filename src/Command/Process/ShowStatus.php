<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Action\ReviewAppliedAction;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show information about available patches and their statuses.
 */
class ShowStatus implements ProcessInterface
{
    /**
     * @var Aggregator
     */
    private $aggregator;

    /**
     * @var OptionalPool
     */
    private $optionalPool;

    /**
     * @var LocalPool
     */
    private $localPool;

    /**
     * @var StatusPool
     */
    private $statusPool;

    /**
     * @var ReviewAppliedAction
     */
    private $reviewAppliedAction;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @param Aggregator $aggregator
     * @param OptionalPool $optionalPool
     * @param LocalPool $localPool
     * @param StatusPool $statusPool
     * @param ReviewAppliedAction $reviewAppliedAction
     * @param Renderer $renderer
     */
    public function __construct(
        Aggregator $aggregator,
        OptionalPool $optionalPool,
        LocalPool $localPool,
        StatusPool $statusPool,
        ReviewAppliedAction $reviewAppliedAction,
        Renderer $renderer
    ) {
        $this->aggregator = $aggregator;
        $this->optionalPool = $optionalPool;
        $this->localPool = $localPool;
        $this->statusPool = $statusPool;
        $this->reviewAppliedAction = $reviewAppliedAction;
        $this->renderer = $renderer;
    }

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->printDetailsInfo($output);

        $this->reviewAppliedAction->execute($input, $output, []);

        $patches = $this->aggregator->aggregate(
            array_merge($this->optionalPool->getList(), $this->localPool->getList())
        );
        foreach ($patches as $patch) {
            if ($patch->isDeprecated() && $this->statusPool->isApplied($patch->getId())) {
                $this->printDeprecatedWarning($output, $patch);
            }
        }

        $patches = array_filter(
            $patches,
            function ($patch) {
                return !$patch->isDeprecated() || $this->statusPool->isApplied($patch->getId());
            }
        );
        $this->renderer->printTable($output, $patches);
    }

    /**
     * Prints information where to find more details about patches.
     *
     * @param OutputInterface $output
     * @return void
     */
    private function printDetailsInfo(OutputInterface $output)
    {
        $supportUrl = 'https://support.magento.com';
        $releaseNotesUrl = 'https://devdocs.magento.com/quality-patches/release-notes.html';

        $output->writeln(
            '<info>Patch details you can find on </info>' .
            sprintf('<href=%1$s>%1$s</> <info>(search for patch id, ex. MDVA-30265)</info>', $supportUrl) .
            PHP_EOL .
            sprintf('<info>Release notes</info> <href=%1$s>%1$s</>', $releaseNotesUrl)
        );
    }

    /**
     * Prints warning message about applied deprecated patch.
     *
     * @param OutputInterface $output
     * @param AggregatedPatchInterface $patch
     * @return void
     */
    private function printDeprecatedWarning(OutputInterface $output, AggregatedPatchInterface $patch)
    {
        $message = sprintf(
            '<error>Deprecated patch %s is currently applied. Please, consider to revert it%s</error>',
            $patch->getId(),
            $patch->getReplacedWith() ? ' and replace with ' . $patch->getReplacedWith() : '.'
        );
        $output->writeln($message);
    }
}
