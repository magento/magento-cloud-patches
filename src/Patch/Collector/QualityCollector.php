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
use Magento\CloudPatches\Patch\SourceProvider;
use Magento\CloudPatches\Patch\SourceProviderException;

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
     * @var SourceProvider
     */
    private $sourceProvider;

    /**
     * @var Package
     */
    private $package;

    /**
     * @var QualityPackage
     */
    private $qualityPackage;

    /**
     * @var array|null
     */
    private $config = null;

    /**
     * @var PatchBuilder
     */
    private $patchBuilder;

    /**
     * @param SourceProvider $sourceProvider
     * @param Package $package
     * @param QualityPackage $qualityPackage
     * @param PatchBuilder $patchBuilder
     */
    public function __construct(
        SourceProvider $sourceProvider,
        Package $package,
        QualityPackage $qualityPackage,
        PatchBuilder $patchBuilder
    ) {
        $this->sourceProvider = $sourceProvider;
        $this->package = $package;
        $this->qualityPackage = $qualityPackage;
        $this->patchBuilder = $patchBuilder;
    }

    /**
     * Collects quality patches.
     *
     * @return PatchInterface[]
     *
     * @throws CollectorException
     */
    public function collect()
    {
        $result = [];
        foreach ($this->getConfig() as $patchId => $patchGeneralConfig) {
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
     * Validates patch configuration.
     *
     * @param array $config
     *
     * @return void
     * @throws CollectorException
     */
    private function validateConfiguration(array $config)
    {
        foreach ($config as $patchId => $patchGeneralConfig) {
            $errors = [];
            foreach ($patchGeneralConfig as $packageConfiguration) {
                foreach ($packageConfiguration as $patchInfo) {
                    foreach ($patchInfo as $packageConstraint => $patchData) {
                        $errors = $this->validateProperties($patchData, $packageConstraint, $errors);
                    }
                }
            }

            if (!empty($errors)) {
                array_unshift($errors, "Patch {$patchId} has invalid configuration:");

                throw new CollectorException(implode(PHP_EOL . ' - ', $errors));
            }
        }
    }

    /**
     * Returns patches config.
     *
     * @return array
     * @throws CollectorException
     */
    private function getConfig(): array
    {
        if ($this->config === null) {
            try {
                $this->config = $this->sourceProvider->getQualityPatches();
            } catch (SourceProviderException $e) {
                throw new CollectorException($e->getMessage(), $e->getCode(), $e);
            }
            $this->validateConfiguration($this->config);
        }

        return $this->config;
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

    /**
     * Validates properties.
     *
     * @param array $patchData
     * @param string $packageConstraint
     * @param string[] $errors
     * @return array
     */
    private function validateProperties(
        array $patchData,
        string $packageConstraint,
        array $errors
    ): array {
        if (!isset($patchData[static::PROP_FILE])) {
            $errors[] = sprintf(
                "Property '%s' is not found in '%s'",
                static::PROP_FILE,
                $packageConstraint
            );
        }

        if (isset($patchData[static::PROP_REQUIRE]) &&
            !is_array($patchData[static::PROP_REQUIRE])
        ) {
            $errors[] = sprintf(
                "Property '%s' from '%s' should have an array type",
                static::PROP_REQUIRE,
                $packageConstraint
            );
        }

        if (isset($patchData[static::PROP_REPLACED_WITH]) &&
            !is_string($patchData[static::PROP_REPLACED_WITH])
        ) {
            $errors[] = sprintf(
                "Property '%s' from '%s' should have a string type",
                static::PROP_REPLACED_WITH,
                $packageConstraint
            );
        }

        if (isset($patchData[static::PROP_DEPRECATED]) &&
            !is_bool($patchData[static::PROP_DEPRECATED])
        ) {
            $errors[] = sprintf(
                "Property '%s' from '%s' should have a boolean type",
                static::PROP_DEPRECATED,
                $packageConstraint
            );
        }

        return $errors;
    }
}
