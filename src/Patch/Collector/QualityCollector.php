<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector;

use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Composer\Package;
use Magento\CloudPatches\Patch\PatchFactory;
use Magento\CloudPatches\Patch\PatchIntegrityException;
use Magento\CloudPatches\Patch\SourceProvider;
use Magento\CloudPatches\Patch\SourceProviderException;
use Magento\QualityPatches\Info as QualityPatchesInfo;

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
     * @var PatchFactory
     */
    private $patchFactory;

    /**
     * @var SourceProvider
     */
    private $sourceProvider;

    /**
     * @var Package
     */
    private $package;

    /**
     * @var QualityPatchesInfo
     */
    private $qualityPatchesInfo;

    /**
     * @var array|null
     */
    private $config = null;

    /**
     * @param PatchFactory $patchFactory
     * @param SourceProvider $sourceProvider
     * @param Package $package
     * @param QualityPatchesInfo $qualityPatchesInfo
     */
    public function __construct(
        PatchFactory $patchFactory,
        SourceProvider $sourceProvider,
        Package $package,
        QualityPatchesInfo $qualityPatchesInfo
    ) {
        $this->patchFactory = $patchFactory;
        $this->sourceProvider = $sourceProvider;
        $this->package = $package;
        $this->qualityPatchesInfo = $qualityPatchesInfo;
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
            $patchPath = $this->qualityPatchesInfo->getPatchesDirectory() . '/' . $patchFile;
            $patch = $this->patchFactory->create(
                $patchId,
                $patchTitle,
                $patchFile,
                $patchPath,
                PatchInterface::TYPE_OPTIONAL,
                $packageName,
                $packageConstraint,
                $patchRequire,
                $patchReplacedWith,
                $patchDeprecated
            );
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
