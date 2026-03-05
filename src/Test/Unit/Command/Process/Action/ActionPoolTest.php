<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process\Action;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Action\ActionInterface;
use Magento\CloudPatches\Command\Process\Action\ActionPool;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class ActionPoolTest extends TestCase
{
    /**
     * Tests executing action pool with multiple actions.
     *
     * @return void
     * @throws RuntimeException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecute(): void
    {
        $inputMock = $this->createMock(InputInterface::class);
        $outputMock = $this->createMock(OutputInterface::class);
        $patchFilter = ['filter'];

        $action1 = $this->createMock(ActionInterface::class);
        $action1->expects($this->once())
            ->method('execute')
            ->with($inputMock, $outputMock, $patchFilter);

        $action2 = $this->createMock(ActionInterface::class);
        $action2->expects($this->once())
            ->method('execute')
            ->with($inputMock, $outputMock, $patchFilter);

        $actionPool = new ActionPool([$action1, $action2]);
        $actionPool->execute($inputMock, $outputMock, $patchFilter);
    }

    /**
     * Tests that constructor throws exception when invalid action is provided.
     *
     * @return void
     */
    public function testConstructThrowsExceptionForInvalidAction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Instance of Magento\CloudPatches\Command\Process\Action\ActionInterface is expected'
        );

        new ActionPool([new \stdClass()]);
    }
}
