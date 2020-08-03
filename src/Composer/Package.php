<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Composer;

use Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;

/**
 * Validates composer package version constraint.
 */
class Package
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param Composer\Composer $composer
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        Composer\Composer $composer,
        MagentoVersion $magentoVersion
    ) {
        $this->repository = $composer->getRepositoryManager()->getLocalRepository();
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Checks whether package with specific constraint exists in the system.
     *
     * @param string $packageName
     * @param string $packageConstraint
     * @return bool True if patch with provided constraint exists, false otherwise.
     */
    public function matchConstraint(string $packageName, string $packageConstraint): bool
    {
        return $this->magentoVersion->matchPackageGit($packageName, $packageConstraint) ||
            $this->repository->findPackage($packageName, $packageConstraint) instanceof PackageInterface;
    }
}
