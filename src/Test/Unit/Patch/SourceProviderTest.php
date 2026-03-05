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
use Magento\CloudPatches\Filesystem\JsonConfigReader;
use Magento\CloudPatches\Patch\SourceProvider;
use Magento\CloudPatches\Patch\SourceProviderException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class SourceProviderTest extends TestCase
{
    /**
     * @var SourceProvider
     */
    private SourceProvider $sourceProvider;

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
     * @var JsonConfigReader|MockObject
     */
    private $jsonConfigReader;

    /**
     * Sets up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->filelist = $this->createMock(FileList::class);
        $this->directoryList = $this->createMock(DirectoryList::class);
        $this->qualityPackage = $this->createMock(QualityPackage::class);
        $this->jsonConfigReader = $this->createMock(JsonConfigReader::class);

        $this->sourceProvider = new SourceProvider(
            $this->filelist,
            $this->directoryList,
            $this->qualityPackage,
            $this->jsonConfigReader
        );
    }

    /**
     * Tests retrieving Cloud patch configuration.
     *
     * @return void
     * @throws SourceProviderException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetCloudPatches(): void
    {
        $configPath = '/cloud/patches.json';
        $configSource = require __DIR__ . '/Collector/Fixture/cloud_config_valid.php';

        $this->filelist->expects($this->once())
            ->method('getPatches')
            ->willReturn($configPath);

        $this->jsonConfigReader->expects($this->once())
            ->method('read')
            ->with($configPath)
            ->willReturn($configSource);

        $this->assertEquals($configSource, $this->sourceProvider->getCloudPatches());
    }

    /**
     * Tests retrieving Quality patch configuration.
     *
     * @return void
     * @throws SourceProviderException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetQualityPatches(): void
    {
        $configPath = '/quality/patches.json';
        $configSource = require __DIR__ . '/Collector/Fixture/quality_config_valid.php';

        $this->qualityPackage->expects($this->once())
            ->method('getSupportPatchesConfigPath')
            ->willReturn($configPath);

        $this->jsonConfigReader->expects($this->once())
            ->method('read')
            ->with($configPath)
            ->willReturn($configSource);

        $this->assertEquals($configSource, $this->sourceProvider->getSupportPatches());
    }

    /**
     * Tests retrieving Quality patch configuration when config path is null.
     * Case when magento/quality-patches package is not installed.
     *
     * @return void
     * @throws SourceProviderException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetQualityPatchesWithNullConfigPath(): void
    {
        $this->qualityPackage->expects($this->once())
            ->method('getSupportPatchesConfigPath')
            ->willReturn(null);

        $this->assertEquals([], $this->sourceProvider->getSupportPatches());
    }

    /**
     * Tests retrieving Local patch configuration.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetLocalPatches(): void
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
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetQualityPatchesFilesystemException(): void
    {
        $configPath = '/quality/patches.json';

        $this->qualityPackage->expects($this->once())
            ->method('getSupportPatchesConfigPath')
            ->willReturn($configPath);

        $this->jsonConfigReader->expects($this->once())
            ->method('read')
            ->willThrowException(new SourceProviderException(''));

        $this->expectException(SourceProviderException::class);
        $this->sourceProvider->getSupportPatches();
    }

    /**
     * Tests retrieving Community patch configuration.
     *
     * @return void
     * @throws SourceProviderException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetCommunityPatches(): void
    {
        $configPath = '/community/patches.json';
        $configSource = [
            'community-patch-1' => [
                'title' => 'Community Patch',
                'packages' => [
                    'magento/module-catalog' => [
                        '1.0.0' => [
                            'file' => 'community-patch.diff'
                        ]
                    ]
                ]
            ]
        ];

        $this->qualityPackage->expects($this->once())
            ->method('getCommunityPatchesConfigPath')
            ->willReturn($configPath);

        $this->jsonConfigReader->expects($this->once())
            ->method('read')
            ->with($configPath)
            ->willReturn($configSource);

        $this->assertEquals($configSource, $this->sourceProvider->getCommunityPatches());
    }

    /**
     * Tests retrieving Community patch configuration when config path is null.
     * Case when magento/quality-patches package is not installed or community config is not available.
     *
     * @return void
     * @throws SourceProviderException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetCommunityPatchesWithNullConfigPath(): void
    {
        $this->qualityPackage->expects($this->once())
            ->method('getCommunityPatchesConfigPath')
            ->willReturn(null);

        $this->jsonConfigReader->expects($this->never())
            ->method('read');

        $this->assertEquals([], $this->sourceProvider->getCommunityPatches());
    }

    /**
     * Tests retrieving Community patch configuration with filesystem exception.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testGetCommunityPatchesFilesystemException(): void
    {
        $configPath = '/community/patches.json';

        $this->qualityPackage->expects($this->once())
            ->method('getCommunityPatchesConfigPath')
            ->willReturn($configPath);

        $this->jsonConfigReader->expects($this->once())
            ->method('read')
            ->with($configPath)
            ->willThrowException(new SourceProviderException('Community config read error'));

        $this->expectException(SourceProviderException::class);
        $this->expectExceptionMessage('Community config read error');

        $this->sourceProvider->getCommunityPatches();
    }
}
