<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Conflict;

use Magento\CloudPatches\Environment\Config;
use Magento\CloudPatches\Patch\Conflict\Analyzer as ConflictAnalyzer;
use Magento\CloudPatches\Patch\Conflict\ApplyChecker;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\RollbackProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class AnalyzerTest extends TestCase
{
    /**
     * @var RollbackProcessor|MockObject
     */
    private $rollbackProcessor;

    /**
     * @var OptionalPool|MockObject
     */
    private $optionalPool;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var ConflictAnalyzer
     */
    private $conflictAnalyzer;

    /**
     * @var ApplyChecker|MockObject
     */
    private $applyChecker;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->optionalPool = $this->createMock(OptionalPool::class);
        $this->config = $this->createMock(Config::class);
        $this->rollbackProcessor = $this->createMock(RollbackProcessor::class);
        $this->applyChecker = $this->createMock(ApplyChecker::class);

        $this->conflictAnalyzer = new ConflictAnalyzer(
            $this->optionalPool,
            $this->config,
            $this->rollbackProcessor,
            $this->applyChecker
        );
    }

    /**
     * Tests patch conflict analyzing.
     *
     * @param array $checkApplyMap
     * @param string $expectedMessage
     * @dataProvider analyzeDataProvider
     */
    public function testAnalyze(array $checkApplyMap, string $expectedMessage)
    {
        $failedPatch = $this->createPatch('MC-1', 'path1', PatchInterface::TYPE_OPTIONAL);
        $requiredPool = ['REQUIRED-1', 'REQUIRED-2'];
        $optionalPool = ['OPTIONAL-1', 'OPTIONAL-2'];

        $this->config->expects($this->once())
            ->method('isCloud')
            ->willReturn(true);
        $this->optionalPool->expects($this->once())
            ->method('getList')
            ->willReturn([]);
        $this->optionalPool->method('getDependencies')
            ->willReturn([]);
        $this->rollbackProcessor->expects($this->once())
            ->method('process');

        $this->optionalPool->expects($this->atLeastOnce())
            ->method('getIdsByType')
            ->willReturnMap([
                [PatchInterface::TYPE_REQUIRED, $requiredPool],
                [PatchInterface::TYPE_OPTIONAL, $optionalPool]
            ]);

        $this->applyChecker->method('check')
            ->willReturnMap($checkApplyMap);

        $this->assertEquals(
            $expectedMessage,
            $this->conflictAnalyzer->analyze($failedPatch, [])
        );
    }

    /**
     * @return array
     */
    public function analyzeDataProvider(): array
    {
        return [
            [
                'checkApplyMap' => [
                    [['REQUIRED-1', 'REQUIRED-2', 'MC-1'], true],
                    [['OPTIONAL-1', 'MC-1'], false],
                    [['OPTIONAL-2', 'MC-1'], false],
                ],
                'expectedMessage' => 'Patch MC-1 is not compatible with optional: OPTIONAL-1 OPTIONAL-2'
            ],
            [
                'checkApplyMap' => [
                    [['REQUIRED-1', 'REQUIRED-2', 'MC-1'], true],
                    [['OPTIONAL-1', 'MC-1'], true],
                    [['OPTIONAL-2', 'MC-1'], false],
                ],
                'expectedMessage' => 'Patch MC-1 is not compatible with optional: OPTIONAL-2'
            ],
            [
                'checkApplyMap' => [
                    [['REQUIRED-1', 'REQUIRED-2', 'MC-1'], false],
                    [['REQUIRED-2', 'MC-1'], false],
                    [['REQUIRED-1', 'MC-1'], true],
                ],
                'expectedMessage' => 'Patch MC-1 is not compatible with required: REQUIRED-2'
            ],
            [
                'checkApplyMap' => [
                    [['REQUIRED-1', 'REQUIRED-2', 'MC-1'], false],
                    [['REQUIRED-2', 'MC-1'], false],
                    [['REQUIRED-1', 'MC-1'], false],
                    [['MC-1'], false],
                ],
                'expectedMessage' => 'Patch MC-1 can\'t be applied to clean Magento instance'
            ],
        ];
    }

    /**
     * Tests with non-Cloud environment.
     */
    public function testAnalyzeWithNonCloudEnv()
    {
        $patch = $this->createPatch('MC-1', 'path1');

        $this->config->expects($this->once())
            ->method('isCloud')
            ->willReturn(false);
        $this->optionalPool->expects($this->never())
            ->method('getIdsByType');

        $this->assertEmpty($this->conflictAnalyzer->analyze($patch));
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     * @param string $path
     * @param string $type
     * @return PatchInterface|MockObject
     */
    private function createPatch(string $id, string $path, string $type = '')
    {
        $patch = $this->getMockForAbstractClass(PatchInterface::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('getPath')->willReturn($path);
        $patch->method('getType')->willReturn($type);

        return $patch;
    }
}
