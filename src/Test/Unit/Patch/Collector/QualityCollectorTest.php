<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Collector;

use Magento\CloudPatches\Composer\Package;
use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\Collector\QualityCollector;
use Magento\CloudPatches\Patch\Data\Patch;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\PatchFactory;
use Magento\CloudPatches\Patch\PatchIntegrityException;
use Magento\CloudPatches\Patch\SourceProvider;
use Magento\CloudPatches\Patch\SourceProviderException;
use Magento\QualityPatches\Info as QualityPatchesInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class QualityCollectorTest extends TestCase
{
    const QUALITY_PATCH_DIR = 'quality/patch/dir';

    /**
     * @var QualityCollector
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
     * @var QualityPatchesInfo|MockObject
     */
    private $qualityPatchesInfo;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->patchFactory = $this->createMock(PatchFactory::class);
        $this->sourceProvider = $this->createMock(SourceProvider::class);
        $this->package = $this->createMock(Package::class);
        $this->qualityPatchesInfo = $this->createMock(QualityPatchesInfo::class);

        $this->collector = new QualityCollector(
            $this->patchFactory,
            $this->sourceProvider,
            $this->package,
            $this->qualityPatchesInfo
        );
    }

    /**
     * Tests collecting patches - valid configuration
     */
    public function testCollectSuccessful()
    {
        $validConfig = require __DIR__ . '/Fixture/quality_config_valid.php';
        $this->sourceProvider->expects($this->once())
            ->method('getQualityPatches')
            ->willReturn($validConfig);
        $this->qualityPatchesInfo->method('getPatchesDirectory')
            ->willReturn(self::QUALITY_PATCH_DIR);

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
                    self::QUALITY_PATCH_DIR . '/MDVA-2470__fix_asset_locking_race_condition__2.2.0.patch',
                    PatchInterface::TYPE_OPTIONAL,
                    'magento/magento2-base',
                    '2.2.0 - 2.2.5'
                ],
                [
                    'MDVA-2470',
                    'Fix asset locker race condition when using Redis EE',
                    'MDVA-2470__fix_asset_locking_race_condition__2.2.0_ee.patch',
                    self::QUALITY_PATCH_DIR . '/MDVA-2470__fix_asset_locking_race_condition__2.2.0_ee.patch',
                    PatchInterface::TYPE_OPTIONAL,
                    'magento/magento2-ee-base',
                    '2.2.0 - 2.2.5'
                ],
                [
                    'MDVA-2033',
                    'Allow DB dumps done with the support module to complete',
                    'MDVA-2033__prevent_deadlock_during_db_dump__2.2.0.patch',
                    self::QUALITY_PATCH_DIR . '/MDVA-2033__prevent_deadlock_during_db_dump__2.2.0.patch',
                    PatchInterface::TYPE_OPTIONAL,
                    'magento/magento2-ee-base',
                    '2.2.0 - 2.2.5',
                    ['MC-11111', 'MC-22222'],
                    'MC-33333',
                    true
                ]
            )->willReturn(
                $this->createMock(Patch::class)
            );

        $this->assertTrue(is_array($this->collector->collect()));
    }

    /**
     * Tests collecting patches - invalid configuration
     */
    public function testInvalidConfiguration()
    {
        $config = require __DIR__ . '/Fixture/quality_config_invalid.php';

        $expectedExceptionMessage = 'Patch MDVA-2033 has invalid configuration:' .
            PHP_EOL . ' - Property \'file\' is not found in \'2.2.0 - 2.2.5\'' .
            PHP_EOL . ' - Property \'require\' from \'2.2.0 - 2.2.5\' should have an array type' .
            PHP_EOL . ' - Property \'replaced-with\' from \'2.2.0 - 2.2.5\' should have a string type' .
            PHP_EOL . ' - Property \'deprecated\' from \'2.2.0 - 2.2.5\' should have a boolean type';

        $this->sourceProvider->expects($this->once())
            ->method('getQualityPatches')
            ->willReturn($config);

        $this->patchFactory->expects($this->never())
            ->method('create');

        $this->expectException(CollectorException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->collector->collect();
    }

    /**
     * Tests case when patch factory can't create a patch for some reason.
     */
    public function testPatchIntegrityException()
    {
        $validConfig = require __DIR__ . '/Fixture/quality_config_valid.php';
        $this->sourceProvider->expects($this->once())
            ->method('getQualityPatches')
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
            ->method('getQualityPatches')
            ->willThrowException(new SourceProviderException(''));

        $this->patchFactory->expects($this->never())
            ->method('create');

        $this->expectException(CollectorException::class);
        $this->collector->collect();
    }
}
