<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Composer;
use Magento\CloudPatches\Filesystem\DirectoryList;
use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Shell\ProcessFactory;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Provides apply methods for patches.
 */
class Applier
{
    /**
     * @var Composer\Repository\RepositoryInterface
     */
    private $repository;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Composer\Composer $composer
     * @param ProcessFactory $processFactory
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     */
    public function __construct(
        Composer\Composer $composer,
        ProcessFactory $processFactory,
        DirectoryList $directoryList,
        Filesystem $filesystem
    ) {
        $this->repository = $composer->getRepositoryManager()->getLocalRepository();
        $this->processFactory = $processFactory;
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $path
     * @param bool $deployedFromGit
     * @return string
     *
     * @throws ApplierException
     */
    public function applyFile(string $path, bool $deployedFromGit): string
    {
        return $this->processApply($path, $path, $deployedFromGit);
    }

    /**
     * Applies patch, using 'git apply' command.
     *
     * If the patch fails to apply, checks if it has already been applied which is considered ok.
     *
     * @param string $path Path to patch
     * @param string $name Name of patch
     * @param string $packageName Name of package to be patched
     * @param string $constraint Specific constraint of package to be fixed
     * @param bool $deployedFromGit
     * @return string|null
     *
     * @throws ApplierException
     */
    public function apply(
        string $path,
        string $name,
        string $packageName,
        string $constraint,
        bool $deployedFromGit
    ) {
        $fullName = sprintf(
            '%s %s',
            sprintf('%s (%s)', $name, $path),
            $constraint
        );

        if ($packageName && !$this->matchConstraint($packageName, $constraint)) {
            return null;
        }

        /**
         * Support for relative paths.
         */
        if (!$this->filesystem->exists($path)) {
            $path = $this->directoryList->getPatches() . '/' . $path;
        }

        return $this->processApply($path, $fullName, $deployedFromGit);
    }

    /**
     * General apply processing.
     *
     * @param string $path
     * @param string $fullName
     * @param bool $deployedFromGit
     * @return string
     *
     * @throws ApplierException
     */
    private function processApply(string $path, string $fullName, bool $deployedFromGit): string
    {
        try {
            $this->processFactory->create(['git', 'apply', $path])
                ->mustRun();
        } catch (ProcessFailedException $exception) {
            if ($deployedFromGit) {
                return sprintf(
                    'Patch "%s" was not applied. (%s)',
                    $fullName,
                    $exception->getMessage()
                );
            }

            try {
                $this->processFactory->create(['git', 'apply', '--check', '--reverse', $path])
                    ->mustRun();
            } catch (ProcessFailedException $reverseException) {
                throw new ApplierException(
                    $reverseException->getMessage(),
                    $reverseException->getCode(),
                    $reverseException
                );
            }

            return sprintf(
                'Patch "%s" was already applied',
                $fullName
            );
        }

        return sprintf(
            'Patch "%s" applied',
            $fullName
        );
    }

    /**
     * Checks whether package with specific constraint exists in the system.
     *
     * @param string $packageName
     * @param string $constraint
     * @return bool True if patch with provided constraint exists, false otherwise.
     */
    private function matchConstraint(string $packageName, string $constraint): bool
    {
        return $this->repository->findPackage($packageName, $constraint) instanceof Composer\Package\PackageInterface;
    }
}
