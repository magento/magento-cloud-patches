<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Conflict;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Conflict\Analyzer as ConflictAnalyzer;
use Magento\CloudPatches\Patch\RollbackProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Process patch conflict.
 */
class Processor
{
    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConflictAnalyzer
     */
    private $conflictAnalyzer;

    /**
     * @var RollbackProcessor
     */
    private $rollbackProcessor;

    /**
     * @param Renderer $renderer
     * @param LoggerInterface $logger
     * @param ConflictAnalyzer $conflictAnalyzer
     * @param RollbackProcessor $rollbackProcessor
     */
    public function __construct(
        Renderer $renderer,
        LoggerInterface $logger,
        ConflictAnalyzer $conflictAnalyzer,
        RollbackProcessor $rollbackProcessor
    ) {
        $this->renderer = $renderer;
        $this->logger = $logger;
        $this->conflictAnalyzer = $conflictAnalyzer;
        $this->rollbackProcessor = $rollbackProcessor;
    }

    /**
     * Makes rollback of applied patches and provides conflict details.
     *
     * @param OutputInterface $output
     * @param PatchInterface $patch
     * @param array $appliedPatches
     * @param string $exceptionMessage
     * @throws RuntimeException
     */
    public function process(
        OutputInterface $output,
        PatchInterface $patch,
        array $appliedPatches,
        string $exceptionMessage
    ) {
        $errorMessage = 'Error: patch ' . $patch->getId() . ' can\'t be applied';
        $this->logger->error($errorMessage);
        $output->writeln('<error>' . $errorMessage . '</error>');

        $messages = $this->rollbackProcessor->process($appliedPatches);
        $output->writeln($messages);
        $conflictDetails = $this->conflictAnalyzer->analyze($patch);
        $errorMessage = sprintf(
            'Applying patch %s (%s) failed.%s%s',
            $patch->getId(),
            $patch->getPath(),
            PHP_EOL . $exceptionMessage,
            $conflictDetails ? PHP_EOL . $conflictDetails : ''
        );

        throw new RuntimeException($errorMessage);
    }
}
