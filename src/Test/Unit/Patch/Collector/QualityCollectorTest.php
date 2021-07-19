<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Collector;

use Magento\CloudPatches\App\GenericException;
use Magento\CloudPatches\Composer\Package;
use Magento\CloudPatches\Composer\QualityPackage;
use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\Collector\GetPatchesConfigInterface;
use Magento\CloudPatches\Patch\Collector\GetSupportPatchesConfig;
use Magento\CloudPatches\Patch\Collector\SupportCollector;
use Magento\CloudPatches\Patch\Collector\ValidatePatchesConfig;
use Magento\CloudPatches\Patch\Data\Patch;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\PatchBuilder;
use Magento\CloudPatches\Patch\PatchIntegrityException;
use Magento\CloudPatches\Patch\SourceProvider;
use Magento\CloudPatches\Patch\SourceProviderException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class QualityCollectorTest extends TestCase
{
    const QUALITY_PATCH_DIR = 'quality/patch/dir';

    /**
     * @var SupportCollector
     */
    private $collector;

    /**
     * @var PatchBuilder|MockObject
     */
    private $patchBuilder;

    /**
     * @var Package|MockObject
     */
    private $package;

    /**
     * @var QualityPackage|MockObject
     */
    private $qualityPackage;

    /**
     * @var \Magento\CloudPatches\Patch\Collector\GetPatchesConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $patchesConfig;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->package = $this->createMock(Package::class);
        $this->qualityPackage = $this->createMock(QualityPackage::class);
        $this->patchBuilder = $this->createMock(PatchBuilder::class);
        $this->patchesConfig = $this->createMock(GetPatchesConfigInterface::class);

        $this->collector = new SupportCollector(
            $this->package,
            $this->qualityPackage,
            $this->patchBuilder,
            $this->patchesConfig
        );
    }

    /**
     * Tests collecting patches - valid configuration
     */
    public function testCollectSuccessful()
    {
        $validConfig = require __DIR__ . '/Fixture/quality_config_valid.php';
        $this->patchesConfig->expects($this->once())
            ->method('execute')
            ->willReturn($validConfig);
        $this->qualityPackage->method('getPatchesDirectoryPath')
            ->willReturn(self::QUALITY_PATCH_DIR);

        $this->package->method('matchConstraint')
            ->willReturnMap([
                ['magento/magento2-base', '2.1.4 - 2.1.14', false],
                ['magento/magento2-base', '2.2.0 - 2.2.5', true],
                ['magento/magento2-ee-base', '2.2.0 - 2.2.5', true],
             ]);

        $this->package->method('matchConstraint')
            ->willReturnMap([
                ['magento/magento2-base', '2.1.4 - 2.1.14', false],
                ['magento/magento2-base', '2.2.0 - 2.2.5', true],
                ['magento/magento2-ee-base', '2.2.0 - 2.2.5', true],
            ]);

        $this->patchBuilder->expects($this->exactly(3))
            ->method('setId')
            ->withConsecutive(['MDVA-2470'], ['MDVA-2470'], ['MDVA-2033']);
        $this->patchBuilder->expects($this->exactly(3))
            ->method('setTitle')
            ->withConsecutive(
                ['Fix asset locker race condition when using Redis'],
                ['Fix asset locker race condition when using Redis'],
                ['Allow DB dumps done with the support module to complete']
            );
        $this->patchBuilder->expects($this->exactly(3))
            ->method('setFilename')
            ->withConsecutive(
                ['MDVA-2470__fix_asset_locking_race_condition__2.2.0.patch'],
                ['MDVA-2470__fix_asset_locking_race_condition__2.2.0_ee.patch'],
                ['MDVA-2033__prevent_deadlock_during_db_dump__2.2.0.patch']
            );
        $this->patchBuilder->expects($this->exactly(3))
            ->method('setPath')
            ->withConsecutive(
                [self::QUALITY_PATCH_DIR . '/MDVA-2470__fix_asset_locking_race_condition__2.2.0.patch'],
                [self::QUALITY_PATCH_DIR . '/MDVA-2470__fix_asset_locking_race_condition__2.2.0_ee.patch'],
                [self::QUALITY_PATCH_DIR . '/MDVA-2033__prevent_deadlock_during_db_dump__2.2.0.patch']
            );
        $this->patchBuilder->expects($this->exactly(3))
            ->method('setType')
            ->withConsecutive(
                [PatchInterface::TYPE_OPTIONAL],
                [PatchInterface::TYPE_OPTIONAL],
                [PatchInterface::TYPE_OPTIONAL]
            );
        $this->patchBuilder->expects($this->exactly(3))
            ->method('setPackageName')
            ->withConsecutive(
                ['magento/magento2-base'],
                ['magento/magento2-ee-base'],
                ['magento/magento2-ee-base']
            );
        $this->patchBuilder->expects($this->exactly(3))
            ->method('setPackageConstraint')
            ->withConsecutive(
                ['2.2.0 - 2.2.5'],
                ['2.2.0 - 2.2.5'],
                ['2.2.0 - 2.2.5']
            );
        $this->patchBuilder->expects($this->exactly(3))
            ->method('setRequire')
            ->withConsecutive([[]], [[]], [['MC-11111', 'MC-22222']]);

        $this->patchBuilder->expects($this->exactly(3))
            ->method('setReplacedWith')
            ->withConsecutive([''], [''], ['MC-33333']);
        $this->patchBuilder->expects($this->exactly(3))
            ->method('setDeprecated')
            ->withConsecutive([false], [false], [true]);
        $this->patchBuilder->expects($this->exactly(3))
            ->method('build')
            ->willReturn($this->createMock(Patch::class));

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

        $sourceProvider = $this->createMock(SourceProvider::class);
        $sourceProvider->expects($this->once())->method('getSupportPatches')->willReturn($config);

        $this->patchesConfig = new GetSupportPatchesConfig(
            $sourceProvider,
            new ValidatePatchesConfig()
        );

        $this->patchBuilder->expects($this->never())
            ->method('build');

        $this->expectException(CollectorException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->collector = new SupportCollector(
            $this->package,
            $this->qualityPackage,
            $this->patchBuilder,
            $this->patchesConfig
        );

        $this->collector->collect();
    }

    /**
     * Tests case when patch factory can't create a patch for some reason.
     */
    public function testPatchIntegrityException()
    {
        $validConfig = require __DIR__ . '/Fixture/quality_config_valid.php';
        $this->patchesConfig->expects($this->once())
            ->method('execute')
            ->willReturn($validConfig);

        $this->package->method('matchConstraint')
            ->willReturnMap([
                ['magento/magento2-base', '2.1.4 - 2.1.14', false],
                ['magento/magento2-base', '2.2.0 - 2.2.5', true],
                ['magento/magento2-ee-base', '2.2.0 - 2.2.5', true],
            ]);

        $this->patchBuilder->method('build')
            ->willThrowException(new PatchIntegrityException(''));

        $this->expectException(CollectorException::class);
        $this->collector->collect();
    }

    /**
     * Tests case when configuration can't be retrieved from source.
     */
    public function testSourceProviderException()
    {
        $this->patchesConfig->expects($this->once())
            ->method('execute')
            ->willThrowException(new CollectorException(''));

        $this->patchBuilder->expects($this->never())
            ->method('build');

        $this->expectException(CollectorException::class);
        $this->collector->collect();
    }
}
