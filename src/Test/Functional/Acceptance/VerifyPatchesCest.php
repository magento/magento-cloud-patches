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
     * Runs verify command and accepts both pass (0) and fail (1) exits.
     *
     * verify returns 1 when compliance is not 100%, which is valid for these tests.
     *
     * @param CliTester $I
     * @param string $args
     */
    private function runVerifyCommand(CliTester $I, string $args = ''): void
    {
        $I->assertTrue(
            $I->runDockerComposeCommand(
                sprintf(
                    "run deploy bash -c './vendor/bin/ece-patches verify %s; "
                    . "code=\$?; [ \"\$code\" -eq 0 ] || [ \"\$code\" -eq 1 ]'",
                    trim($args)
                )
            )
        );
    }

    /**
     * Prepares a template and generates docker-compose for verify tests.
     *
     * @param CliTester $I
     * @param Example $data
     */
    private function prepareVerifyEnvironment(CliTester $I, Example $data): void
    {
        $this->prepareTemplate($I, $data['templateVersion'], $data['magentoVersion'] ?? null);
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson($data['variables'])
            )
        );
    }

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
        $this->prepareVerifyEnvironment($I, $data);
        $I->copyFileToWorkDir('files/patches/.gitkeep', 'patches/.gitkeep');
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'));
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'));
        $I->assertTrue($I->runDockerComposeCommand('run deploy ece-command env:config:show'));
        $this->runVerifyCommand($I, '--cloud-only');
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
        $this->prepareVerifyEnvironment($I, $data);
        $I->copyFileToWorkDir('files/patches/.gitkeep', 'patches/.gitkeep');
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'));
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'));
        $this->runVerifyCommand($I, '--format=json --cloud-only');
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
        $this->prepareVerifyEnvironment($I, $data);
        $I->copyFileToWorkDir('files/patches/.gitkeep', 'patches/.gitkeep');
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'));
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'));
        $I->assertTrue(
            $I->runDockerComposeCommand('run deploy bash -c "./vendor/bin/ece-patches status --format=json"')
        );
        $this->runVerifyCommand($I, '--patch-id=MCLOUD-10032 --cloud-only');
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
        $this->prepareVerifyEnvironment($I, $data);
        $I->copyFileToWorkDir('files/patches/.gitkeep', 'patches/.gitkeep');
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'));
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'));
        $this->runVerifyCommand($I, '--cloud-only');
        $I->seeInOutput('Patch Application Verification Report');
    }

    /**
     * Tests verify command help output.
     *
     * @param CliTester $I
     * @param Example $data
     * @dataProvider patchDataProvider
     */
    public function testVerifyHelpOutput(CliTester $I, Example $data): void
    {
        $this->prepareVerifyEnvironment($I, $data);
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'));
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'));
        $I->assertTrue(
            $I->runDockerComposeCommand('run deploy bash -c "./vendor/bin/ece-patches verify --help --cloud-only"')
        );
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
