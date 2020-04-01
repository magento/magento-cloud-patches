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
     * @var string
     */
    protected $edition = 'EE';

    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        $I->cleanupWorkDir();
    }

    /**
     * @param \CliTester $I
     * @param string $templateVersion
     * @param string $magentoVersion
     */
    protected function prepareTemplate(\CliTester $I, string $templateVersion, string $magentoVersion): void
    {
        $I->cloneTemplateToWorkDir($templateVersion);
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

        if ($this->edition === 'CE') {
            $I->removeDependencyFromComposer('magento/magento-cloud-metapackage');
            $I->addDependencyToComposer('magento/ece-tools', '^2002.1.0');
            $I->addDependencyToComposer('magento/product-community-edition', $magentoVersion);
        }

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
        $this->prepareTemplate($I, $data['templateVersion'], $data['magentoVersion']);
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
     * @return array
     */
    protected function patchesDataProvider(): array
    {
        return [
            ['templateVersion' => '2.3.3', 'magentoVersion' => '>= 2.3.3 <2.3.4'],
            ['templateVersion' => '2.3.4', 'magentoVersion' => '>= 2.3.4 <2.3.5'],
            ['templateVersion' => 'master', 'magentoVersion' => '@stable'],
        ];
    }

    /**
     * @param \CliTester $I
     */
    public function _after(\CliTester $I): void
    {
        $I->stopEnvironment();
        $I->removeWorkDir();
    }
}
