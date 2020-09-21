<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\Shell\ProcessFactory;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Patch command for unix patch
 */
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
    public function apply(string $patch)
    {
        $this->applyCheck($patch);
        $this->processFactory->create(['patch', '--silent', '-f', '-p1'], $patch)
            ->mustRun();
    }

    /**
     * @inheritDoc
     */
    public function revert(string $patch)
    {
        $this->reverseCheck($patch);
        $this->processFactory->create(['patch', '--silent', '-f', '-p1', '--reverse'], $patch)
            ->mustRun();
    }

    /**
     * @inheritDoc
     */
    public function applyCheck(string $patch)
    {
        $this->processFactory->create(['patch', '--silent', '-f', '-p1', '--dry-run'], $patch)
            ->mustRun();
    }

    /**
     * @inheritDoc
     */
    public function reverseCheck(string $patch)
    {
        $this->processFactory->create(['patch', '--silent', '-f', '-p1', '--reverse', '--dry-run'], $patch)
            ->mustRun();
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
