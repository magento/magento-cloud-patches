<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * Abstract PatchApplierCest
 *
 * @abstract
 */
abstract class PatchApplierCest extends AbstractCest
{
    /**
     * Prepares the test environment before each test.
     *
     * @param \CliTester $I The CLI tester instance.
     * @throws \Robo\Exception\TaskException
     */
    public function _before(\CliTester $I): void
    {
        parent::_before($I);
    }

    /**
     * Tests applying an existing patch to a target file.
     *
     * @param \CliTester $I
     * @param \Codeception\Example $data The example data for the test.
     *        Expected structure:
     *        [
     *            'templateVersion' => string,
     *            'magentoVersion' => string|null (optional)
     *        ]
     * @throws \Robo\Exception\TaskException
     * @dataProvider patchesDataProvider
     */
    public function testApplyingPatch(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareTemplate($I, $data['templateVersion'], $data['magentoVersion'] ?? null);

        $I->generateDockerCompose('--mode=production');

        $I->copyFileToWorkDir('files/debug_logging/.magento.env.yaml', '.magento.env.yaml');
        $I->copyFileToWorkDir('files/patches/target_file.md', 'target_file.md');
        $I->copyFileToWorkDir('files/patches/patch.patch', 'm2-hotfixes/patch.patch');

        // For this test, only the build phase is enough
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();

        $targetFile = $I->grabFileContent('/target_file.md', Docker::BUILD_CONTAINER);
        $I->assertStringContainsString('# Hello Magento', $targetFile);
        $I->assertStringContainsString('## Additional Info', $targetFile);

        // Try to get the log file, but handle case where it might not exist
        $log = $I->grabFileContent('/init/var/log/cloud.log', Docker::BUILD_CONTAINER);
        if (empty($log)) {
            // Log file might be in a different location, try alternative paths
            $log = $I->grabFileContent('/var/log/cloud.log', Docker::BUILD_CONTAINER);
        }
        $I->assertNotEmpty($log, 'cloud.log file should exist and contain build logs');
        $I->assertStringContainsString('Patch ../m2-hotfixes/patch.patch has been applied', $log);
    }

    /**
     * Tests that an existing patch is not applied again.
     *
     * @param \CliTester $I
     * @param \Codeception\Example $data The example data for the test.
     *        Expected structure:
     *        [
     *            'templateVersion' => string,
     *            'magentoVersion' => string|null (optional)
     *        ]
     * @throws \Robo\Exception\TaskException
     * @dataProvider patchesDataProvider
     */
    public function testApplyingExistingPatch(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareTemplate($I, $data['templateVersion'], $data['magentoVersion'] ?? null);

        $I->generateDockerCompose('--mode=production');

        $I->copyFileToWorkDir('files/debug_logging/.magento.env.yaml', '.magento.env.yaml');
        $I->copyFileToWorkDir('files/patches/target_file_applied_patch.md', 'target_file.md');
        $I->copyFileToWorkDir('files/patches/patch.patch', 'm2-hotfixes/patch.patch');

        // For this test, only the build phase is enough
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();

        $targetFile = $I->grabFileContent('/target_file.md', Docker::BUILD_CONTAINER);
        $I->assertStringContainsString('# Hello Magento', $targetFile);
        $I->assertStringContainsString('## Additional Info', $targetFile);

        // Try to get the log file, but handle case where it might not exist
        $log = $I->grabFileContent('/init/var/log/cloud.log', Docker::BUILD_CONTAINER);
        if (empty($log)) {
            // Log file might be in a different location, try alternative paths
            $log = $I->grabFileContent('/var/log/cloud.log', Docker::BUILD_CONTAINER);
        }
        $I->assertNotEmpty($log, 'cloud.log file should exist and contain build logs');
        $I->assertStringContainsString(
            'Patch ../m2-hotfixes/patch.patch was already applied',
            $log
        );
    }

    /**
     * Returns the data provider for patches.
     * @return array
     */
    abstract protected function patchesDataProvider(): array;
}
