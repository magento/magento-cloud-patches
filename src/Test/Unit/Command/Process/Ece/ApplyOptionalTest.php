<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process\Ece;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Command\Process\Action\ActionPool;
use Magento\CloudPatches\Command\Process\Ece\ApplyOptional;
use Magento\CloudPatches\Environment\Config;
use Magento\CloudPatches\Patch\FilterFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class ApplyOptionalTest extends TestCase
{
    /**
     * @var ApplyOptional
     */
    private $applyOptionalEce;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ActionPool|MockObject
     */
    private $actionPool;

    /**
     * @var FilterFactory|MockObject
     */
    private $filterFactory;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->filterFactory = $this->createMock(FilterFactory::class);
        $this->actionPool = $this->createMock(ActionPool::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->config = $this->createMock(Config::class);

        $this->applyOptionalEce = new ApplyOptional(
            $this->filterFactory,
            $this->actionPool,
            $this->logger,
            $this->config
        );
    }

    /**
     * Tests successful optional patches applying.
     *
     * @throws RuntimeException
     */
    public function testApplyWithPatchEnvVariableProvided()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $configQualityPatches = ['MC-1111', 'MC-22222'];
        $this->config->expects($this->once())
            ->method('getQualityPatches')
            ->willReturn($configQualityPatches);
        $this->filterFactory->method('createApplyFilter')
            ->with($configQualityPatches)
            ->willReturn($configQualityPatches);

        $this->actionPool->expects($this->once())
            ->method('execute')
            ->withConsecutive([$inputMock, $outputMock, $configQualityPatches]);

        $this->applyOptionalEce->run($inputMock, $outputMock);
    }

    /**
     * Tests optional patches applying when QUALITY_PATCHES env variable is empty.
     *
     * @throws RuntimeException
     */
    public function testApplyWithEmptyPatchEnvVariable()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $configQualityPatches = [];
        $this->config->expects($this->once())
            ->method('getQualityPatches')
            ->willReturn($configQualityPatches);
        $this->filterFactory->method('createApplyFilter')
            ->with($configQualityPatches)
            ->willReturn(null);

        $this->actionPool->expects($this->never())
            ->method('execute');

        $this->applyOptionalEce->run($inputMock, $outputMock);
    }
}
