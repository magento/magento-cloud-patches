<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Pool\PatchNotFoundException;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Ask user confirmation to apply additional required patches.
 */
class ConfirmRequiredAction implements ActionInterface
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
     * @var Aggregator
     */
    private $aggregator;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @param OptionalPool $optionalPool
     * @param StatusPool $statusPool
     * @param Aggregator $aggregator
     * @param Renderer $renderer
     */
    public function __construct(
        OptionalPool $optionalPool,
        StatusPool $statusPool,
        Aggregator $aggregator,
        Renderer $renderer
    ) {
        $this->optionalPool = $optionalPool;
        $this->statusPool = $statusPool;
        $this->aggregator = $aggregator;
        $this->renderer = $renderer;
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output, array $patchFilter)
    {
        try {
            $requiredNotAppliedPatches = array_filter(
                $this->optionalPool->getAdditionalRequiredPatches($patchFilter),
                function ($patch) {
                    return !$this->statusPool->isApplied($patch->getId());
                }
            );
        } catch (PatchNotFoundException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if ($requiredNotAppliedPatches) {
            $output->writeln(
                '<info>Next patches are required by ' . implode(' ', $patchFilter) . ':</info>' . PHP_EOL
            );

            $aggregatedPatches = $this->aggregator->aggregate($requiredNotAppliedPatches);
            $this->renderer->printTable($output, $aggregatedPatches);

            $question = 'Do you want to proceed with applying these patches?';
            if (!$this->renderer->printQuestion($input, $output, $question)) {
                throw new RuntimeException(
                    implode(' ', $patchFilter) . ' can\'t be applied without required patches'
                );
            }
        }
    }
}
