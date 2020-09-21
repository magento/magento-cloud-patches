<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Shell\Command;

use Magento\CloudPatches\Patch\PatchCommandException;
use Magento\CloudPatches\Shell\ProcessFactory;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Unix patch driver
 */
class PatchDriver implements DriverInterface
{
    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @param ProcessFactory $processFactory
     */
    public function __construct(
        ProcessFactory $processFactory
    ) {
        $this->processFactory = $processFactory;
    }

    /**
     * @inheritDoc
     */
    public function apply(string $patch)
    {
        try {
            $this->applyCheck($patch);
            $this->processFactory->create(['patch', '--silent', '-f', '-p1'], $patch)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new PatchCommandException('Failed to apply patch', $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function revert(string $patch)
    {
        try {
            $this->revertCheck($patch);
            $this->processFactory->create(['patch', '--silent', '-f', '-p1', '--reverse'], $patch)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new PatchCommandException('Failed to revert patch', $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function applyCheck(string $patch)
    {
        try {
            $this->processFactory->create(['patch', '--silent', '-f', '-p1', '--dry-run'], $patch)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new PatchCommandException('Patch cannot be applied', $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function revertCheck(string $patch)
    {
        try {
            $this->processFactory->create(['patch', '--silent', '-f', '-p1', '--reverse', '--dry-run'], $patch)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new PatchCommandException('Patch cannot be reverted', $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function isInstalled(): bool
    {
        try {
            $this->processFactory->create(['patch', '--version'])->mustRun();
            $result = true;
        } catch (ProcessFailedException $exception) {
            $result = false;
        }

        return $result;
    }
}
