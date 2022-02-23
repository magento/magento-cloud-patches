<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\RollbackProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class RollbackProcessorTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Applier|MockObject
     */
    private $applier;

    /**
     * @var RollbackProcessor
     */
    private $rollbackProcessor;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->applier = $this->createMock(Applier::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->rollbackProcessor = new RollbackProcessor(
            $this->applier,
            $this->logger
        );
    }

    /**
     * Tests patch conflict processing.
     */
    public function testProcess()
    {
        $patch1 = $this->createPatch('MC-1', 'path1');
        $patch2 = $this->createPatch('MC-2', 'path2');
        $expectedMessages = [
            'Start of rollback',
            'Patch MC-2 has been reverted',
            'Patch MC-1 has been reverted',
            'End of rollback'
        ];

        $this->applier->method('revert')
            ->willReturnMap([
                [$patch1->getPath(), $patch1->getId(), 'Patch ' . $patch1->getId() .' has been reverted'],
                [$patch2->getPath(), $patch2->getId(), 'Patch ' . $patch2->getId() .' has been reverted'],
            ]);

        $this->assertEquals(
            $expectedMessages,
            $this->rollbackProcessor->process([$patch1, $patch2])
        );
    }

    /**
     * Tests with empty passing argument.
     */
    public function testProcessWithEmptyArray()
    {
        $this->applier->expects($this->never())
            ->method('revert');

        $this->assertEmpty($this->rollbackProcessor->process([]));
    }

    /**
     * Creates patch mock.
     *
     * @param string $id
     * @param string $path
     * @return PatchInterface|MockObject
     */
    private function createPatch(string $id, string $path)
    {
        $patch = $this->getMockForAbstractClass(PatchInterface::class);
        $patch->method('getId')->willReturn($id);
        $patch->method('getPath')->willReturn($path);

        return $patch;
    }
}
