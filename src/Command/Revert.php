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
use Magento\CloudPatches\Patch\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class Revert extends AbstractCommand
{
    /**
     * Command name.
     */
    const NAME = 'revert';

    /**
     * List of quality patches to revert.
     */
    const ARG_QUALITY_PATCHES = 'quality-patches';

    /**
     * Revert all patches.
     */
    const OPT_ALL = 'all';

    /**
     * @var RevertProcess
     */
    private $revert;

    /**
     * @var Environment
     */
    private $environment;

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
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        RevertProcess $revert,
        Environment $environment,
        LoggerInterface $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->revert = $revert;
        $this->environment = $environment;
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
            ->setDescription('Revert patches')
            ->addArgument(
                self::ARG_QUALITY_PATCHES,
                InputArgument::IS_ARRAY,
                'List of quality patches to revert'
            )->addOption(
                self::OPT_ALL,
                'a',
                InputOption::VALUE_NONE,
                'Revert all patches'
            );

        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->environment->isCloud()) {
            $output->writeln('<error>Revert command is unavailable on Magento Cloud</error>');

            return self::RETURN_FAILURE;
        }

        $this->logger->notice($this->magentoVersion->get());

        try {
            $this->revert->run($input, $output);
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
