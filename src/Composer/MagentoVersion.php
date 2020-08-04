<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Composer;

use Composer;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Semver\Semver;

/**
 * Defines version of Magento.
 */
class MagentoVersion
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var RootPackageInterface
     */
    private $rootPackage;

    /**
     * @var string
     */
    private $version;

    /**
     * @var array
     */
    private $editionMap = [
        'magento/magento2-b2b-base' => 'B2B Edition',
        'magento/magento2-ee-base' => 'Enterprise Edition',
        'magento/magento2ee' => 'Enterprise Edition',
        'magento/magento2ce' => 'Community Edition'
    ];

    /**
     * @var array
     */
    private $gitToComposerMap = [
        'magento/magento2ce' => ['magento/magento2-base'],
        'magento/magento2ee' => ['magento/magento2-base', 'magento/magento2-ee-base']
    ];

    /**
     * @param Composer\Composer $composer
     */
    public function __construct(
        Composer\Composer $composer
    ) {
        $this->rootPackage = $composer->getPackage();
        $this->repository = $composer->getRepositoryManager()->getLocalRepository();
    }

    /**
     * Returns Magento version and edition.
     *
     * @return string
     */
    public function get(): string
    {
        if (null !== $this->version) {
            return $this->version;
        }

        $this->version = 'Magento 2 is not installed';
        $basePackage = $this->repository->findPackage('magento/magento2-base', '*');
        if ($basePackage instanceof PackageInterface) {
            $version = $basePackage->getVersion();
            $edition = $this->getEdition();
            $this->version = 'Magento 2 ' . $edition . ', version ' . $version;
        } elseif ($this->isGitBased()) {
            $edition = $this->editionMap[$this->rootPackage->getName()];
            $this->version = 'Git-based: Magento 2 ' . $edition . ', version ' . $this->rootPackage->getVersion();
        }

        return $this->version;
    }

    /**
     * Checks if it's git-based installation.
     *
     * @return boolean
     */
    public function isGitBased(): bool
    {
        return isset($this->gitToComposerMap[$this->rootPackage->getName()]);
    }

    /**
     * Matches package on git-based Magento instance
     *
     * @param string $name
     * @param string $constraint
     * @return boolean
     */
    public function matchPackageGit(string $name, string $constraint): bool
    {
        return $this->isGitBased()
            && in_array($name, $this->gitToComposerMap[$this->rootPackage->getName()])
            && Semver::satisfies($this->rootPackage->getVersion(), $constraint);
    }

    /**
     * Returns Magento edition.
     *
     * @return string
     */
    private function getEdition(): string
    {
        foreach ($this->editionMap as $package => $edition) {
            if ($this->repository->findPackage($package, '*') instanceof PackageInterface) {
                return $edition;
            }
        }

        return 'Community Edition';
    }
}
