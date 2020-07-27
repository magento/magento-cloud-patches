<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\ShowStatus;
use Magento\CloudPatches\Composer\MagentoVersion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
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
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ShowStatus $showStatus
     * @param LoggerInterface $logger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        ShowStatus $showStatus,
        LoggerInterface $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->showStatus = $showStatus;
        $this->logger = $logger;
        $this->magentoVersion = $magentoVersion;

        parent::__construct(self::NAME);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Shows the list of available patches and their statuses');

        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->showStatus->run($input, $output);
            $output->writeln('<info>' . $this->magentoVersion->get() . '</info>');
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
