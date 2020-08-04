<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\RevertValidator;
use Magento\CloudPatches\Patch\Status\StatusPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class RevertValidatorTest extends TestCase
{
    /**
     * @var RevertValidator
     */
    private $revertValidator;

    /**
     * @var OptionalPool|MockObject
     */
    private $optionalPool;

    /**
     * @var StatusPool|MockObject
     */
    private $statusPool;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->optionalPool = $this->createMock(OptionalPool::class);
        $this->statusPool = $this->createMock(StatusPool::class);

        $this->revertValidator = new RevertValidator(
            $this->optionalPool,
            $this->statusPool
        );
    }

    /**
     * Tests validation fails.
     *
     * Case when patch has applied dependent patches.
     */
    public function testValidateWithAppliedDependents()
    {
        $patchFilter = ['MC-1'];

        $this->optionalPool->method('getDependentOn')
            ->with('MC-1')
            ->willReturn(['MC-2', 'MC-3']);

        $this->statusPool->method('isApplied')
            ->willReturnMap([
                ['MC-2', true],
                ['MC-3', true],
            ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Patch MC-1 is a dependency for MC-2 MC-3. Please, revert MC-2 MC-3 first');

        $this->revertValidator->validate($patchFilter);
    }

    /**
     * Tests validation success.
     *
     * Case when dependent patches are not applied.
     *
     * @doesNotPerformAssertions
     */
    public function testValidateWithNotAppliedDependents()
    {
        $patchFilter = ['MC-1'];

        $this->optionalPool->method('getDependentOn')
            ->with('MC-1')
            ->willReturn(['MC-2', 'MC-3']);

        $this->statusPool->method('isApplied')
            ->willReturnMap([
                ['MC-2', false],
                ['MC-3', false],
            ]);

        $this->revertValidator->validate($patchFilter);
    }

    /**
     * Tests validation success.
     *
     * Case when there are no dependent patches.
     *
     * @doesNotPerformAssertions
     */
    public function testValidateWithNoDependents()
    {
        $patchFilter = ['MC-1'];

        $this->optionalPool->method('getDependentOn')
            ->with('MC-1')
            ->willReturn([]);

        $this->statusPool->expects($this->never())
            ->method('isApplied');

        $this->revertValidator->validate($patchFilter);
    }
}
