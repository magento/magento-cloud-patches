<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\Composer\MagentoVersion;
use Magento\CloudPatches\Filesystem\Filesystem;
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
     * @var GitConverter
     */
    private $gitConverter;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param ProcessFactory $processFactory
     * @param GitConverter $gitConverter
     * @param MagentoVersion $magentoVersion
     * @param Filesystem $filesystem
     */
    public function __construct(
        ProcessFactory $processFactory,
        GitConverter $gitConverter,
        MagentoVersion $magentoVersion,
        Filesystem $filesystem
    ) {
        $this->processFactory = $processFactory;
        $this->gitConverter = $gitConverter;
        $this->magentoVersion = $magentoVersion;
        $this->filesystem = $filesystem;
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
        $content = $this->readContent($path);
        try {
            $this->processFactory->create(['git', 'apply'], $content)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            try {
                $this->processFactory->create(['git', 'apply', '--check', '--reverse'], $content)
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
        $content = $this->readContent($path);
        try {
            $this->processFactory->create(['git', 'apply', '--reverse'], $content)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            try {
                $this->processFactory->create(['git', 'apply', '--check'], $content)
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
        $patchContent = $this->prepareContent($patchContent);
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

    /**
     * Checks if the patch can be applied.
     *
     * @param string $patchContent
     * @return boolean
     */
    public function checkApply(string $patchContent): bool
    {
        $patchContent = $this->prepareContent($patchContent);
        try {
            $this->processFactory->create(['git', 'apply', '--check'], $patchContent)
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Returns patch content.
     *
     * @param string $path
     * @return string
     */
    private function readContent(string $path): string
    {
        $content = $this->filesystem->get($path);

        return $this->prepareContent($content);
    }

    /**
     * Prepares patch content.
     *
     * @param string $content
     * @return string
     */
    private function prepareContent(string $content): string
    {
        if ($this->magentoVersion->isGitBased()) {
            $content = $this->gitConverter->convert($content);
        }

        return $content;
    }
}
