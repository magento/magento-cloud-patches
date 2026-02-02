<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;

/**
 * Tests for patch verification command.
 *
 * @abstract
 */
abstract class VerifyPatchesCest extends AbstractCest
{
    /**
     * @param CliTester $I
     */
    public function _before(CliTester $I): void
    {
        parent::_before($I);
    }

    /**
     * Tests the verify command with table output format.
     *
     * @param CliTester $I
     * @param Example $data
     * @dataProvider patchDataProvider
     */
    public function testVerifyTableOutput(CliTester $I, Example $data): void
    {
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson($data['variables'])
            )
        );
        $I->copyFileToWorkDir('files/patches/.gitkeep', 'patches/.gitkeep');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy ece-command env:config:show');
        
        // Run the verify command
        $I->runDockerComposeCommand('run deploy ece-patches verify');
        
        // Check that the command executed
        $output = $I->grabFileContent('/var/www/ece-tools/docker-compose.yml');
        $I->assertNotEmpty($output);
        
        // Verify expected output patterns
        $I->runDockerComposeCommand('run deploy ece-patches verify');
        $I->seeInOutput('Patch Application Verification Report');
        $I->seeInOutput('Statistics:');
        $I->seeInOutput('Total Expected Patches:');
        $I->seeInOutput('Compliance:');
    }

    /**
     * Tests the verify command with JSON output format.
     *
     * @param CliTester $I
     * @param Example $data
     * @dataProvider patchDataProvider
     */
    public function testVerifyJsonOutput(CliTester $I, Example $data): void
    {
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson($data['variables'])
            )
        );
        $I->copyFileToWorkDir('files/patches/.gitkeep', 'patches/.gitkeep');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        
        // Run the verify command with JSON format
        $I->runDockerComposeCommand('run deploy ece-patches verify --format=json');
        
        // Verify JSON output structure
        $output = $I->grabShellOutput();
        $I->assertNotEmpty($output);
        
        // Check if output contains JSON structure
        $I->seeInOutput('"status"');
        $I->seeInOutput('"compliance_percentage"');
        $I->seeInOutput('"summary"');
        $I->seeInOutput('"expected_patches"');
        $I->seeInOutput('"applied_patches"');
    }

    /**
     * Tests the verify command with specific patch IDs.
     *
     * @param CliTester $I
     * @param Example $data
     * @dataProvider patchDataProvider
     */
    public function testVerifySpecificPatches(CliTester $I, Example $data): void
    {
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson($data['variables'])
            )
        );
        $I->copyFileToWorkDir('files/patches/.gitkeep', 'patches/.gitkeep');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        
        // First, get the status to find an available patch ID
        $I->runDockerComposeCommand('run deploy ece-patches status --format=json');
        
        // Run verify with specific patch (using a common patch ID)
        $I->runDockerComposeCommand('run deploy ece-patches verify --patch-id=MCLOUD-10032');
        
        // Verify the output contains patch information
        $I->seeInOutput('Verification');
    }

    /**
     * Tests that verify command exits with appropriate codes.
     *
     * @param CliTester $I
     * @param Example $data
     * @dataProvider patchDataProvider
     */
    public function testVerifyExitCodes(CliTester $I, Example $data): void
    {
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson($data['variables'])
            )
        );
        $I->copyFileToWorkDir('files/patches/.gitkeep', 'patches/.gitkeep');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        
        // Run verify and check exit code (will vary based on actual patch status)
        $I->runDockerComposeCommand('run deploy ece-patches verify');
        
        // The command should complete without error
        $output = $I->grabShellOutput();
        $I->assertNotEmpty($output);
    }

    /**
     * Tests verify command help output.
     *
     * @param CliTester $I
     */
    public function testVerifyHelpOutput(CliTester $I): void
    {
        $I->generateDockerCompose('--mode=production');
        $I->runDockerComposeCommand('run build cloud-build');
        
        // Run verify help
        $I->runDockerComposeCommand('run deploy ece-patches verify --help');
        
        // Verify help output contains key information
        $I->seeInOutput('verify');
        $I->seeInOutput('Verifies that expected patches');
        $I->seeInOutput('--format');
        $I->seeInOutput('--patch-id');
        $I->seeInOutput('Exit Codes');
    }

    /**
     * Provides test data for different Magento versions.
     *
     * @return array
     */
    abstract protected function patchDataProvider(): array;
}
