<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Filesystem\FileSystemException;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\PatchBuilder;
use Magento\CloudPatches\Patch\PatchIntegrityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class PatchBuilderTest extends TestCase
{
    const PATCH_FILENAME = 'filename.patch';

    /**
     * @var PatchBuilder
     */
    private $patchBuilder;

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

        $this->patchBuilder = new PatchBuilder($this->filesystem);
    }

    /**
     * Tests patch creation.
     */
    public function testBuild()
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

        $patch = $this->buildPatch($patchData);

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
    public function testBuildWithException()
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
        $this->buildPatch($patchData);
    }

    /**
     * Builds a patch.
     *
     * @param array $patchData
     *
     * @return PatchInterface
     * @throws PatchIntegrityException
     */
    private function buildPatch(array $patchData): PatchInterface
    {
        $this->patchBuilder->setId($patchData['id']);
        $this->patchBuilder->setTitle($patchData['title']);
        $this->patchBuilder->setFilename($patchData['filename']);
        $this->patchBuilder->setPath($patchData['path']);
        $this->patchBuilder->setType($patchData['type']);
        $this->patchBuilder->setPackageName($patchData['packageName']);
        $this->patchBuilder->setPackageConstraint($patchData['packageConstraint']);
        $this->patchBuilder->setRequire($patchData['require']);
        $this->patchBuilder->setReplacedWith($patchData['replacedWith']);
        $this->patchBuilder->setDeprecated($patchData['deprecated']);

        return $this->patchBuilder->build();
    }
}
