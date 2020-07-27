<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Composer\QualityPackage;
use Magento\CloudPatches\Filesystem\DirectoryList;
use Magento\CloudPatches\Filesystem\FileList;
use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Filesystem\FileSystemException;
use Magento\CloudPatches\Patch\SourceProvider;
use Magento\CloudPatches\Patch\SourceProviderException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class SourceProviderTest extends TestCase
{
    /**
     * @var SourceProvider
     */
    private $sourceProvider;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystem;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryList;

    /**
     * @var QualityPackage|MockObject
     */
    private $qualityPackage;

    /**
     * @var FileList|MockObject
     */
    private $filelist;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->filelist = $this->createMock(FileList::class);
        $this->directoryList = $this->createMock(DirectoryList::class);
        $this->qualityPackage = $this->createMock(QualityPackage::class);

        $this->sourceProvider = new SourceProvider(
            $this->filesystem,
            $this->filelist,
            $this->directoryList,
            $this->qualityPackage
        );
    }

    /**
     * Tests retrieving Cloud patch configuration.
     */
    public function testGetCloudPatches()
    {
        $configPath = '/cloud/patches.json';
        $configSource = require __DIR__ . '/Collector/Fixture/cloud_config_valid.php';
        $jsonConfig = json_encode($configSource);

        $this->filelist->expects($this->once())
            ->method('getPatches')
            ->willReturn($configPath);

        $this->filesystem->expects($this->once())
            ->method('get')
            ->with($configPath)
            ->willReturn($jsonConfig);

        $this->assertEquals($configSource, $this->sourceProvider->getCloudPatches());
    }

    /**
     * Tests retrieving Quality patch configuration.
     */
    public function testGetQualityPatches()
    {
        $configPath = '/quality/patches.json';
        $configSource = require __DIR__ . '/Collector/Fixture/quality_config_valid.php';
        $jsonConfig = json_encode($configSource);

        $this->qualityPackage->expects($this->once())
            ->method('getPatchesConfig')
            ->willReturn($configPath);

        $this->filesystem->expects($this->once())
            ->method('get')
            ->with($configPath)
            ->willReturn($jsonConfig);

        $this->assertEquals($configSource, $this->sourceProvider->getQualityPatches());
    }

    /**
     * Tests retrieving Quality patch configuration when config path is null.
     *
     * Case when magento/quality-patches package is not installed.
     */
    public function testGetQualityPatchesWithNullConfigPath()
    {
        $this->qualityPackage->expects($this->once())
            ->method('getPatchesConfig')
            ->willReturn(null);

        $this->filesystem->expects($this->never())
            ->method('get');

        $this->assertEquals([], $this->sourceProvider->getQualityPatches());
    }

    /**
     * Tests retrieving Local patch configuration.
     */
    public function testGetLocalPatches()
    {
        $this->directoryList->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/Collector/Fixture');

        $expectedResult = [
            __DIR__ . '/Collector/Fixture/' . SourceProvider::HOT_FIXES_DIR . '/patch1.patch',
            __DIR__ . '/Collector/Fixture/' . SourceProvider::HOT_FIXES_DIR . '/patch2.patch'
        ];

        $this->assertEquals($expectedResult, $this->sourceProvider->getLocalPatches());
    }

    /**
     * Tests retrieving Quality patch configuration with filesystem exception.
     */
    public function testGetQualityPatchesFilesystemException()
    {
        $configPath = '/quality/patches.json';

        $this->qualityPackage->expects($this->once())
            ->method('getPatchesConfig')
            ->willReturn($configPath);

        $this->filesystem->expects($this->once())
            ->method('get')
            ->willThrowException(new FileSystemException(''));

        $this->expectException(SourceProviderException::class);
        $this->sourceProvider->getQualityPatches();
    }

    /**
     * Tests retrieving Quality patch configuration with json decoding exception.
     */
    public function testGetQualityPatchesJsonException()
    {
        $configPath = '/quality/patches.json';

        $this->qualityPackage->expects($this->once())
            ->method('getPatchesConfig')
            ->willReturn($configPath);

        $this->filesystem->expects($this->once())
            ->method('get')
            ->with($configPath)
            ->willReturn('Invalid config format');

        $this->expectException(SourceProviderException::class);
        $this->expectExceptionMessageRegExp('/Unable to unserialize patches configuration/');

        $this->sourceProvider->getQualityPatches();
    }
}
