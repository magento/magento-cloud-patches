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
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Applies and reverts patches.
 */
class Applier
{
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
     * @var PatchCommandInterface
     */
    private $patchCommand;

    /**
     * @param GitConverter $gitConverter
     * @param MagentoVersion $magentoVersion
     * @param Filesystem $filesystem
     * @param PatchCommandInterface $patchCommand
     */
    public function __construct(
        GitConverter $gitConverter,
        MagentoVersion $magentoVersion,
        Filesystem $filesystem,
        PatchCommandInterface $patchCommand
    ) {
        $this->gitConverter = $gitConverter;
        $this->magentoVersion = $magentoVersion;
        $this->filesystem = $filesystem;
        $this->patchCommand = $patchCommand;
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
            $result = $this->patchCommand->apply($content);
        } catch (ProcessFailedException $exception) {
            throw new ApplierException($exception->getMessage(), $exception->getCode());
        }

        return $result ? sprintf('Patch %s has been applied', $id) : sprintf('Patch %s was already applied', $id);
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
            $result = $this->patchCommand->revert($content);
        } catch (ProcessFailedException $exception) {
            throw new ApplierException($exception->getMessage(), $exception->getCode());
        }

        return $result ? sprintf('Patch %s has been reverted', $id) : sprintf('Patch %s wasn\'t applied', $id);
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
            $result = $this->patchCommand->status($patchContent);
        } catch (ProcessFailedException $exception) {
            return StatusPool::NA;
        }

        return $result ? StatusPool::NOT_APPLIED : StatusPool::APPLIED;
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

        return $this->patchCommand->check($patchContent);
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
