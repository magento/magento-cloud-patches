<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\ApplyRequired;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Magento\CloudPatches\Patch\Conflict\Processor as ConflictProcessor;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\RequiredPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ApplyRequiredTest extends TestCase
{
    /**
     * @var ApplyRequired
     */
    private $manager;

    /**
     * @var Applier|MockObject
     */
    private $applier;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var RequiredPool|MockObject
     */
    private $requiredPool;

    /**
     * @var Renderer|MockObject
     */
    private $renderer;

    /**
     * @var ConflictProcessor|MockObject
     */
    private $conflictProcessor;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->applier = $this->createMock(Applier::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requiredPool = $this->createMock(RequiredPool::class);
        $this->renderer = $this->createMock(Renderer::class);
        $this->conflictProcessor = $this->createMock(ConflictProcessor::class);

        $this->manager = new ApplyRequired(
            $this->applier,
            $this->requiredPool,
            $this->renderer,
            $this->logger,
            $this->conflictProcessor
        );
    }

    /**
     * Tests successful required patches applying.
     *
     * @throws RuntimeException
     */
    public function testApplySuccessful()
    {
        $patch1 = $this->createPatch('/path/patch1.patch', 'MC-11111');
        $patch2 = $this->createPatch('/path/patch2.patch', 'MC-22222');
        $patch3 = $this->createPatch('/path/patch3.patch', 'MC-33333');

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->createMock(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createMock(OutputInterface::class);
        $this->requiredPool->method('getList')
            ->willReturn([$patch1, $patch2, $patch3]);

        $this->applier->method('apply')
            ->willReturnMap([
                [$patch1->getPath(), $patch1->getId(), 'Patch ' . $patch1->getId() .' has been applied'],
                [$patch2->getPath(), $patch2->getId(), 'Patch ' . $patch2->getId() .' has been applied'],
                [$patch3->getPath(), $patch3->getId(), 'Patch ' . $patch3->getId() .' has been applied'],
            ]);

        $this->renderer->expects($this->exactly(3))
            ->method('printPatchInfo')
            ->willReturnCallback(function ($patch, $message) use ($patch1, $patch2, $patch3) {
                static $callCount = 0;
                $expectedPatches = [$patch1, $patch2, $patch3];
                $expectedMessages = [
                    'Patch ' . $patch1->getId() . ' has been applied',
                    'Patch ' . $patch2->getId() . ' has been applied',
                    'Patch ' . $patch3->getId() . ' has been applied'
                ];

                if ($patch === $expectedPatches[$callCount] && $message === $expectedMessages[$callCount]) {
                    $callCount++;
                    return true;
                }

                return false;
            });
        $this->manager->run($inputMock, $outputMock);
    }

    /**
     * Tests required patches applying with exception.
     *
     * @throws RuntimeException
     */
    public function testApplyWithException()
    {
        $patch = $this->createPatch('/path/patch.patch', 'MC-11111');

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->createMock(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createMock(OutputInterface::class);
        $this->requiredPool->method('getList')
            ->willReturn([$patch]);

        $this->applier->method('apply')
            ->with(
                $this->logicalOr($this->equalTo($patch->getPath()), $this->equalTo($patch->getId()))
            )
            ->willThrowException(new ApplierException('Applier error message'));

        $this->conflictProcessor->expects($this->once())
            ->method('process')
            ->with(
                $this->logicalOr(
                    $this->equalTo($outputMock),
                    $this->equalTo($patch),
                    $this->equalTo([]),
                    $this->equalTo('Applier error message')
                )
            )
        ->willThrowException(new RuntimeException('Error message'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error message');

        $this->manager->run($inputMock, $outputMock);
    }

    /**
     * Creates patch mock.
     *
     * @param string $path
     * @param string $id
     *
     * @return PatchInterface|MockObject
     */
    private function createPatch(string $path, string $id)
    {
        $patch = $this->createMock(PatchInterface::class);
        $patch->method('getPath')->willReturn($path);
        $patch->method('getId')->willReturn($id);

        return $patch;
    }
}
