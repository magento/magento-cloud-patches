<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Ece;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\AbstractCommand;
use Magento\CloudPatches\Command\Process\Ece\Revert as RevertProcess;
use Magento\CloudPatches\Composer\MagentoVersion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Patch revert command (Cloud).
 */
class Revert extends AbstractCommand
{
    /**
     * Command name.
     */
    const NAME = 'revert';

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
            ->setDescription('Reverts patches (Magento Cloud only)');

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
