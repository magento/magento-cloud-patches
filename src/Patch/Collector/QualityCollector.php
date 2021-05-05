<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector;

use Magento\CloudPatches\Composer\QualityPackage;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Composer\Package;
use Magento\CloudPatches\Patch\PatchBuilder;
use Magento\CloudPatches\Patch\PatchIntegrityException;

/**
 * Collects patches.
 */
class QualityCollector
{
    /**
     * Configuration JSON property.
     *
     * Contains patch filename, type string.
     */
    const PROP_FILE = 'file';

    /**
     * Configuration JSON property.
     *
     * Contains required patch ids, type array.
     */
    const PROP_REQUIRE = 'require';

    /**
     * Configuration JSON property.
     *
     * Contains patch id that current patch replaced with, type string.
     */
    const PROP_REPLACED_WITH = 'replaced-with';

    /**
     * Configuration JSON property.
     *
     * Defines whether patch is deprecated, type boolean.
     */
    const PROP_DEPRECATED = 'deprecated';

    /**
     * @var Package
     */
    private $package;

    /**
     * @var QualityPackage
     */
    private $qualityPackage;

    /**
     * @var PatchBuilder
     */
    private $patchBuilder;

    /**
     * @var GetPatchesConfigInterface
     */
    private $getPatchesConfig;

    /**
     * @param Package $package
     * @param QualityPackage $qualityPackage
     * @param PatchBuilder $patchBuilder
     * @param GetPatchesConfigInterface $getPatchesConfig
     */
    public function __construct(
        Package $package,
        QualityPackage $qualityPackage,
        PatchBuilder $patchBuilder,
        GetPatchesConfigInterface $getPatchesConfig
    ) {
        $this->package = $package;
        $this->qualityPackage = $qualityPackage;
        $this->patchBuilder = $patchBuilder;
        $this->getPatchesConfig = $getPatchesConfig;
    }

    /**
     * Collects quality patches.
     *
     * @throws CollectorException
     * @return PatchInterface[]
     */
    public function collect()
    {
        $result = [];
        foreach ($this->getPatchesConfig->execute() as $patchId => $patchGeneralConfig) {
            foreach ($patchGeneralConfig as $packageName => $packageConfiguration) {
                foreach ($packageConfiguration as $patchTitle => $patchInfo) {
                    foreach ($patchInfo as $packageConstraint => $patchData) {
                        $patchFile = $patchData[static::PROP_FILE];
                        $patchRequire = $patchData[static::PROP_REQUIRE] ?? [];
                        $patchReplacedWith = $patchData[static::PROP_REPLACED_WITH] ?? '';
                        $patchDeprecated = $patchData[static::PROP_DEPRECATED] ?? (bool)$patchReplacedWith;

                        if ($this->package->matchConstraint($packageName, $packageConstraint)) {
                            $result[] = $this->createPatch(
                                $patchId,
                                $patchTitle,
                                $patchFile,
                                $packageName,
                                $packageConstraint,
                                $patchRequire,
                                $patchReplacedWith,
                                $patchDeprecated
                            );
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Creates patch.
     *
     * @param string $patchId
     * @param string $patchTitle
     * @param string $patchFile
     * @param string $packageName
     * @param string $packageConstraint
     * @param array $patchRequire
     * @param string $patchReplacedWith
     * @param bool $patchDeprecated
     *
     * @return PatchInterface
     * @throws CollectorException
     */
    private function createPatch(
        string $patchId,
        string $patchTitle,
        string $patchFile,
        string $packageName,
        string $packageConstraint,
        array $patchRequire,
        string $patchReplacedWith,
        bool $patchDeprecated
    ): PatchInterface {
        try {
            $patchPath = $this->qualityPackage->getPatchesDirectory() . '/' . $patchFile;
            $this->patchBuilder->setId($patchId);
            $this->patchBuilder->setTitle($patchTitle);
            $this->patchBuilder->setFilename($patchFile);
            $this->patchBuilder->setPath($patchPath);
            $this->patchBuilder->setType(PatchInterface::TYPE_OPTIONAL);
            $this->patchBuilder->setPackageName($packageName);
            $this->patchBuilder->setPackageConstraint($packageConstraint);
            $this->patchBuilder->setRequire($patchRequire);
            $this->patchBuilder->setReplacedWith($patchReplacedWith);
            $this->patchBuilder->setDeprecated($patchDeprecated);
            $patch = $this->patchBuilder->build();
        } catch (PatchIntegrityException $e) {
            throw new CollectorException($e->getMessage(), $e->getCode(), $e);
        }

        return $patch;
    }
}
