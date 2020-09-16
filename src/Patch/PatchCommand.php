<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\Shell\ProcessFactory;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PatchCommand implements PatchCommandInterface
{
    /**
     * @var ProcessFactory
     */
    private $processFactory;

    public function __construct(
        ProcessFactory $processFactory
    ) {
        $this->processFactory = $processFactory;
    }

    /**
     * @inheritDoc
     */
    public function apply(string $patch): bool
    {
        try {
            $this->processFactory->create(['patch', '--silent', '-p1'], $patch)
                ->mustRun();
            $result = true;
        } catch (ProcessFailedException $exception) {
            $this->processFactory->create(['patch', '--dry-run', '--silent', '-R', '-p1'], $patch)
                ->mustRun();
            $result = false;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function revert(string $patch): bool
    {
        try {
            $this->processFactory->create(['patch', '--silent', '-R', '-p1'], $patch)
                ->mustRun();
            $result = true;
        } catch (ProcessFailedException $exception) {
            $this->processFactory->create(['patch', '--dry-run', '--silent', '-p1'], $patch)
                ->mustRun();
            $result = false;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function check(string $patch): bool
    {
        try {
            $this->processFactory->create(['patch', '--dry-run', '--silent', '-p1'], $patch)
                ->mustRun();
            $result = true;
        } catch (ProcessFailedException $exception) {
            $result = false;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function status(string $patch): bool
    {
        try {
            $this->processFactory->create(['patch', '--dry-run', '--silent', '-p1'], $patch)
                ->mustRun();
            $result = true;
        } catch (ProcessFailedException $exception) {
            $this->processFactory->create(['patch', '--dry-run', '--silent', '-R', '-p1'], $patch)
                ->mustRun();
            $result = false;
        }

        return $result;
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
