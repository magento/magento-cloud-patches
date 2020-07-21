<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\ApplyLocal;
use Magento\CloudPatches\Command\Process\ApplyOptionalEce;
use Magento\CloudPatches\Command\Process\ApplyRequired;
use Magento\CloudPatches\Composer\MagentoVersion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Patch apply command (Cloud).
 */
class ApplyEce extends AbstractCommand
{
    /**
     * Command name.
     */
    const NAME = 'apply';

    /**
     * @var ApplyOptionalEce
     */
    private $applyOptionalEce;

    /**
     * @var ApplyRequired
     */
    private $applyRequired;

    /**
     * @var ApplyLocal
     */
    private $applyLocal;

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
     * @param ApplyOptionalEce $applyOptionalEce
     * @param ApplyLocal $applyLocal
     * @param LoggerInterface $logger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        ApplyRequired $applyRequired,
        ApplyOptionalEce $applyOptionalEce,
        ApplyLocal $applyLocal,
        LoggerInterface $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->applyRequired = $applyRequired;
        $this->applyOptionalEce = $applyOptionalEce;
        $this->applyLocal = $applyLocal;
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
            ->setDescription('Applies patches (Magento Cloud only)');

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->notice($this->magentoVersion->get());

        try {
            $this->applyRequired->run($input, $output);
            $this->applyOptionalEce->run($input, $output);
            $this->applyLocal->run($input, $output);
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
