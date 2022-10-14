<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\ShowStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class Status extends AbstractCommand
{
    const NAME = 'status';

    /**
     * @var ShowStatus
     */
    private $showStatus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ShowStatus $showStatus
     * @param LoggerInterface $logger
     */
    public function __construct(
        ShowStatus $showStatus,
        LoggerInterface $logger
    ) {
        $this->showStatus = $showStatus;
        $this->logger = $logger;

        parent::__construct(self::NAME);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Shows the list of available patches and their statuses')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format', 'table');

        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->showStatus->run($input, $output);
        } catch (RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->logger->error($e->getMessage());

            return self::RETURN_FAILURE;
        } catch (\Exception $e) {
            $this->logger->critical($e);

            throw $e;
        }

        return self::RETURN_SUCCESS;
    }
}
