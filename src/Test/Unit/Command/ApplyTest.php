<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command;

use Magento\CloudPatches\Command\Apply;
use Magento\CloudPatches\Command\Patch\Manager;
use Magento\CloudPatches\Command\Patch\ManagerException;
use Magento\CloudPatches\Patch\ApplierException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class ApplyTest extends TestCase
{
    /**
     * @var Apply
     */
    private $command;

    /**
     * @var Manager|MockObject
     */
    private $managerMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->managerMock = $this->createMock(Manager::class);

        $this->command = new Apply(
            $this->managerMock
        );
    }

    /**
     * @throws ManagerException
     * @throws ApplierException
     */
    public function testExecute()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->managerMock->expects($this->once())
            ->method('applyComposerPatches');
        $this->managerMock->expects($this->once())
            ->method('applyHotFixes');

        $this->command->execute($inputMock, $outputMock);
    }
}
