<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Pool;

use Magento\CloudPatches\Patch\Collector\CloudCollector;
use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\Collector\SupportCollector;
use Magento\CloudPatches\Patch\Data\Patch;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\PatchIntegrityException;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Pool\PatchNotFoundException;
use Magento\CloudPatches\Patch\Pool\RequiredPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class OptionalPoolTest extends TestCase
{
    /**
     * @var SupportCollector|MockObject
     */
    private $qualityCollector;

    /**
     * @var \Magento\CloudPatches\Patch\Collector\CloudCollector|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cloudCollector;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->cloudCollector = $this->createMock(CloudCollector::class);
        $this->qualityCollector = $this->createMock(SupportCollector::class);
    }

    /**
     * Tests retrieving patches.
     *
     * @param string[] $filter
     * @param bool $useRequire
     * @param PatchInterface[] $cloudPatches
     * @param PatchInterface[] $qualityPatches
     * @param PatchInterface[] $expectedResult
     *
     * @dataProvider getListFilterDataProvider
     */
    public function testGetList(
        array $filter,
        bool $useRequire,
        array $cloudPatches,
        array $qualityPatches,
        array $expectedResult
    ) {
        $pool = $this->createPool($cloudPatches, $qualityPatches);

        $this->assertEquals($expectedResult, array_values($pool->getList($filter, $useRequire)));
    }

    /**
     * @return array
     */
    public function getListFilterDataProvider(): array
    {
        return [
            $this->caseReturnAllWithEmptyFilter(),
            $this->caseReturnPatchWithoutRequired(),
            $this->caseReturnPatchWithRequired(),
            $this->caseReturnPatchListUnique()
        ];
    }

    /**
     * Tests case when patch is not found in a pool.
     */
    public function testGetListPatchNotFound()
    {
        $filter = ['MC-3'];
        $patch1 = $this->createPatch('MC-1');
        $patch2 = $this->createPatch('MC-2');

        $pool = $this->createPool([$patch1, $patch2]);

        $this->expectException(PatchNotFoundException::class);
        $pool->getList($filter);
    }

    /**
     * Tests case when patch from 'require' configuration attribute is not found in a pool.
     */
    public function testGetListRequiredPatchNotFound()
    {
        $filter = ['MC-1'];
        $patch = $this->createPatch('MC-1', ['MC-not-exists']);

        $pool = $this->createPool([$patch]);

        $this->expectException(PatchIntegrityException::class);
        $pool->getList($filter);
    }

    /**
     * Tests retrieving only optional patches.
     */
    public function testGetOptionalAll()
    {
        $requiredPatch1 = $this->createPatch('MCLOUD-1');
        $requiredPatch2 = $this->createPatch('MCLOUD-2');
        $optionalPatch1 = $this->createPatch('MC-1');
        $optionalPatch2 = $this->createPatch('MC-2');
        $optionalPatch1->method('getType')
            ->willReturn(PatchInterface::TYPE_OPTIONAL);
        $optionalPatch2->method('getType')
            ->willReturn(PatchInterface::TYPE_OPTIONAL);

        $pool = $this->createPool([$requiredPatch1, $requiredPatch2], [$optionalPatch1, $optionalPatch2]);

        $this->assertEquals([$optionalPatch1, $optionalPatch2], $pool->getOptionalAll());
    }

    /**
     * Tests retrieving patch ids dependent on provided patch if any.
     */
    public function testGetDependentOn()
    {
        $patch1 = $this->createPatch('MC-1');
        $patch2 = $this->createPatch('MC-2', ['MC-1']);
        $patch3 = $this->createPatch('MC-3', ['MC-1']);

        $pool = $this->createPool([$patch1, $patch2, $patch3]);

        $this->assertEquals(['MC-2', 'MC-3'], $pool->getDependentOn('MC-1'));
    }

    /**
     * Tests retrieving ids of patch dependencies.
     */
    public function testGetDependencies()
    {
        $patch1 = $this->createPatch('MC-1');
        $patch2 = $this->createPatch('MC-2', ['MC-1']);
        $patch3 = $this->createPatch('MC-3', ['MC-2']);
        $patch4 = $this->createPatch('MC-4', ['MC-3']);

        $pool = $this->createPool([$patch1, $patch2, $patch3, $patch4]);

        $this->assertEquals(
            ['MC-1', 'MC-2', 'MC-3'],
            array_values($pool->getDependencies('MC-4'))
        );
    }

    /**
     * Tests retrieving additional required patches which are not included in patch filter.
     */
    public function testGetAdditionalRequiredPatches()
    {
        $filter = ['MC-4', 'MC-1'];
        $patch1 = $this->createPatch('MC-1');
        $patch2 = $this->createPatch('MC-2', ['MC-1']);
        $patch3 = $this->createPatch('MC-3', ['MC-2']);
        $patch4 = $this->createPatch('MC-4', ['MC-3']);

        $pool = $this->createPool([$patch1, $patch2, $patch3, $patch4]);

        $this->assertEquals(
            [$patch2, $patch3],
            array_values($pool->getAdditionalRequiredPatches($filter))
        );
    }

    /**
     * Tests retrieving patch ids replaced by provided patch if any.
     */
    public function testGetReplacedBy()
    {
        $patchForReplaceId = 'MC-4';
        $patch1 = $this->createPatch('MC-1');
        $patch2 = $this->createPatch('MC-2', ['MC-1'], $patchForReplaceId);
        $patch3 = $this->createPatch('MC-3', [], $patchForReplaceId);
        $patch4 = $this->createPatch('MC-4');

        $pool = $this->createPool([$patch1, $patch2, $patch3, $patch4]);

        $this->assertEquals(
            [$patch2->getId(), $patch3->getId()],
            array_values($pool->getReplacedBy($patchForReplaceId))
        );
    }

    /**
     * Tests retrieving not deprecated patch ids by type.
     */
    public function testGetIdsByType()
    {
        $patch1 = $this->createPatch('OPTIONAL-1');
        $patch1->method('getType')->willReturn(PatchInterface::TYPE_OPTIONAL);
        $patch2 = $this->createPatch('OPTIONAL-2');
        $patch2->method('getType')->willReturn(PatchInterface::TYPE_OPTIONAL);
        $patch2->method('isDeprecated')->willReturn(true);
        $patch3 = $this->createPatch('REQUIRED-3');
        $patch3->method('getType')->willReturn(PatchInterface::TYPE_REQUIRED);
        $patch4 = $this->createPatch('REQUIRED-4');
        $patch4->method('getType')->willReturn(PatchInterface::TYPE_REQUIRED);

        $pool = $this->createPool([$patch1, $patch2, $patch3, $patch4]);

        $this->assertEquals(
            ['OPTIONAL-1'],
            array_values($pool->getIdsByType(PatchInterface::TYPE_OPTIONAL))
        );

        $this->assertEquals(
            ['REQUIRED-3', 'REQUIRED-4'],
            array_values($pool->getIdsByType(PatchInterface::TYPE_REQUIRED))
        );
    }

    /**
     * Filter is empty, Cloud + Quality patches expected to return.
     *
     * @return array[]
     */
    private function caseReturnAllWithEmptyFilter(): array
    {
        $cloudPatch1 = $this->createPatch('MCLOUD-1');
        $cloudPatch2 = $this->createPatch('MCLOUD-2');
        $qualityPatch1 = $this->createPatch('MC-1');
        $qualityPatch2 = $this->createPatch('MC-2');

        return [
            'filter' => [],
            'useRequire' => false,
            'cloudPatches' => [$cloudPatch1, $cloudPatch2],
            'qualityPatches' => [$qualityPatch1, $qualityPatch2],
            'expectedResult' => [$cloudPatch1, $cloudPatch2, $qualityPatch1, $qualityPatch2]
        ];
    }

    /**
     * Filter is not empty, expected to return requested patch without required.
     *
     * @return array[]
     */
    private function caseReturnPatchWithoutRequired(): array
    {
        $cloudPatch1 = $this->createPatch('MCLOUD-1');
        $cloudPatch2 = $this->createPatch('MCLOUD-2');
        $qualityPatch1 = $this->createPatch('MC-1');
        $qualityPatch2 = $this->createPatch('MC-2', ['MC-1']);

        return [
            'filter' => ['MC-2'],
            'useRequire' => false,
            'cloudPatches' => [$cloudPatch1, $cloudPatch2],
            'qualityPatches' => [$qualityPatch1, $qualityPatch2],
            'expectedResult' => [$qualityPatch2]
        ];
    }

    /**
     * Expected to return requested patch with required.
     *
     * @return array[]
     */
    private function caseReturnPatchWithRequired(): array
    {
        $cloudPatch1 = $this->createPatch('MCLOUD-1');
        $cloudPatch2 = $this->createPatch('MCLOUD-2');
        $qualityPatch1 = $this->createPatch('MC-1');
        $qualityPatch2 = $this->createPatch('MC-2', ['MCLOUD-2']);

        return [
            'filter' => ['MC-2'],
            'useRequire' => true,
            'cloudPatches' => [$cloudPatch1, $cloudPatch2],
            'qualityPatches' => [$qualityPatch1, $qualityPatch2],
            'expectedResult' => [$cloudPatch2, $qualityPatch2]
        ];
    }

    /**
     * Expected to return result without duplicates.
     *
     * @return array[]
     */
    private function caseReturnPatchListUnique(): array
    {
        $cloudPatch1 = $this->createPatch('MCLOUD-1');
        $cloudPatch2 = $this->createPatch('MCLOUD-2');
        $qualityPatch1 = $this->createPatch('MC-1');
        $qualityPatch2 = $this->createPatch('MC-2', ['MCLOUD-2']);

        return [
            'filter' => ['MCLOUD-2', 'MC-2'],
            'useRequire' => true,
            'cloudPatches' => [$cloudPatch1, $cloudPatch2],
            'qualityPatches' => [$qualityPatch1, $qualityPatch2],
            'expectedResult' => [$cloudPatch2, $qualityPatch2]
        ];
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     * @param array $require
     * @param string $replacedWith
     * @return Patch|MockObject
     */
    private function createPatch(string $id, array $require = [], string $replacedWith = '')
    {
        $patch = $this->createMock(Patch::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('getRequire')->willReturn($require);
        $patch->method('getReplacedWith')->willReturn($replacedWith);
        $patch->method('getOrigin')->willReturn(SupportCollector::ORIGIN);

        // To make mock object unique for assertions and array operations.
        $patch->id = microtime();
        $patch->method('__toString')->willReturn($patch->id);

        return $patch;
    }

    /**
     * Creates optional pool.
     *
     * @param PatchInterface[] $cloudPatches
     * @param PatchInterface[] $qualityPatches
     *
     * @return OptionalPool
     * @throws CollectorException
     */
    private function createPool(array $cloudPatches = [], array $qualityPatches = []): OptionalPool
    {
        $this->cloudCollector->expects($this->once())
            ->method('collect')
            ->willReturn($cloudPatches);

        $this->qualityCollector->expects($this->once())
            ->method('collect')
            ->willReturn($qualityPatches);

        $collectors = [
            $this->cloudCollector,
            $this->qualityCollector
        ];
        $pool = new OptionalPool($collectors);

        return $pool;
    }
}
