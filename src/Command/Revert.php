<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Revert as RevertProcess;
use Magento\CloudPatches\Composer\MagentoVersion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Patch revert command (OnPrem).
 */
class Revert extends AbstractCommand
{
    /**
     * Command name.
     */
    const NAME = 'revert';

    /**
     * List of patches to revert.
     */
    const ARG_LIST_OF_PATCHES = 'list-of-patches';

    /**
     * Revert all patches.
     */
    const OPT_ALL = 'all';

    /**
     * @var RevertProcess
     */
    private $revert;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param RevertProcess $revert
     * @param LoggerInterface $logger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        RevertProcess $revert,
        LoggerInterface $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->revert = $revert;
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
            ->setDescription(
                'Reverts patches. The list of patches should pass as a command argument' .
                ' or use option --all to revert all patches'
            )->addArgument(
                self::ARG_LIST_OF_PATCHES,
                InputArgument::IS_ARRAY,
                'List of patches to revert'
            )->addOption(
                self::OPT_ALL,
                'a',
                InputOption::VALUE_NONE,
                'Reverts all patches'
            );

        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info($this->magentoVersion->get());

        try {
            $this->revert->run($input, $output);
        } catch (RuntimeException $e) {
            $output->writeln($this->magentoVersion->get());
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->logger->info($this->magentoVersion->get());
            $this->logger->error($e->getMessage());

            return self::RETURN_FAILURE;
        } catch (\Exception $e) {
            $this->logger->critical($e);

            throw $e;
        }

        return self::RETURN_SUCCESS;
    }
}
