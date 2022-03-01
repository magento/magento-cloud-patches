<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process\Ece;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Action\RevertAction;
use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Command\Process\Ece\Revert;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Status\StatusPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class RevertTest extends TestCase
{
    /**
     * @var Revert
     */
    private $revertEce;

    /**
     * @var RevertAction|MockObject
     */
    private $revertAction;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Applier|MockObject
     */
    private $applier;

    /**
     * @var LocalPool|MockObject
     */
    private $localPool;

    /**
     * @var Renderer|MockObject
     */
    private $renderer;

    /**
     * @var StatusPool|MockObject
     */
    private $statusPool;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->revertAction = $this->createMock(RevertAction::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->applier = $this->createMock(Applier::class);
        $this->localPool = $this->createMock(LocalPool::class);
        $this->renderer = $this->createMock(Renderer::class);
        $this->statusPool = $this->createMock(StatusPool::class);

        $this->revertEce = new Revert(
            $this->revertAction,
            $this->logger,
            $this->applier,
            $this->localPool,
            $this->renderer,
            $this->statusPool
        );
    }

    /**
     * Tests successful patches reverting.
     *
     * @throws RuntimeException
     */
    public function testRevertSuccessful()
    {
        $patch1 = $this->createPatch('/path/patch1.patch', '../m2-hotfixes/patch1.patch');
        $patch2 = $this->createPatch('/path/patch2.patch', '../m2-hotfixes/patch2.patch');
        $patch3 = $this->createPatch('/path/patch3.patch', '../m2-hotfixes/patch3.patch');
        $this->statusPool->method('isNotApplied')
            ->willReturnMap([
                ['../m2-hotfixes/patch1.patch', false],
                ['../m2-hotfixes/patch2.patch', false],
                ['../m2-hotfixes/patch3.patch', true]
            ]);

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->localPool->method('getList')
            ->willReturn([$patch1, $patch2, $patch3]);

        $this->applier->method('revert')
            ->willReturnMap([
                [$patch2->getPath(), $patch2->getTitle(), 'Patch ' . $patch2->getTitle() .' has been reverted'],
                [$patch1->getPath(), $patch1->getTitle(), 'Patch ' . $patch1->getTitle() .' has been reverted'],
            ]);

        $outputMock->expects($this->exactly(4))
            ->method('writeln')
            ->withConsecutive(
                [$this->anything()],
                [$this->stringContains('Patch ' . $patch2->getTitle() .' has been reverted')],
                [$this->stringContains('Patch ' . $patch1->getTitle() .' has been reverted')]
            );

        $this->revertAction->expects($this->once())
            ->method('execute')
            ->withConsecutive([$inputMock, $outputMock, []]);

        $this->revertEce->run($inputMock, $outputMock);
    }

    /**
     * Tests patches reverting with exception.
     *
     * @throws RuntimeException
     */
    public function testRevertWithError()
    {
        $patch1 = $this->createPatch('/path/patch1.patch', '../m2-hotfixes/patch1.patch');
        $patch2 = $this->createPatch('/path/patch2.patch', '../m2-hotfixes/patch2.patch');
        $this->statusPool->method('isNotApplied')
            ->willReturnMap([
                ['../m2-hotfixes/patch1.patch', false],
                ['../m2-hotfixes/patch2.patch', false]
            ]);

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->localPool->method('getList')
            ->willReturn([$patch1, $patch2]);

        $this->applier->method('revert')
            ->willReturnMap([
                [$patch1->getPath(), $patch1->getTitle()],
                [$patch2->getPath(), $patch2->getTitle()]
            ])->willReturnCallback(
                function ($path, $title) {
                    if (strpos($title, 'patch2') !== false) {
                        throw new ApplierException('Applier error message');
                    }

                    return "Patch {$path} {$title} has been reverted";
                }
            );

        $this->revertAction->expects($this->once())
            ->method('execute')
            ->withConsecutive([$inputMock, $outputMock, []]);

        $this->revertEce->run($inputMock, $outputMock);
    }

    /**
     * Creates patch mock.
     *
     * @param string $path
     * @param string $title
     *
     * @return PatchInterface|MockObject
     */
    private function createPatch(string $path, string $title)
    {
        $patch = $this->getMockForAbstractClass(PatchInterface::class);
        $patch->method('getPath')->willReturn($path);
        $patch->method('getTitle')->willReturn($title);
        $patch->method('getId')->willReturn($title);

        return $patch;
    }
}
