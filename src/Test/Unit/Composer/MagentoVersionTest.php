<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Composer;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableRepositoryInterface;
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
     * @var WritableRepositoryInterface|MockObject
     */
    private $repository;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->repository = $this->getMockForAbstractClass(WritableRepositoryInterface::class);
        $repositoryManager = $this->createMock(RepositoryManager::class);
        $repositoryManager->method('getLocalRepository')
            ->willReturn($this->repository);

        /** @var Composer $composer */
        $composer = $this->createMock(Composer::class);
        $composer->method('getRepositoryManager')
            ->willReturn($repositoryManager);

        $this->magentoVersion = new MagentoVersion($composer);
    }

    /**
     * Tests retrieving Magento version and edition.
     *
     * @param bool $ce
     * @param bool $ee
     * @param bool $b2b
     * @param string $expectedResult
     *
     * @dataProvider getDataProvider
     */
    public function testGet(bool $ce, bool $ee, bool $b2b, string $expectedResult)
    {
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
            ['CE' => false, 'EE' => false, 'B2B' => false, 'Magento 2 is not installed'],
            ['CE' => true, 'EE' => true, 'B2B' => false, 'Magento 2 Enterprise Edition, version ' . self::VERSION],
            ['CE' => true, 'EE' => false, 'B2B' => true, 'Magento 2 B2B Edition, version ' . self::VERSION],
            ['CE' => true, 'EE' => false, 'B2B' => false, 'Magento 2 Community Edition, version ' . self::VERSION],
        ];
    }
}
