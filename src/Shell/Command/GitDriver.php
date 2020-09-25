<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Shell\Command;

use Magento\CloudPatches\Shell\ProcessFactory;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * GIT patch driver
 */
class GitDriver implements DriverInterface
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
            $this->processFactory->create(['git', 'apply'], $patch)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new DriverException($exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function revert(string $patch)
    {
        try {
            $this->processFactory->create(['git', 'apply', '--reverse'], $patch)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new DriverException($exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function applyCheck(string $patch)
    {
        try {
            $this->processFactory->create(['git', 'apply', '--check'], $patch)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new DriverException($exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function revertCheck(string $patch)
    {
        try {
            $this->processFactory->create(['git', 'apply', '--reverse', '--check'], $patch)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new DriverException($exception);
        }
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
