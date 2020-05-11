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
     */
    protected function prepareTemplate(\CliTester $I, string $templateVersion): void
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
            $version = $this->getVersionRangeForMagento($I);
            $I->removeDependencyFromComposer('magento/magento-cloud-metapackage');
            $I->addDependencyToComposer('magento/ece-tools', '^2002.1.0');
            $I->addDependencyToComposer('magento/product-community-edition', $version);
        }

        $I->composerUpdate();
    }

    /**
     * @param \CliTester $I
     * @return string
     */
    protected function getVersionRangeForMagento(\CliTester $I): string
    {
        $composer = json_decode(file_get_contents($I->getWorkDirPath() . '/composer.json'), true);

        return $composer['require']['magento/magento-cloud-metapackage'] ?? '';
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider patchesDataProvider
     */
    public function testPatches(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareTemplate($I, $data['templateVersion']);
        $I->assertTrue($I->runEceDockerCommand('build:compose --mode=production'));
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
            ['templateVersion' => '2.3.3'],
            ['templateVersion' => '2.3.4'],
            ['templateVersion' => '2.3.5'],
            ['templateVersion' => 'master'],
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
