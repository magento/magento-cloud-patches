<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Collector;

use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\Collector\GetCommunityPatchesConfig;
use Magento\CloudPatches\Patch\Collector\ValidatePatchesConfig;
use Magento\CloudPatches\Patch\SourceProvider;
use Magento\CloudPatches\Patch\SourceProviderException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class GetCommunityPatchesConfigTest extends TestCase
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
     * Tests that execute returns the community patches configuration.
     *
     * @return void
     * @throws CollectorException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteReturnsCommunityConfig(): void
    {
        $config = [
            'community-patch-1' => [
                'title' => 'Community Patch',
                'packages' => [
                    'magento/module-catalog' => [
                        '1.0.0' => [
                            'file' => 'community-patch.diff'
                        ]
                    ]
                ]
            ]
        ];

        $this->sourceProviderMock->expects($this->once())
            ->method('getCommunityPatches')
            ->willReturn($config);

        $this->validatePatchesConfigMock->expects($this->once())
            ->method('execute')
            ->with($config);

        $getCommunityPatchesConfig = new GetCommunityPatchesConfig(
            $this->sourceProviderMock,
            $this->validatePatchesConfigMock
        );

        $this->assertEquals($config, $getCommunityPatchesConfig->execute());
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
            'community-patch-1' => [
                'title' => 'Community Patch',
                'packages' => []
            ]
        ];

        $this->sourceProviderMock->expects($this->once())
            ->method('getCommunityPatches')
            ->willReturn($config);

        $this->validatePatchesConfigMock->expects($this->once())
            ->method('execute')
            ->with($config);

        $getCommunityPatchesConfig = new GetCommunityPatchesConfig(
            $this->sourceProviderMock,
            $this->validatePatchesConfigMock
        );

        // Call twice - source provider should only be called once due to caching
        $result1 = $getCommunityPatchesConfig->execute();
        $result2 = $getCommunityPatchesConfig->execute();

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
            ->method('getCommunityPatches')
            ->willThrowException(new SourceProviderException('Community source error'));

        $this->validatePatchesConfigMock->expects($this->never())
            ->method('execute');

        $getCommunityPatchesConfig = new GetCommunityPatchesConfig(
            $this->sourceProviderMock,
            $this->validatePatchesConfigMock
        );

        $this->expectException(CollectorException::class);
        $this->expectExceptionMessage('Community source error');

        $getCommunityPatchesConfig->execute();
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
            'community-patch-1' => [
                'title' => 'Community Patch',
                'packages' => [
                    'package/name' => [
                        '1.0.0' => [] // Missing 'file' property
                    ]
                ]
            ]
        ];

        $this->sourceProviderMock->expects($this->once())
            ->method('getCommunityPatches')
            ->willReturn($config);

        $this->validatePatchesConfigMock->expects($this->once())
            ->method('execute')
            ->with($config)
            ->willThrowException(new CollectorException('Validation error'));

        $getCommunityPatchesConfig = new GetCommunityPatchesConfig(
            $this->sourceProviderMock,
            $this->validatePatchesConfigMock
        );

        $this->expectException(CollectorException::class);
        $this->expectExceptionMessage('Validation error');

        $getCommunityPatchesConfig->execute();
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
            ->method('getCommunityPatches')
            ->willReturn($config);

        $this->validatePatchesConfigMock->expects($this->once())
            ->method('execute')
            ->with($config);

        $getCommunityPatchesConfig = new GetCommunityPatchesConfig(
            $this->sourceProviderMock,
            $this->validatePatchesConfigMock
        );

        $this->assertEquals([], $getCommunityPatchesConfig->execute());
    }
}
