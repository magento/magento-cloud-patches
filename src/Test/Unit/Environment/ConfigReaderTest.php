<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Environment;

use Magento\CloudPatches\Environment\ConfigReader;
use Magento\CloudPatches\Filesystem\FileList;
use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ConfigReaderTest extends TestCase
{
    /**
     * @var FileList|MockObject
     */
    private $fileList;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystem;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileList = $this->createMock(FileList::class);
        $this->filesystem = $this->createPartialMock(Filesystem::class, ['exists']);

        $this->configReader = new ConfigReader(
            $this->fileList,
            $this->filesystem
        );
    }

    /**
     * @throws FileSystemException
     */
    public function testRead()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->fileList->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento.env.yaml');
        $this->filesystem->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $this->configReader->read();
        $this->assertEquals(
            [
                'stage' => [
                    'build' => [
                        'QUALITY_PATCHES' => ['MC-1', 'MC-2']
                    ]
                ]
            ],
            $this->configReader->read()
        );
    }

    /**
     * @throws FileSystemException
     */
    public function testReadNotExist()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->fileList->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento.env.yaml');
        $this->filesystem->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->assertEquals([], $this->configReader->read());
    }
}
