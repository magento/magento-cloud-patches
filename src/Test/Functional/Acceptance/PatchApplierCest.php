<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * @group php82
 */
class PatchApplierCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        parent::_before($I);

        $this->prepareTemplate($I, '2.4.6');
        $I->copyFileToWorkDir('files/debug_logging/.magento.env.yaml', '.magento.env.yaml');
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testApplyingPatch(\CliTester $I): void
    {
        $I->generateDockerCompose('--mode=production');
        $I->copyFileToWorkDir('files/patches/target_file.md', 'target_file.md');
        $I->copyFileToWorkDir('files/patches/patch.patch', 'm2-hotfixes/patch.patch');

        // For this test, only the build phase is enough
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();

        $targetFile = $I->grabFileContent('/target_file.md', Docker::BUILD_CONTAINER);
        $I->assertStringContainsString('# Hello Magento', $targetFile);
        $I->assertStringContainsString('## Additional Info', $targetFile);
        $log = $I->grabFileContent('/var/log/cloud.log', Docker::BUILD_CONTAINER);
        $I->assertStringContainsString('Patch ../m2-hotfixes/patch.patch has been applied', $log);
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testApplyingExistingPatch(\CliTester $I): void
    {
        $I->generateDockerCompose('--mode=production');
        $I->copyFileToWorkDir('files/patches/target_file_applied_patch.md', 'target_file.md');
        $I->copyFileToWorkDir('files/patches/patch.patch', 'm2-hotfixes/patch.patch');

        // For this test, only the build phase is enough
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();

        $targetFile = $I->grabFileContent('/target_file.md', Docker::BUILD_CONTAINER);
        $I->assertStringContainsString('# Hello Magento', $targetFile);
        $I->assertStringContainsString('## Additional Info', $targetFile);
        $I->assertStringContainsString(
            'Patch ../m2-hotfixes/patch.patch was already applied',
            $I->grabFileContent('/var/log/cloud.log', Docker::BUILD_CONTAINER)
        );
    }
}
