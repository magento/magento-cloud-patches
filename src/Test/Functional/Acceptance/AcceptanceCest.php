<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php74
 */
class AcceptanceCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        parent::_before($I);
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider patchesDataProvider
     */
    public function testPatches(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareTemplate($I, $data['templateVersion'], $data['magentoVersion'] ?? null);
        $I->copyFileToWorkDir('files/patches/.apply_quality_patches.env.yaml', '.magento.env.yaml');
        $I->runEceDockerCommand(sprintf(
            'build:compose --mode=production --env-vars="%s"',
            $this->convertEnvFromArrayToJson(['MAGENTO_CLOUD_PROJECT' => 'travis-testing'])
        ));
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'));
        $I->assertTrue($I->startEnvironment());
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'));
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'));
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @return array
     */
    protected function patchesDataProvider(): array
    {
        return [
            ['templateVersion' => '2.4.0', 'magentoVersion' => '2.4.0'],
            ['templateVersion' => 'master'],
        ];
    }
}
