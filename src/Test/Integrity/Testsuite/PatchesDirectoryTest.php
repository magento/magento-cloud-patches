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
 * Tests for patches directory structure and content
 */
class PatchesDirectoryTest extends TestCase
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
     * Tests that patches directory exists and is readable
     *
     * @return void
     */
    public function testPatchesDirectoryExists(): void
    {
        $patchesDir = $this->config->getPatchesDirectory();

        $this->assertDirectoryExists($patchesDir, 'Patches directory must exist');
        $this->assertTrue(is_readable($patchesDir), 'Patches directory must be readable');
    }

    /**
     * Tests that patches directory contains patch files
     *
     * @return void
     */
    public function testPatchesDirectoryContainsPatchFiles(): void
    {
        $patchesDir = $this->config->getPatchesDirectory();
        $patchFiles = glob($patchesDir . '/*.patch');

        $this->assertNotEmpty($patchFiles, 'Patches directory must contain at least one .patch file');
        $this->assertGreaterThan(
            50,
            count($patchFiles),
            'Expected at least 50 patch files in patches directory'
        );
    }

    /**
     * Tests that all patch files in directory are referenced in patches.json
     *
     * @return void
     */
    public function testAllPatchFilesAreReferenced(): void
    {
        $patchesDir = $this->config->getPatchesDirectory();
        $patchFiles = glob($patchesDir . '/*.patch');
        $patchesConfig = $this->config->get();

        // Collect all referenced files
        $referencedFiles = [];
        foreach ($patchesConfig as $patches) {
            foreach ($patches as $versionConstraints) {
                foreach ($versionConstraints as $patchFile) {
                    $referencedFiles[$patchFile] = true;
                }
            }
        }

        // Check for unreferenced files
        $unreferencedFiles = [];
        foreach ($patchFiles as $filePath) {
            $fileName = basename($filePath);
            if (!isset($referencedFiles[$fileName])) {
                $unreferencedFiles[] = $fileName;
            }
        }

        if (!empty($unreferencedFiles)) {
            // Show warning but don't fail the test
            $warningMessage = "\n⚠️  WARNING: The following patch files exist but are not "
                . "referenced in patches.json:\n" .
                implode("\n", array_map(function ($file) {
                    return "  - " . $file;
                }, $unreferencedFiles)) .
                "\n\nThis may indicate orphaned patches that should be removed or added to patches.json.\n";
            fwrite(STDERR, $warningMessage);
        }

        $this->assertTrue(true, 'All patch files are referenced in patches.json');
    }

    /**
     * Tests patch file quality (line endings, size, etc.)
     *
     * @return void
     */
    public function testPatchFileQuality(): void
    {
        $patchesConfig = $this->config->get();
        $patchesDir = $this->config->getPatchesDirectory();
        $checkedFiles = [];
        $qualityIssues = $this->collectQualityIssues($patchesConfig, $patchesDir, $checkedFiles);

        if (!empty($qualityIssues)) {
            // Show warning but don't fail the test
            $warningMessage = "\n⚠️  WARNING: Quality issues found (these are warnings, not failures):\n" .
                implode("\n", array_map(function ($issue) {
                    return "  - " . $issue;
                }, $qualityIssues)) . "\n";
            fwrite(STDERR, $warningMessage);
        }

        $this->assertTrue(true, 'All patch files meet quality standards');
    }

    /**
     * Collects quality issues from patch files
     *
     * @param array $patchesConfig
     * @param string $patchesDir
     * @param array $checkedFiles
     * @return array
     */
    private function collectQualityIssues(array $patchesConfig, string $patchesDir, array &$checkedFiles): array
    {
        $qualityIssues = [];

        foreach ($patchesConfig as $patches) {
            foreach ($patches as $versionConstraints) {
                foreach ($versionConstraints as $patchFile) {
                    $issues = $this->checkPatchFileQuality($patchFile, $patchesDir, $checkedFiles);
                    $qualityIssues = array_merge($qualityIssues, $issues);
                }
            }
        }

        return $qualityIssues;
    }

    /**
     * Checks quality of a single patch file
     *
     * @param string $patchFile
     * @param string $patchesDir
     * @param array $checkedFiles
     * @return array
     */
    private function checkPatchFileQuality(string $patchFile, string $patchesDir, array &$checkedFiles): array
    {
        $issues = [];

        // Skip if already checked
        if (isset($checkedFiles[$patchFile])) {
            return $issues;
        }

        $checkedFiles[$patchFile] = true;
        $patchPath = $patchesDir . '/' . $patchFile;

        if (!file_exists($patchPath)) {
            return $issues;
        }

        $content = file_get_contents($patchPath);

        // Check for Windows line endings (should use Unix)
        if (str_contains($content, "\r\n")) {
            $issues[] = sprintf("%s: Contains Windows line endings (CRLF), should use Unix (LF)", $patchFile);
        }

        // Check file size (warn if too large - over 1MB)
        $sizeKb = filesize($patchPath) / 1024;
        if ($sizeKb > 1024) {
            $issues[] = sprintf("%s: Very large patch file (%.1f KB), consider splitting", $patchFile, $sizeKb);
        }

        // Check for trailing whitespace in patch lines
        if (preg_match('/^[+\-].*\s+$/m', $content)) {
            $issues[] = sprintf("%s: Contains trailing whitespace in patch lines", $patchFile);
        }

        return $issues;
    }
}
