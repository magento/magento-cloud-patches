<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\ApplyLocal;
use Magento\CloudPatches\Command\Process\ApplyOptional;
use Magento\CloudPatches\Command\Process\ApplyRequired;
use Magento\CloudPatches\Composer\MagentoVersion;
use Magento\CloudPatches\Patch\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Patch apply command.
 */
class Apply extends AbstractCommand
{
    /**
     * Command name.
     */
    const NAME = 'apply';

    /**
     * Defines whether Magento is installed from Git.
     */
    const OPT_GIT_INSTALLATION = 'git-installation';

    /**
     * List of quality patches to apply.
     */
    const ARG_QUALITY_PATCHES = 'quality-patches';

    /**
     * @var ApplyOptional
     */
    private $applyOptional;

    /**
     * @var ApplyRequired
     */
    private $applyRequired;

    /**
     * @var ApplyLocal
     */
    private $applyLocal;

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
     * @param ApplyRequired $applyRequired
     * @param ApplyOptional $applyOptional
     * @param ApplyLocal $applyLocal
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        ApplyRequired $applyRequired,
        ApplyOptional $applyOptional,
        ApplyLocal $applyLocal,
        Environment $environment,
        LoggerInterface $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->applyRequired = $applyRequired;
        $this->applyOptional = $applyOptional;
        $this->applyLocal = $applyLocal;
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
            ->setDescription('Apply patches')
            ->addArgument(
                self::ARG_QUALITY_PATCHES,
                InputArgument::IS_ARRAY,
                'List of quality patches to apply'
            )->addOption(
                self::OPT_GIT_INSTALLATION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Is git installation',
                false
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $deployedFromGit = $input->getOption(Apply::OPT_GIT_INSTALLATION);
        if ($deployedFromGit) {
            $output->writeln('<info>Git-based installation. Skipping patches applying.</info>');

            return self::RETURN_SUCCESS;
        }

        $this->logger->notice($this->magentoVersion->get());

        try {
            if ($this->environment->isCloud()) {
                $this->applyRequired->run($input, $output);
                $this->applyOptional->run($input, $output);
                $this->applyLocal->run($input, $output);
            } else {
                $this->applyOptional->run($input, $output);
            }
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
