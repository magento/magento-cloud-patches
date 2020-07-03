<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Filesystem\FileSystemException;
use Magento\CloudPatches\Patch\PatchFactory;
use Magento\CloudPatches\Patch\PatchIntegrityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class PatchFactoryTest extends TestCase
{
    const PATCH_FILENAME = 'filename.patch';

    /**
     * @var PatchFactory
     */
    private $patchFactory;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystem;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->patchFactory = new PatchFactory($this->filesystem);
    }

    /**
     * Tests patch creation.
     */
    public function testCreate()
    {
        $patchData = [
            'id' => 'mc-1',
            'title' => 'Title patch MC-1',
            'filename' => self::PATCH_FILENAME,
            'path' => 'patch/dir/' . self::PATCH_FILENAME,
            'type' => 'patch type',
            'packageName' => 'magento/magento-base',
            'packageConstraint' => '2.3.5',
            'require' => ['MC-2'],
            'replacedWith' => 'MC-3',
            'deprecated' => true
        ];
        $patchContent = file_get_contents(__DIR__ . '/Fixture/MC-1__testfixture__1.1.patch');

        $this->filesystem->method('get')
            ->willReturn($patchContent);

        $patch = $this->patchFactory->create(
            $patchData['id'],
            $patchData['title'],
            $patchData['filename'],
            $patchData['path'],
            $patchData['type'],
            $patchData['packageName'],
            $patchData['packageConstraint'],
            $patchData['require'],
            $patchData['replacedWith'],
            $patchData['deprecated']
        );

        $this->assertEquals($patch->getType(), $patchData['type']);
        $this->assertEquals($patch->getPath(), $patchData['path']);
        $this->assertEquals($patch->getId(), strtoupper($patchData['id']));
        $this->assertEquals($patch->getTitle(), $patchData['title']);
        $this->assertEquals($patch->getFilename(), $patchData['filename']);
        $this->assertEquals($patch->getPackageName(), $patchData['packageName']);
        $this->assertEquals($patch->getPackageConstraint(), $patchData['packageConstraint']);
        $this->assertEquals($patch->getRequire(), $patchData['require']);
        $this->assertEquals($patch->getReplacedWith(), $patchData['replacedWith']);
        $this->assertEquals($patch->isDeprecated(), $patchData['deprecated']);
        $this->assertEquals(
            ['magento/framework', 'magento/module-email', 'setup/src'],
            $patch->getAffectedComponents()
        );
    }

    /**
     * Tests a case when patch content can't be received.
     *
     * @throws PatchIntegrityException
     */
    public function testCreateWithException()
    {
        $patchData = [
            'id' => 'mc-1',
            'title' => 'Title patch MC-1',
            'filename' => self::PATCH_FILENAME,
            'path' => 'patch/dir/' . self::PATCH_FILENAME,
            'type' => 'patch type',
            'packageName' => 'magento/magento-base',
            'packageConstraint' => '2.3.5',
            'require' => ['MC-2'],
            'replacedWith' => 'MC-3',
            'deprecated' => true
        ];

        $this->filesystem->method('get')
            ->willThrowException(new FileSystemException(''));

        $this->expectException(PatchIntegrityException::class);
        $this->patchFactory->create(
            $patchData['id'],
            $patchData['title'],
            $patchData['filename'],
            $patchData['path'],
            $patchData['type'],
            $patchData['packageName'],
            $patchData['packageConstraint'],
            $patchData['require'],
            $patchData['replacedWith'],
            $patchData['deprecated']
        );
    }
}
