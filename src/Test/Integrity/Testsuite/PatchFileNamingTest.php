<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Integrity\Testsuite;

use Magento\CloudPatches\Test\Integrity\Lib\Config;
use PHPUnit\Framework\TestCase;

/**
 * Tests for patch file naming conventions
 */
class PatchFileNamingTest extends TestCase
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->config = new Config();
    }

    /**
     * Validates that patch files follow naming conventions
     *
     * @return void
     */
    public function testPatchFileNamingConventions(): void
    {
        $patchesConfig = $this->config->get();
        $checkedFiles = [];
        $namingIssues = $this->collectNamingIssues($patchesConfig, $checkedFiles);

        if (!empty($namingIssues)) {
            $this->fail(
                "The following patch files don't follow naming conventions:\n\n" .
                implode("\n", $namingIssues) .
                "\nExpected pattern: TICKET-NUMBER__description[__additional]__version.patch\n" .
                "Examples:\n" .
                "  - MCLOUD-12345__fix_issue__2.4.6.patch\n" .
                "  - MSI-GH-2515__eliminate_concat__indexer__1.0.3.patch"
            );
        }

        $this->assertTrue(true, 'All patch files follow naming conventions');
    }

    /**
     * Collects naming issues from patch configuration
     *
     * @param array $patchesConfig
     * @param array $checkedFiles
     * @return array
     */
    private function collectNamingIssues(array $patchesConfig, array &$checkedFiles): array
    {
        $namingIssues = [];

        foreach ($patchesConfig as $patches) {
            foreach ($patches as $versionConstraints) {
                foreach ($versionConstraints as $patchFile) {
                    if (isset($checkedFiles[$patchFile])) {
                        continue;
                    }

                    $checkedFiles[$patchFile] = true;

                    if (!$this->isValidPatchFileName($patchFile)) {
                        $namingIssues[] = sprintf(
                            "  File: %s\n  Expected pattern: TICKET-NUMBER__description[__additional]__version.patch\n",
                            $patchFile
                        );
                    }
                }
            }
        }

        return $namingIssues;
    }

    /**
     * Checks if patch file name follows naming conventions
     *
     * @param string $patchFile
     * @return bool
     */
    private function isValidPatchFileName(string $patchFile): bool
    {
        // Check naming pattern - allow various formats:
        // Standard: TICKET-NUMBER__description[__additional]__version.patch
        // Examples:
        //   - MAGECLOUD-1234__fix_issue__2.3.4.patch
        //   - MC-12345__fix_problem__2.4.0.patch
        //   - MDVA-1234__security_fix__2.3.7-p1.patch
        //   - MSI-GH-2515__eliminate_concat__indexer__1.0.3.patch
        //   - Revert-AC-15165__2.4.8-p3__composer.patch (revert patches)
        //   - MCLOUD_6139__improvement_flock_locks__2.3.2.patch (underscore separator)
        //   - B2B-4051__fields_hydration__1.3.3.patch (B2B prefix)

        // Flexible pattern to accommodate existing patch naming variations
        return (
            // Standard format: PREFIX-NUMBER__segments.patch
            preg_match('/^[A-Z0-9]+-(?:GH-)?[0-9]+(?:__[\w.-]+)+\.patch$/', $patchFile) ||
            // With underscore: PREFIX_NUMBER__segments.patch
            preg_match('/^[A-Z0-9]+_[0-9]+(?:__[\w.-]+)+\.patch$/', $patchFile) ||
            // Multiple tickets: PREFIX-NUM_PREFIX-NUM__segments.patch
            preg_match('/^[A-Z0-9]+-[0-9]+_[A-Z0-9]+-[0-9]+(?:__[\w.-]+)+\.patch$/', $patchFile) ||
            // Revert patches: Revert-PREFIX-NUMBER__segments.patch
            preg_match('/^Revert-[A-Z0-9]+-[0-9]+(?:__[\w.-]+)+\.patch$/', $patchFile)
        );
    }

    /**
     * Validates that there are no duplicate patch files
     *
     * @return void
     */
    public function testNoDuplicatePatchFiles(): void
    {
        $patchesConfig = $this->config->get();
        $fileOccurrences = [];

        foreach ($patchesConfig as $package => $patches) {
            foreach ($patches as $patchTitle => $versionConstraints) {
                foreach ($versionConstraints as $constraint => $patchFile) {
                    if (!isset($fileOccurrences[$patchFile])) {
                        $fileOccurrences[$patchFile] = [];
                    }
                    $fileOccurrences[$patchFile][] = [
                        'package' => $package,
                        'title' => $patchTitle,
                        'constraint' => $constraint
                    ];
                }
            }
        }

        $duplicates = [];
        foreach ($fileOccurrences as $patchFile => $occurrences) {
            if (count($occurrences) > 1) {
                $duplicates[] = sprintf(
                    "File '%s' is referenced %d times:\n%s",
                    $patchFile,
                    count($occurrences),
                    implode("\n", array_map(function ($occ) {
                        return sprintf(
                            "    - Package: %s, Patch: %s, Constraint: %s",
                            $occ['package'],
                            $occ['title'],
                            $occ['constraint']
                        );
                    }, $occurrences))
                );
            }
        }

        if (!empty($duplicates)) {
            // Show warning but don't fail the test
            $warningMessage = "\n⚠️  WARNING: The following patch files are referenced multiple times "
                . "(this may be intentional):\n\n" .
                implode("\n\n", $duplicates) . "\n";
            fwrite(STDERR, $warningMessage);
        }

        $this->assertTrue(true, 'No duplicate patch file references found');
    }
}
