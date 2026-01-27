<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Collector;

use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\Collector\GetSupportPatchesConfig;
use Magento\CloudPatches\Patch\Collector\ValidatePatchesConfig;
use Magento\CloudPatches\Patch\SourceProvider;
use Magento\CloudPatches\Patch\SourceProviderException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class GetSupportPatchesConfigTest extends TestCase
{
    /**
     * @var SourceProvider|MockObject
     */
    private $sourceProviderMock;

    /**
     * @var ValidatePatchesConfig|MockObject
     */
    private $validatePatchesConfigMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->sourceProviderMock        = $this->createMock(SourceProvider::class);
        $this->validatePatchesConfigMock = $this->createMock(ValidatePatchesConfig::class);
    }

    /**
     * Tests that execute returns the support patches configuration.
     *
     * @return void
     * @throws CollectorException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteReturnsConfig(): void
    {
        $config = [
            'patch-1' => [
                'title' => 'Test Patch',
                'packages' => [
                    'magento/module-test' => [
                        '1.0.0' => [
                            'file' => 'patch.diff'
                        ]
                    ]
                ]
            ]
        ];

        $this->sourceProviderMock->expects($this->once())
            ->method('getSupportPatches')
            ->willReturn($config);

        $this->validatePatchesConfigMock->expects($this->once())
            ->method('execute')
            ->with($config);

        $getSupportPatchesConfig = new GetSupportPatchesConfig(
            $this->sourceProviderMock,
            $this->validatePatchesConfigMock
        );

        $this->assertEquals($config, $getSupportPatchesConfig->execute());
    }

    /**
     * Tests that configuration is cached after first retrieval.
     *
     * @return void
     * @throws CollectorException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteCachesConfig(): void
    {
        $config = [
            'patch-1' => [
                'title' => 'Test Patch',
                'packages' => []
            ]
        ];

        $this->sourceProviderMock->expects($this->once())
            ->method('getSupportPatches')
            ->willReturn($config);

        $this->validatePatchesConfigMock->expects($this->once())
            ->method('execute')
            ->with($config);

        $getSupportPatchesConfig = new GetSupportPatchesConfig(
            $this->sourceProviderMock,
            $this->validatePatchesConfigMock
        );

        // Call twice - source provider should only be called once due to caching
        $result1 = $getSupportPatchesConfig->execute();
        $result2 = $getSupportPatchesConfig->execute();

        $this->assertEquals($config, $result1);
        $this->assertEquals($config, $result2);
    }

    /**
     * Tests that CollectorException is thrown when SourceProvider throws exception.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteThrowsCollectorExceptionOnSourceProviderException(): void
    {
        $this->sourceProviderMock->expects($this->once())
            ->method('getSupportPatches')
            ->willThrowException(new SourceProviderException('Source provider error'));

        $this->validatePatchesConfigMock->expects($this->never())
            ->method('execute');

        $getSupportPatchesConfig = new GetSupportPatchesConfig(
            $this->sourceProviderMock,
            $this->validatePatchesConfigMock
        );

        $this->expectException(CollectorException::class);
        $this->expectExceptionMessage('Source provider error');

        $getSupportPatchesConfig->execute();
    }

    /**
     * Tests that CollectorException is thrown when validation fails.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteThrowsCollectorExceptionOnValidationFailure(): void
    {
        $config = [
            'patch-1' => [
                'title' => 'Test Patch',
                'packages' => [
                    'package/name' => [
                        '1.0.0' => [] // Missing 'file' property
                    ]
                ]
            ]
        ];

        $this->sourceProviderMock->expects($this->once())
            ->method('getSupportPatches')
            ->willReturn($config);

        $this->validatePatchesConfigMock->expects($this->once())
            ->method('execute')
            ->with($config)
            ->willThrowException(new CollectorException('Validation error'));

        $getSupportPatchesConfig = new GetSupportPatchesConfig(
            $this->sourceProviderMock,
            $this->validatePatchesConfigMock
        );

        $this->expectException(CollectorException::class);
        $this->expectExceptionMessage('Validation error');

        $getSupportPatchesConfig->execute();
    }

    /**
     * Tests that execute returns empty array when source returns empty.
     *
     * @return void
     * @throws CollectorException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteReturnsEmptyArrayWhenSourceIsEmpty(): void
    {
        $config = [];

        $this->sourceProviderMock->expects($this->once())
            ->method('getSupportPatches')
            ->willReturn($config);

        $this->validatePatchesConfigMock->expects($this->once())
            ->method('execute')
            ->with($config);

        $getSupportPatchesConfig = new GetSupportPatchesConfig(
            $this->sourceProviderMock,
            $this->validatePatchesConfigMock
        );

        $this->assertEquals([], $getSupportPatchesConfig->execute());
    }
}
