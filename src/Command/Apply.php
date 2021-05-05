<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\ApplyOptional;
use Magento\CloudPatches\Composer\MagentoVersion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Patch apply command (OnPrem).
 */
class Apply extends AbstractCommand
{
    /**
     * Command name.
     */
    const NAME = 'apply';

    /**
     * List of patches to apply.
     */
    const ARG_LIST_OF_PATCHES = 'list-of-patches';

    /**
     * @var ApplyOptional
     */
    private $applyOptional;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ApplyOptional $applyOptional
     * @param LoggerInterface $logger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        ApplyOptional $applyOptional,
        LoggerInterface $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->applyOptional = $applyOptional;
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
            ->setDescription('Applies patches. The list of patches should pass as a command argument')
            ->addArgument(
                self::ARG_LIST_OF_PATCHES,
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'List of patches to apply'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info($this->magentoVersion->get());

        try {
            $this->applyOptional->run($input, $output);
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
