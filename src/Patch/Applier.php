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

/**
 * Applies and reverts patches.
 */
class Applier
{
    /**
     * @var PatchCommandInterface
     */
    private $patchCommand;

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
     * @param PatchCommandInterface $patchCommand
     * @param GitConverter $gitConverter
     * @param MagentoVersion $magentoVersion
     * @param Filesystem $filesystem
     */
    public function __construct(
        PatchCommandInterface $patchCommand,
        GitConverter $gitConverter,
        MagentoVersion $magentoVersion,
        Filesystem $filesystem
    ) {
        $this->patchCommand = $patchCommand;
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
            $this->patchCommand->apply($content);
        } catch (PatchCommandException $exception) {
            try {
                $this->patchCommand->revertCheck($content);
            } catch (PatchCommandException $reverseException) {
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
            $this->patchCommand->revert($content);
        } catch (PatchCommandException $exception) {
            try {
                $this->patchCommand->applyCheck($content);
            } catch (PatchCommandException $applyException) {
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
            $this->patchCommand->applyCheck($patchContent);
        } catch (PatchCommandException $exception) {
            try {
                $this->patchCommand->revertCheck($patchContent);
            } catch (PatchCommandException $reverseException) {
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
            $this->patchCommand->applyCheck($patchContent);
        } catch (PatchCommandException $exception) {
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

        $content = $this->replaceDiffGit($content);

        return $content;
    }

    /**
     * Replace `diff --git`
     *
     * In some environments 'git apply' command skips applying a patch
     * while execution inside the git working tree. To prevent this issue
     * we need to replace `diff --git` with `diff -Nuar` in the patch source.
     *
     * @param string $content
     * @return string
     */
    private function replaceDiffGit(string $content): string
    {
        return preg_replace(['/^diff --git/', '/\ndiff --git/'], ['diff -Nuar', "\ndiff -Nuar"], $content);
    }
}
