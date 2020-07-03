<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Collector;

use Magento\CloudPatches\Composer\Package;
use Magento\CloudPatches\Filesystem\DirectoryList;
use Magento\CloudPatches\Patch\Collector\CloudCollector;
use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\Data\Patch;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Environment;
use Magento\CloudPatches\Patch\PatchFactory;
use Magento\CloudPatches\Patch\PatchIntegrityException;
use Magento\CloudPatches\Patch\SourceProvider;
use Magento\CloudPatches\Patch\SourceProviderException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class CloudCollectorTest extends TestCase
{
    const CLOUD_PATCH_DIR = 'cloud/patch/dir';

    /**
     * @var CloudCollector
     */
    private $collector;

    /**
     * @var PatchFactory|MockObject
     */
    private $patchFactory;

    /**
     * @var SourceProvider|MockObject
     */
    private $sourceProvider;

    /**
     * @var Package|MockObject
     */
    private $package;

    /**
     * @var Environment|MockObject
     */
    private $environment;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryList;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->patchFactory = $this->createMock(PatchFactory::class);
        $this->sourceProvider = $this->createMock(SourceProvider::class);
        $this->package = $this->createMock(Package::class);
        $this->environment = $this->createMock(Environment::class);
        $this->directoryList = $this->createMock(DirectoryList::class);

        $this->collector = new CloudCollector(
            $this->patchFactory,
            $this->sourceProvider,
            $this->package,
            $this->directoryList,
            $this->environment
        );
    }

    /**
     * Tests collecting patches - valid configuration
     *
     * @param bool $isCloud
     * @param string $expectedType
     * @dataProvider collectDataProvider
     */
    public function testCollectSuccessful(bool $isCloud, string $expectedType)
    {
        $validConfig = require __DIR__ . '/Fixture/cloud_config_valid.php';
        $this->sourceProvider->expects($this->once())
            ->method('getCloudPatches')
            ->willReturn($validConfig);
        $this->directoryList->method('getPatches')
            ->willReturn(self::CLOUD_PATCH_DIR);
        $this->environment->method('isCloud')
            ->willReturn($isCloud);

        $this->package->method('matchConstraint')
            ->willReturnMap([
                ['magento/magento2-base', '2.1.4 - 2.1.14', false],
                ['magento/magento2-base', '2.2.0 - 2.2.5', true],
                ['magento/magento2-ee-base', '2.2.0 - 2.2.5', true],
             ]);

        $this->patchFactory->expects($this->exactly(3))
            ->method('create')
            ->withConsecutive(
                [
                    'MDVA-2470',
                    'Fix asset locker race condition when using Redis',
                    'MDVA-2470__fix_asset_locking_race_condition__2.2.0.patch',
                    self::CLOUD_PATCH_DIR . '/MDVA-2470__fix_asset_locking_race_condition__2.2.0.patch',
                    $expectedType,
                    'magento/magento2-base',
                    '2.2.0 - 2.2.5'
                ],
                [
                    'MDVA-2470',
                    'Fix asset locker race condition when using Redis EE',
                    'MDVA-2470__fix_asset_locking_race_condition__2.2.0_ee.patch',
                    self::CLOUD_PATCH_DIR . '/MDVA-2470__fix_asset_locking_race_condition__2.2.0_ee.patch',
                    $expectedType,
                    'magento/magento2-ee-base',
                    '2.2.0 - 2.2.5'
                ],
                [
                    'MAGECLOUD-2033',
                    'Allow DB dumps done with the support module to complete',
                    'MAGECLOUD-2033__prevent_deadlock_during_db_dump__2.2.0.patch',
                    self::CLOUD_PATCH_DIR . '/MAGECLOUD-2033__prevent_deadlock_during_db_dump__2.2.0.patch',
                    $expectedType,
                    'magento/magento2-ee-base',
                    '2.2.0 - 2.2.5'
                ]
            )->willReturn(
                $this->createMock(Patch::class)
            );

        $this->assertTrue(is_array($this->collector->collect()));
    }

    /**
     * @return array
     */
    public function collectDataProvider(): array
    {
        return [
            ['isCloud' => false, 'expectedType' => PatchInterface::TYPE_OPTIONAL],
            ['isCloud' => true, 'expectedType' => PatchInterface::TYPE_REQUIRED]
        ];
    }

    /**
     * Tests collecting patches - invalid configuration, patch filename
     *
     * @param array $invalidConfig
     * @dataProvider invalidPatchFilenameDataProvider
     */
    public function testInvalidConfigurationPatchFilename(array $invalidConfig)
    {
        $this->sourceProvider->expects($this->once())
            ->method('getCloudPatches')
            ->willReturn($invalidConfig);

        $this->package->expects($this->never())
            ->method('matchConstraint');

        $this->expectException(CollectorException::class);
        $this->collector->collect();
    }

    /**
     * @return array
     */
    public function invalidPatchFilenameDataProvider(): array
    {
        return [
            [$this->createConfig('fix_asset_locking_race_condition__2.1.4.patch')],
            [$this->createConfig('MDVA-2470__fix_asset_locking_race_condition.patch')],
            [$this->createConfig('MDVA-2470_fix_asset_locking_race_condition__2.1.4.patch')],
            [$this->createConfig('MDVA-2470__fix_asset_locking_race_condition_2.1.4.patch')],
        ];
    }

    /**
     * Returns config.
     *
     * @param string $filename
     * @return array
     */
    private function createConfig(string $filename): array
    {
        return [
            'magento/magento2-base' => [
                'Fix asset locker race condition when using Redis' => [
                    '2.1.4 - 2.1.14' => $filename
                ]
            ]
        ];
    }

    /**
     * Tests collecting patches - invalid configuration, patch title section
     *
     * @param array $config
     * @dataProvider invalidTitleSectionDataProvider
     */
    public function testInvalidConfigurationTitleSection(array $config)
    {
        $this->sourceProvider->expects($this->once())
            ->method('getCloudPatches')
            ->willReturn($config);

        $this->patchFactory->expects($this->never())
            ->method('create');

        $this->expectException(CollectorException::class);
        $this->collector->collect();
    }

    /**
     * @return array
     */
    public function invalidTitleSectionDataProvider(): array
    {
        return [
            [
                [
                    'magento/magento2-base' => [
                        'Fix asset locker race condition when using Redis' => [],
                    ]
                ]
            ],
            [
                [
                    'magento/magento2-base' => [
                        'Fix asset locker race condition when using Redis' => 'String instead of array',
                    ]
                ]
            ]
        ];
    }

    /**
     * Tests case when patch factory can't create a patch for some reason.
     */
    public function testPatchIntegrityException()
    {
        $validConfig = require __DIR__ . '/Fixture/cloud_config_valid.php';
        $this->sourceProvider->expects($this->once())
            ->method('getCloudPatches')
            ->willReturn($validConfig);

        $this->package->method('matchConstraint')
            ->willReturnMap([
                ['magento/magento2-base', '2.1.4 - 2.1.14', false],
                ['magento/magento2-base', '2.2.0 - 2.2.5', true],
                ['magento/magento2-ee-base', '2.2.0 - 2.2.5', true],
            ]);

        $this->patchFactory->method('create')
            ->willThrowException(new PatchIntegrityException(''));

        $this->expectException(CollectorException::class);
        $this->collector->collect();
    }

    /**
     * Tests case when configuration can't be retrieved from source.
     */
    public function testSourceProviderException()
    {
        $this->sourceProvider->expects($this->once())
            ->method('getCloudPatches')
            ->willThrowException(new SourceProviderException(''));

        $this->patchFactory->expects($this->never())
            ->method('create');

        $this->expectException(CollectorException::class);
        $this->collector->collect();
    }
}
