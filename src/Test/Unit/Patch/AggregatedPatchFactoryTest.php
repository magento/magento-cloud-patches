<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Patch\AggregatedPatchFactory;
use Magento\CloudPatches\Patch\Data\Patch;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class AggregatedPatchFactoryTest extends TestCase
{
    /**
     * @var AggregatedPatchFactory
     */
    private $aggregatedPatchFactory;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->aggregatedPatchFactory = new AggregatedPatchFactory();
    }

    /**
     * Tests creating aggregated patch.
     *
     * @param PatchInterface[] $patches
     * @param array $expectedResult
     * @dataProvider createDataProvider
     */
    public function testCreate(array $patches, array $expectedResult)
    {
        $aggregatedPatch = $this->aggregatedPatchFactory->create($patches);

        $this->assertEquals($aggregatedPatch->getId(), $expectedResult['id']);
        $this->assertEquals($aggregatedPatch->getTitle(), $expectedResult['title']);
        $this->assertEquals($aggregatedPatch->getType(), $expectedResult['type']);
        $this->assertEquals($aggregatedPatch->getAffectedComponents(), $expectedResult['affected_components']);
        $this->assertEquals($aggregatedPatch->getRequire(), $expectedResult['require']);
        $this->assertEquals($aggregatedPatch->getReplacedWith(), $expectedResult['replaced_with']);
        $this->assertEquals($aggregatedPatch->isDeprecated(), $expectedResult['is_deprecated']);
    }

    /**
     * @return array
     */
    public function createDataProvider(): array
    {
        return [
            [
                'patches' => [
                    $this->createPatch(
                        'MC-1',
                        'Title patch MC-1 CE',
                        'Optional',
                        ['magento-module1', 'magento-module2'],
                        ['MC-2'],
                        'MC-3',
                        true
                    ),
                    $this->createPatch(
                        'MC-1',
                        'Title patch MC-1 EE',
                        'Optional',
                        ['magento-module3'],
                        ['MC-3'],
                        'MC-4',
                        false
                    )
                ],
                'expectedResult' => [
                    'id' => 'MC-1',
                    'title' => 'Title patch MC-1 EE',
                    'type' => 'Optional',
                    'affected_components' => ['magento-module1', 'magento-module2', 'magento-module3'],
                    'require' => ['MC-2', 'MC-3'],
                    'replaced_with' => 'MC-4',
                    'is_deprecated' => true
                ]
            ],
        ];
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     * @param string $title
     * @param string $type
     * @param array $affectedComponents
     * @param array $require
     * @param string $replacedWith
     * @param bool $isDeprecated
     * @return Patch|MockObject
     */
    private function createPatch(
        string $id,
        string $title,
        string $type,
        array $affectedComponents,
        array $require,
        string $replacedWith,
        bool $isDeprecated
    ) {
        $patch = $this->createMock(Patch::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('getTitle')->willReturn($title);
        $patch->method('getType')->willReturn($type);
        $patch->method('getAffectedComponents')->willReturn($affectedComponents);
        $patch->method('getRequire')->willReturn($require);
        $patch->method('getReplacedWith')->willReturn($replacedWith);
        $patch->method('isDeprecated')->willReturn($isDeprecated);

        return $patch;
    }
}
