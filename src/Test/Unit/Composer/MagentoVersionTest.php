<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Composer;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Magento\CloudPatches\Composer\MagentoVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class MagentoVersionTest extends TestCase
{
    const VERSION = '2.3.5';

    /**
     * @var WritableRepositoryInterface|InstalledRepositoryInterface|MockObject
     */
    private $repository;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var RootPackageInterface|MockObject
     */
    private $rootPackage;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->repository = $this->getMockForAbstractClass(
            (version_compare(PHP_VERSION, '7.3') == -1)
                ? WritableRepositoryInterface::class
                : InstalledRepositoryInterface::class
        );
        $this->rootPackage = $this->getMockForAbstractClass(RootPackageInterface::class);
        $repositoryManager = $this->createMock(RepositoryManager::class);
        $repositoryManager->method('getLocalRepository')
            ->willReturn($this->repository);

        /** @var Composer $composer */
        $composer = $this->createMock(Composer::class);
        $composer->method('getRepositoryManager')
            ->willReturn($repositoryManager);
        $composer->method('getPackage')
            ->willReturn($this->rootPackage);

        $this->magentoVersion = new MagentoVersion($composer);
    }

    /**
     * Tests retrieving Magento version and edition.
     *
     * @param bool $ce
     * @param bool $ee
     * @param bool $b2b
     * @param string $rootPackage
     * @param string $expectedResult
     *
     * @dataProvider getDataProvider
     */
    public function testGet(bool $ce, bool $ee, bool $b2b, string $rootPackage, string $expectedResult)
    {
        $this->rootPackage->method('getName')
            ->willReturn($rootPackage);
        $this->rootPackage->method('getVersion')
            ->willReturn(self::VERSION);

        $package = $this->getMockForAbstractClass(PackageInterface::class);
        $package->method('getVersion')
            ->willReturn(self::VERSION);
        $this->repository->method('findPackage')
            ->willReturnMap([
                ['magento/magento2-base', '*', $ce ? $package : null],
                ['magento/magento2-ee-base', '*', $ee ? $package : null],
                ['magento/magento2-b2b-base', '*', $b2b ? $package : null],
            ]);

        $this->assertEquals($expectedResult, $this->magentoVersion->get());
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [
                'CE' => false,
                'EE' => false,
                'B2B' => false,
                'gitPackage' => '',
                'Magento 2 is not installed'
            ],
            [
                'CE' => true,
                'EE' => true,
                'B2B' => false,
                'gitPackage' => '',
                'Magento 2 Enterprise Edition, version ' . self::VERSION
            ],
            [
                'CE' => true,
                'EE' => false,
                'B2B' => true,
                'gitPackage' => '',
                'Magento 2 B2B Edition, version ' . self::VERSION
            ],
            [
                'CE' => true,
                'EE' => false,
                'B2B' => false,
                'gitPackage' => '',
                'Magento 2 Community Edition, version ' . self::VERSION
            ],
            [
                'CE' => false,
                'EE' => false,
                'B2B' => false,
                'gitPackage' => 'magento/magento2ce',
                'Git-based: Magento 2 Community Edition, version ' . self::VERSION
            ],
            [
                'CE' => false,
                'EE' => false,
                'B2B' => false,
                'gitPackage' => 'magento/magento2ee',
                'Git-based: Magento 2 Enterprise Edition, version ' . self::VERSION
            ],
        ];
    }

    /**
     * Tests Magento git-version identifying .
     *
     * @param string $rootPackageName
     * @param bool $expectedResult
     * @dataProvider isGitBasedDataProvider
     */
    public function testIsGitBased(string $rootPackageName, bool $expectedResult)
    {
        $this->rootPackage->method('getName')
            ->willReturn($rootPackageName);

        $this->assertEquals($expectedResult, $this->magentoVersion->isGitBased());
    }

    /**
     * @return array
     */
    public function isGitBasedDataProvider(): array
    {
        return [
            ['rootPackageName' => 'magento/magento2ce', 'expectedResult' => true],
            ['rootPackageName' => 'magento/magento2ee', 'expectedResult' => true],
            ['rootPackageName' => 'magento/magento2-ce-base', 'expectedResult' => false]
        ];
    }

    /**
     * Tests package matching using composer root package.
     *
     * @param string $rootPackageName
     * @param string $rootPackageVersion
     * @param string $testPackageName
     * @param string $testPackageVersion
     * @param bool $expectedResult
     * @dataProvider matchPackageGitProvider
     */
    public function testMatchPackageGit(
        string $rootPackageName,
        string $rootPackageVersion,
        string $testPackageName,
        string $testPackageVersion,
        bool $expectedResult
    ) {
        $this->rootPackage->method('getName')
            ->willReturn($rootPackageName);
        $this->rootPackage->method('getVersion')
            ->willReturn($rootPackageVersion);

        $this->assertEquals(
            $expectedResult,
            $this->magentoVersion->matchPackageGit($testPackageName, $testPackageVersion)
        );
    }

    /**
     * @return array
     */
    public function matchPackageGitProvider(): array
    {
        return [
            [
                'magento/magento2ce',
                '2.3.5',
                'magento/magento2-base',
                '<=2.3.5 <2.3.6',
                'expectedResult' => true
            ],
            [
                'magento/magento2ce',
                '2.3.5',
                'magento/magento2-base',
                '<2.3.5',
                'expectedResult' => false
            ],
            [
                'magento/magento2ce',
                '2.3.5',
                'magento/magento2-ee-base',
                '<=2.3.5 <2.3.6',
                'expectedResult' => false
            ],
            [
                'magento/magento2ee',
                '2.3.5',
                'magento/magento2-ee-base',
                '<=2.3.5 <2.3.6',
                'expectedResult' => true
            ],
        ];
    }
}
