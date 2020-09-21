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
 * Patch command for GIT
 */
class GitPatchCommand implements PatchCommandInterface
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
        $this->processFactory->create(['git', 'apply'], $patch)
            ->mustRun();
    }

    /**
     * @inheritDoc
     */
    public function revert(string $patch)
    {
        $this->processFactory->create(['git', 'apply', '--reverse'], $patch)
            ->mustRun();
    }

    /**
     * @inheritDoc
     */
    public function applyCheck(string $patch)
    {
        $this->processFactory->create(['git', 'apply', '--check'], $patch)
            ->mustRun();
    }

    /**
     * @inheritDoc
     */
    public function reverseCheck(string $patch)
    {
        $this->processFactory->create(['git', 'apply', '--reverse', '--check'], $patch)
            ->mustRun();
    }

    /**
     * @inheritDoc
     */
    public function isInstalled(): bool
    {
        try {
            $this->processFactory->create(['git', '--version'])->mustRun();
            $result = true;
        } catch (ProcessFailedException $exception) {
            $result = false;
        }

        return $result;
    }
}
