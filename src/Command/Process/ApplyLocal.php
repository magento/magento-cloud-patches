<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Magento\CloudPatches\Patch\RollbackProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Applies patches from root directory m2-hotfixes (Cloud only).
 */
class ApplyLocal implements ProcessInterface
{
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RollbackProcessor
     */
    private $rollbackProcessor;

    /**
     * @param Applier $applier
     * @param LocalPool $localPool
     * @param Renderer $renderer
     * @param LoggerInterface $logger
     * @param RollbackProcessor $rollbackProcessor
     */
    public function __construct(
        Applier $applier,
        LocalPool $localPool,
        Renderer $renderer,
        LoggerInterface $logger,
        RollbackProcessor $rollbackProcessor
    ) {
        $this->applier = $applier;
        $this->localPool = $localPool;
        $this->renderer = $renderer;
        $this->logger = $logger;
        $this->rollbackProcessor = $rollbackProcessor;
    }

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $patches = $this->localPool->getList();
        if (empty($patches)) {
            $this->printInfo($output, 'Hot-fixes were not found. Skipping');

            return;
        }

        $this->logger->notice('Start of applying hot-fixes');

        $output->writeln('<info>Applying hot-fixes</info>');
        $appliedPatches = [];
        foreach ($patches as $patch) {
            try {
                $message = $this->applier->apply($patch->getPath(), $patch->getTitle());
                $this->printInfo($output, $message);
                array_push($appliedPatches, $patch);
            } catch (ApplierException $exception) {
                $this->printError($output, 'Error: patch ' . $patch->getPath() . ' can\'t be applied');
                $messages = $this->rollbackProcessor->process($appliedPatches);
                $output->writeln($messages);
                $errorMessage = sprintf(
                    'Applying patch %s failed.%s',
                    $patch->getPath(),
                    PHP_EOL . $exception->getMessage()
                );

                throw new RuntimeException($errorMessage, $exception->getCode());
            }
        }

        $this->logger->notice('End of applying hot-fixes');
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
