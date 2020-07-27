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
 * Defines version of Magento.
 */
class MagentoVersion
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string[]
     */
    private $editionMap = [
        'magento/magento2-b2b-base' => 'B2B Edition',
        'magento/magento2-ee-base' => 'Enterprise Edition'
    ];

    /**
     * @param Composer\Composer $composer
     */
    public function __construct(
        Composer\Composer $composer
    ) {
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
        }

        return $this->version;
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
