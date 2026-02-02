<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\VerifyPatches;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verifies that expected patches from patches.json are applied correctly.
 */
class Verify extends AbstractCommand
{
    public const NAME = 'verify';

    /**
     * @var VerifyPatches
     */
    private $verifyPatches;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param VerifyPatches $verifyPatches
     * @param LoggerInterface $logger
     */
    public function __construct(
        VerifyPatches $verifyPatches,
        LoggerInterface $logger
    ) {
        $this->verifyPatches = $verifyPatches;
        $this->logger = $logger;

        parent::__construct(self::NAME);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Verifies that expected patches are applied correctly')
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Output format (table or json)',
                'table'
            )
            ->addOption(
                'patch-id',
                'p',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Verify specific patch IDs only'
            )
            ->addOption(
                'cloud-only',
                null,
                InputOption::VALUE_NONE,
                'Verify only Cloud patches from patches.json (excludes Quality patches from vendor)'
            )
            ->setHelp(
                <<<HELP
The <info>verify</info> command validates whether all expected patches defined in patches.json 
are actually applied during the build/deployment process.

<info>Usage:</info>
  <comment>./vendor/bin/ece-patches verify</comment>
    Verifies all relevant patches and displays results in table format
  
  <comment>./vendor/bin/ece-patches verify --format=json</comment>
    Outputs verification results in JSON format
  
  <comment>./vendor/bin/ece-patches verify --patch-id=MDVA-30106 --patch-id=MDVA-30107</comment>
    Verifies specific patches only

<info>Exit Codes:</info>
  0 - All expected patches are applied (verification passed)
  1 - One or more patches are missing (verification failed)
  2 - Runtime error occurred

<info>Report Includes:</info>
  • Total expected patches
  • Number of patches applied
  • Number of patches missing
  • Compliance percentage
  • Detailed list of missing patches
  • Recommendations for remediation
HELP
            );

        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            return $this->verifyPatches->run($input, $output);
        } catch (RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->logger->error($e->getMessage());

            return self::RETURN_FAILURE;
        } catch (\Exception $e) {
            $this->logger->critical($e);

            throw $e;
        }
    }
}
