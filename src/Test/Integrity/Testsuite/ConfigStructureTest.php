<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Integrity\Testsuite;

use Composer\Semver\VersionParser;
use Exception;
use InvalidArgumentException;
use Magento\CloudPatches\Test\Integrity\Lib\Config;
use PHPUnit\Framework\TestCase;

/**
 * Tests for patches.json structure and validity
 */
class ConfigStructureTest extends TestCase
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var VersionParser
     */
    private VersionParser $versionParser;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->config = new Config();
        $this->versionParser = new VersionParser();
    }

    /**
     * Validates patches.json structure
     *
     * @return void
     */
    public function testPatchesJsonStructure(): void
    {
        try {
            $patchesConfig = $this->config->get();
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage());
        }

        $this->assertIsArray($patchesConfig, 'patches.json must contain a valid JSON object');
        $this->assertNotEmpty($patchesConfig, 'patches.json must not be empty');

        $errors = $this->validatePatchesConfiguration($patchesConfig);
        
        if (!empty($errors)) {
            $this->fail(
                "patches.json has structural errors:\n" . implode("\n", $errors)
            );
        }
    }

    /**
     * Validates all referenced patch files exist
     *
     * @return void
     */
    public function testAllReferencedPatchFilesExist(): void
    {
        $patchesConfig = $this->config->get();
        $patchesDir = $this->config->getPatchesDirectory();
        $missingFiles = [];
        $checkedFiles = [];

        foreach ($patchesConfig as $patches) {
            foreach ($patches as $versionConstraints) {
                foreach ($versionConstraints as $patchFile) {
                    // Skip if already checked
                    if (isset($checkedFiles[$patchFile])) {
                        continue;
                    }

                    $checkedFiles[$patchFile] = true;
                    $patchPath = $patchesDir . '/' . $patchFile;

                    if (!is_file($patchPath)) {
                        $missingFiles[] = sprintf("  File: %s\n", $patchFile);
                    }
                }
            }
        }

        if (!empty($missingFiles)) {
            $this->fail(
                "The following patch files are referenced in patches.json but do not exist:\n\n" .
                implode("\n", $missingFiles)
            );
        }

        $this->assertTrue(true, 'All referenced patch files exist');
    }

    /**
     * Validates that patch files are valid .patch format
     *
     * @return void
     */
    public function testPatchFilesAreValid(): void
    {
        $patchesConfig = $this->config->get();
        $patchesDir = $this->config->getPatchesDirectory();
        $checkedFiles = [];

        $invalidFiles = $this->collectInvalidPatchFiles($patchesConfig, $patchesDir, $checkedFiles);

        if (!empty($invalidFiles)) {
            $this->fail(
                "The following patch files are invalid:\n" . implode("\n", $invalidFiles)
            );
        }

        $this->assertTrue(true, 'All patch files are valid');
    }

    /**
     * Collects invalid patch files from configuration
     *
     * @param array $patchesConfig
     * @param string $patchesDir
     * @param array $checkedFiles
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function collectInvalidPatchFiles(array $patchesConfig, string $patchesDir, array &$checkedFiles): array
    {
        $invalidFiles = [];

        foreach ($patchesConfig as $patches) {
            foreach ($patches as $versionConstraints) {
                foreach ($versionConstraints as $patchFile) {
                    $errors = $this->validatePatchFile($patchFile, $patchesDir, $checkedFiles);
                    $invalidFiles = array_merge($invalidFiles, $errors);
                }
            }
        }

        return $invalidFiles;
    }

    /**
     * Validates a single patch file
     *
     * @param string $patchFile
     * @param string $patchesDir
     * @param array $checkedFiles
     * @return array
     */
    private function validatePatchFile(string $patchFile, string $patchesDir, array &$checkedFiles): array
    {
        $errors = [];

        // Skip if already checked
        if (isset($checkedFiles[$patchFile])) {
            return $errors;
        }

        $checkedFiles[$patchFile] = true;
        $patchPath = $patchesDir . '/' . $patchFile;

        if (!is_file($patchPath)) {
            return $errors; // Already reported in testAllReferencedPatchFilesExist
        }

        // Check file extension
        if (pathinfo($patchFile, PATHINFO_EXTENSION) !== 'patch') {
            $errors[] = sprintf("%s: Invalid file extension (expected .patch)", $patchFile);
            return $errors;
        }

        // Check patch file is not empty
        $content = file_get_contents($patchPath);
        if (empty(trim($content))) {
            $errors[] = sprintf("%s: Patch file is empty", $patchFile);
            return $errors;
        }

        // Check for basic patch format markers
        if (!preg_match('/^(---|diff|Index:)/m', $content)) {
            $errors[] = sprintf("%s: Does not appear to be a valid patch file (missing diff markers)", $patchFile);
        }

        return $errors;
    }

    /**
     * Validates version constraints
     *
     * @return void
     */
    public function testVersionConstraintsAreValid(): void
    {
        $patchesConfig = $this->config->get();
        $invalidConstraints = [];

        foreach ($patchesConfig as $package => $patches) {
            foreach ($patches as $patchTitle => $versionConstraints) {
                foreach (array_keys($versionConstraints) as $constraint) {
                    try {
                        // Split by || to handle multiple constraints
                        $constraintParts = array_map('trim', explode('||', $constraint));
                        foreach ($constraintParts as $singleConstraint) {
                            $this->versionParser->parseConstraints($singleConstraint);
                        }
                    } catch (Exception $e) {
                        $invalidConstraints[] = sprintf(
                            "  Package: %s\n  Patch: %s\n  Constraint: '%s'\n  Error: %s\n",
                            $package,
                            $patchTitle,
                            $constraint,
                            $e->getMessage()
                        );
                    }
                }
            }
        }

        if (!empty($invalidConstraints)) {
            $this->fail(
                "The following version constraints are invalid:\n\n" . implode("\n", $invalidConstraints)
            );
        }

        $this->assertTrue(true, 'All version constraints are valid');
    }

    /**
     * Validates patches configuration
     *
     * @param array $patchesConfig
     * @return array
     */
    private function validatePatchesConfiguration(array $patchesConfig): array
    {
        $errors = [];

        foreach ($patchesConfig as $package => $patches) {
            $packageErrors = $this->validatePackageConfiguration($package, $patches);
            $errors = array_merge($errors, $packageErrors);
        }

        return $errors;
    }

    /**
     * Validates a single package configuration
     *
     * @param string $package
     * @param mixed $patches
     * @return array
     */
    private function validatePackageConfiguration(string $package, mixed $patches): array
    {
        $errors = [];

        if (!is_array($patches)) {
            $errors[] = sprintf("Package '%s' configuration must be an array", $package);
            return $errors;
        }

        foreach ($patches as $patchTitle => $versionConstraints) {
            $patchErrors = $this->validatePatchConfiguration($package, $patchTitle, $versionConstraints);
            $errors = array_merge($errors, $patchErrors);
        }

        return $errors;
    }

    /**
     * Validates a single patch configuration
     *
     * @param string $package
     * @param mixed $patchTitle
     * @param mixed $versionConstraints
     * @return array
     */
    private function validatePatchConfiguration(string $package, mixed $patchTitle, mixed $versionConstraints): array
    {
        $errors = [];

        if (!is_string($patchTitle) || empty($patchTitle)) {
            $errors[] = sprintf("Package '%s' has an invalid patch title", $package);
            return $errors;
        }

        if (!is_array($versionConstraints)) {
            $errors[] = sprintf(
                "Package '%s', patch '%s' must have version constraints as an array",
                $package,
                $patchTitle
            );
            return $errors;
        }

        foreach ($versionConstraints as $constraint => $patchFile) {
            if (!is_string($constraint) || empty($constraint)) {
                $errors[] = sprintf(
                    "Package '%s', patch '%s' has an invalid version constraint",
                    $package,
                    $patchTitle
                );
            }

            if (!is_string($patchFile) || empty($patchFile)) {
                $errors[] = sprintf(
                    "Package '%s', patch '%s', constraint '%s' has an invalid patch file reference",
                    $package,
                    $patchTitle,
                    $constraint
                );
            }
        }

        return $errors;
    }
}
