<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process\Ece;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Action\RevertAction;
use Magento\CloudPatches\Command\Process\ProcessInterface;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reverts all patches (Cloud).
 */
class Revert implements ProcessInterface
{
    /**
     * @var RevertAction
     */
    private $revertAction;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Applier
     */
    private $applier;

    /**
     * @var LocalPool
     */
    private $localPool;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var StatusPool
     */
    private $statusPool;

    /**
     * @param RevertAction $revertAction
     * @param LoggerInterface $logger
     * @param Applier $applier
     * @param LocalPool $localPool
     * @param Renderer $renderer
     * @param StatusPool $statusPool
     */
    public function __construct(
        RevertAction $revertAction,
        LoggerInterface $logger,
        Applier $applier,
        LocalPool $localPool,
        Renderer $renderer,
        StatusPool $statusPool
    ) {
        $this->revertAction = $revertAction;
        $this->logger = $logger;
        $this->applier = $applier;
        $this->localPool = $localPool;
        $this->renderer = $renderer;
        $this->statusPool = $statusPool;
    }

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->logger->notice('Start of reverting all patches');

        $this->revertLocalPatches($output);
        $this->revertAction->execute($input, $output, []);

        $this->logger->notice('End of reverting all patches');
    }

    /**
     * Reverts local custom patches.

     * @param OutputInterface $output
     * @return void
     * @throws RuntimeException
     */
    private function revertLocalPatches(OutputInterface $output)
    {
        $patches = array_filter(
            $this->localPool->getList(),
            function ($patch) {
                return !$this->statusPool->isNotApplied($patch->getId());
            }
        );

        if (empty($patches)) {
            return;
        }

        $output->writeln('<info>Start of reverting hot-fixes</info>');

        foreach (array_reverse($patches) as $patch) {
            try {
                $message = $this->applier->revert($patch->getPath(), $patch->getTitle());
                $this->printInfo($output, $message);
            } catch (ApplierException $exception) {
                $errorMessage = sprintf(
                    'Reverting patch %s failed.%s',
                    $patch->getPath(),
                    PHP_EOL . $exception->getMessage()
                );
                $this->printError($output, $errorMessage);
            }
        }

        $output->writeln('<info>End of reverting hot-fixes</info>');
    }

    /**
     * Prints and logs info message.
     *
     * @param OutputInterface $output
     * @param string $message
     */
    private function printInfo(OutputInterface $output, string $message)
    {
        $output->writeln('<info>' . $message . '</info>');
        $this->logger->info($message);
    }

    /**
     * Prints and logs error message.
     *
     * @param OutputInterface $output
     * @param string $message
     */
    private function printError(OutputInterface $output, string $message)
    {
        $output->writeln('<error>' . $message . '</error>');
        $this->logger->error($message);
    }
}
