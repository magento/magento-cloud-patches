<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Functional\Acceptance;

/**
 * @group php73
 */
class AcceptanceCest
{
    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        $I->cleanupWorkDir();
    }

    /**
     * @param \CliTester $I
     * @param string $magentoVersion
     */
    protected function prepareTemplate(\CliTester $I, string $magentoVersion): void
    {
        $I->cloneTemplateToWorkDir($magentoVersion);
        $I->createAuthJson();
        $I->createArtifactsDir();
        $I->createArtifactCurrentTestedCode('patches', '1.0.99');
        $I->addArtifactsRepoToComposer();
        $I->addEceDockerGitRepoToComposer();
        $I->addDependencyToComposer('magento/magento-cloud-patches', '1.0.99');
        $I->addDependencyToComposer(
            'magento/magento-cloud-docker',
            $I->getDependencyVersion('magento/magento-cloud-docker')
        );
        $I->composerUpdate();
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider patchesDataProvider
     */
    public function testPatches(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareTemplate($I, $data['magentoVersion']);
        $this->removeESIfExists($I);
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @param \CliTester $I
     */
    protected function removeESIfExists(\CliTester $I): void
    {
        $services = $I->readServicesYaml();

        if (isset($services['elasticsearch'])) {
            unset($services['elasticsearch']);
            $I->writeServicesYaml($services);

            $app = $I->readAppMagentoYaml();
            unset($app['relationships']['elasticsearch']);
            $I->writeAppMagentoYaml($app);
        }
    }

    /**
     * @return array
     */
    public function patchesDataProvider(): array
    {
        return [
            ['magentoVersion' => '2.3.3'],
            ['magentoVersion' => 'master'],
        ];
    }

    /**
     * @param \CliTester $I
     */
    public function _after(\CliTester $I): void
    {
        $I->stopEnvironment();
        $I->removeDockerCompose();
        $I->removeWorkDir();
    }
}
