<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process;

use Magento\CloudPatches\Composer\MagentoVersion;
use Magento\CloudPatches\Patch\Verification\PatchVerifier;
use Magento\CloudPatches\Patch\Verification\VerificationReport;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verifies that expected patches are applied.
 */
class VerifyPatches implements ProcessInterface
{
    public const FORMAT_JSON = 'json';
    public const FORMAT_TABLE = 'table';

    /**
     * @var PatchVerifier
     */
    private $patchVerifier;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param PatchVerifier $patchVerifier
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        PatchVerifier $patchVerifier,
        MagentoVersion $magentoVersion
    ) {
        $this->patchVerifier = $patchVerifier;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format') ?? self::FORMAT_TABLE;
        $patchIds = $input->getOption('patch-id');

        // Enable cloud-only mode if requested (verify only Cloud patches from patches.json)
        $cloudOnly = $input->getOption('cloud-only');
        if ($cloudOnly) {
            $this->patchVerifier->setCloudOnly(true);
            if ($format !== self::FORMAT_JSON) {
                $output->writeln(
                    '<comment>Cloud-only mode enabled - verifying only Cloud patches from patches.json</comment>'
                );
                $output->writeln('');
            }
        }

        // Run verification
        if (!empty($patchIds)) {
            $report = $this->patchVerifier->verifySpecific($patchIds);
        } else {
            $report = $this->patchVerifier->verify();
        }

        // Output the report
        if ($format === self::FORMAT_JSON) {
            $this->outputJson($output, $report);
        } else {
            $this->outputTable($output, $report);
        }

        // Return appropriate exit code
        return $report->isPassing() ? 0 : 1;
    }

    /**
     * Outputs the report in JSON format.
     *
     * @param OutputInterface $output
     * @param VerificationReport $report
     * @return void
     */
    private function outputJson(OutputInterface $output, VerificationReport $report): void
    {
        $output->writeln(json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Outputs the report in table format.
     *
     * @param OutputInterface $output
     * @param VerificationReport $report
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function outputTable(OutputInterface $output, VerificationReport $report): void
    {
        $output->writeln('');
        $output->writeln('<info>═══════════════════════════════════════════════════════════</info>');
        $output->writeln('<info>            Patch Application Verification Report         </info>');
        $output->writeln('<info>═══════════════════════════════════════════════════════════</info>');
        $output->writeln('');

        // Summary
        $statusTag = $report->isPassing() ? 'info' : 'error';
        $output->writeln(sprintf('<%s>%s</%s>', $statusTag, $report->getSummary(), $statusTag));
        $output->writeln('');

        // Statistics
        $output->writeln('<info>Statistics:</info>');
        $output->writeln(sprintf('  Total Expected Patches:  <comment>%d</comment>', $report->getTotalExpected()));
        $output->writeln(sprintf('  Patches Applied:         <info>%d</info>', $report->getTotalApplied()));
        $missingCount = count($report->getMissingPatches());
        $unexpectedCount = count($report->getUnexpectedPatches());
        $output->writeln(sprintf('  Missing Patches:         <error>%d</error>', $missingCount));
        $output->writeln(sprintf('  Unexpected Patches:      <comment>%d</comment>', $unexpectedCount));
        $compliance = $report->getCompliancePercentage();
        $output->writeln(sprintf('  Compliance:              <comment>%.2f%%</comment>', $compliance));
        $output->writeln('');

        // Missing patches (failures)
        if (!empty($report->getMissingPatches())) {
            $output->writeln('<error>Missing Patches (Expected but Not Applied):</error>');
            $output->writeln('<error>───────────────────────────────────────────────────────────</error>');
            foreach ($report->getMissingPatches() as $patch) {
                $output->writeln(sprintf('  <error>✗</error> <comment>%s</comment>', $patch['id']));
                $output->writeln(sprintf('    Title:      %s', $patch['title']));
                $output->writeln(sprintf('    Type:       %s', $patch['type']));
                $output->writeln(sprintf('    Origin:     %s', $patch['origin']));
                if (!empty($patch['categories'])) {
                    $output->writeln(sprintf('    Categories: %s', implode(', ', $patch['categories'])));
                }
                if (isset($patch['note'])) {
                    $output->writeln(sprintf('    <comment>Note: %s</comment>', $patch['note']));
                }
                $output->writeln('');
            }
        }

        // Unexpected patches (informational)
        if (!empty($report->getUnexpectedPatches())) {
            $output->writeln('<comment>Unexpected Patches (Applied but Not Expected):</comment>');
            $output->writeln('<comment>───────────────────────────────────────────────────────────</comment>');
            foreach ($report->getUnexpectedPatches() as $patch) {
                $output->writeln(sprintf('  <comment>⚠</comment> <comment>%s</comment>', $patch['id']));
                $output->writeln(sprintf('    Title:  %s', $patch['title']));
                $output->writeln('');
            }
        }

        // Applied patches summary
        if ($report->getTotalApplied() > 0 && empty($report->getMissingPatches())) {
            $output->writeln('<info>✓ All Expected Patches Applied Successfully</info>');
            $output->writeln('');
        }

        $output->writeln('<info>═══════════════════════════════════════════════════════════</info>');
        $output->writeln('');

        // Recommendations
        if (!$report->isPassing()) {
            $output->writeln('<comment>Recommendations:</comment>');
            $output->writeln('  • Review missing patches and determine if they should be applied');
            $output->writeln('  • Check environment configuration that may prevent patch application');
            $output->writeln('  • Verify Magento version compatibility with missing patches');
            $output->writeln('  • Run: <info>./vendor/bin/ece-patches status</info> for detailed patch status');
            $output->writeln('  • Run: <info>./vendor/bin/ece-patches apply</info> to apply missing patches');
            $output->writeln('');
        }
    }
}
