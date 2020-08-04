<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Shell;

use Composer\Composer;
use Composer\Repository\RepositoryInterface;
use Composer\Semver\Comparator;
use Magento\CloudPatches\Filesystem\DirectoryList;
use Symfony\Component\Process\Process;

/**
 * Factory method for Process.
 *
 * @see Process
 */
class ProcessFactory
{
    const ARRAY_PARAM_MIN_VERSION = '3.3.0';

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList, Composer $composer)
    {
        $this->directoryList = $directoryList;
        $this->repository = $composer->getLocker()->getLockedRepository();
    }

    /**
     * Creates process.
     *
     * @param array $cmd
     * @param string|null $input
     * @return Process
     * @throws PackageNotFoundException
     */
    public function create(array $cmd, string $input = null): Process
    {
        return new Process(
            $this->processSupportsArrayParam() ? $cmd : implode(' ', $cmd),
            $this->directoryList->getMagentoRoot(),
            null,
            $input
        );
    }

    /**
     * Test if symfony/process is current enough to support an array for its first parameter.
     *
     * @throws PackageNotFoundException
     */
    private function processSupportsArrayParam(): bool
    {
        $package = $this->repository->findPackage('symfony/process', '*');

        if ($package === null) {
            throw new PackageNotFoundException('Could not find symfony/process package.');
        }

        return Comparator::greaterThanOrEqualTo($package->getVersion(), self::ARRAY_PARAM_MIN_VERSION);
    }
}
