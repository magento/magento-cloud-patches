<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\Patch\Status\StatusPool;
use Magento\CloudPatches\Shell\ProcessFactory;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Applies and reverts patches.
 */
class Applier
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
     * General apply processing.
     *
     * @param string $path
     * @param string $id
     * @return string
     *
     * @throws ApplierException
     */
    public function apply(string $path, string $id): string
    {
        try {
            $this->processFactory->create(['git', 'apply', $path])
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            try {
                $this->processFactory->create(['git', 'apply', '--check', '--reverse', $path])
                    ->mustRun();
            } catch (ProcessFailedException $reverseException) {
                throw new ApplierException($exception->getMessage(), $exception->getCode());
            }

            return sprintf('Patch %s was already applied', $id);
        }

        return sprintf('Patch %s has been applied', $id);
    }

    /**
     * General revert processing.
     *
     * @param string $path
     * @param string $id
     * @return string
     *
     * @throws ApplierException
     */
    public function revert(string $path, string $id): string
    {
        try {
            $this->processFactory->create(['git', 'apply', '--reverse', $path])
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            try {
                $this->processFactory->create(['git', 'apply', '--check', $path])
                    ->mustRun();
            } catch (ProcessFailedException $applyException) {
                throw new ApplierException($exception->getMessage(), $exception->getCode());
            }

            return sprintf('Patch %s wasn\'t applied', $id);
        }

        return sprintf('Patch %s has been reverted', $id);
    }

    /**
     * Checks patch status.
     *
     * @param string $patchContent
     * @return string
     */
    public function status(string $patchContent): string
    {
        try {
            $this->processFactory->create(['git', 'apply', '--check'], $patchContent)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            try {
                $this->processFactory->create(['git', 'apply', '--check', '--reverse'], $patchContent)
                    ->mustRun();
            } catch (ProcessFailedException $reverseException) {
                return StatusPool::NA;
            }

            return StatusPool::APPLIED;
        }

        return StatusPool::NOT_APPLIED;
    }
}
