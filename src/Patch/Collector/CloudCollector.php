<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector;

use Magento\CloudPatches\Environment\Config;
use Magento\CloudPatches\Filesystem\DirectoryList;
use Magento\CloudPatches\Patch\CollectorInterface;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Composer\Package;
use Magento\CloudPatches\Patch\PatchBuilder;
use Magento\CloudPatches\Patch\PatchIntegrityException;
use Magento\CloudPatches\Patch\SourceProvider;
use Magento\CloudPatches\Patch\SourceProviderException;

/**
 * Collects cloud patches.
 */
class CloudCollector implements CollectorInterface
{
    /**
     * @var SourceProvider
     */
    private $sourceProvider;

    /**
     * @var Package
     */
    private $package;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Config
     */
    private $envConfig;

    /**
     * @var PatchBuilder
     */
    private $patchBuilder;

    /**
     * @param SourceProvider $sourceProvider
     * @param Package $package
     * @param DirectoryList $directoryList
     * @param Config $envConfig
     * @param PatchBuilder $patchBuilder
     */
    public function __construct(
        SourceProvider $sourceProvider,
        Package $package,
        DirectoryList $directoryList,
        Config $envConfig,
        PatchBuilder $patchBuilder
    ) {
        $this->sourceProvider = $sourceProvider;
        $this->package = $package;
        $this->directoryList = $directoryList;
        $this->envConfig = $envConfig;
        $this->patchBuilder = $patchBuilder;
    }

    /**
     * Collects quality patches.
     *
     * @return PatchInterface[]
     * @throws CollectorException
     */
    public function collect(): array
    {
        try {
            $config = $this->sourceProvider->getCloudPatches();
        } catch (SourceProviderException $e) {
            throw new CollectorException($e->getMessage(), $e->getCode(), $e);
        }

        $result = [];
        foreach ($config as $packageName => $packagePatches) {
            foreach ($packagePatches as $patchTitle => $patchConfiguration) {
                $this->validatePatchConfiguration($patchConfiguration, $patchTitle);
                foreach ($patchConfiguration as $packageConstraint => $patchData) {
                    $patchFile = $patchData;
                    $patchId = $this->getPatchId($patchFile);
                    if ($this->package->matchConstraint($packageName, $packageConstraint)) {
                        try {
                            $patchPath = $this->directoryList->getPatches() . '/' . $patchFile;
                            $patchType = $this->envConfig->isCloud()
                                ? PatchInterface::TYPE_REQUIRED : PatchInterface::TYPE_OPTIONAL;

                            $this->patchBuilder->setId($patchId);
                            $this->patchBuilder->setTitle($patchTitle);
                            $this->patchBuilder->setFilename($patchFile);
                            $this->patchBuilder->setPath($patchPath);
                            $this->patchBuilder->setType($patchType);
                            $this->patchBuilder->setPackageName($packageName);
                            $this->patchBuilder->setPackageConstraint($packageConstraint);
                            $this->patchBuilder->setOrigin(SupportCollector::ORIGIN);
                            $this->patchBuilder->setCategories(['Other']);

                            $result[] = $this->patchBuilder->build();
                        } catch (PatchIntegrityException $e) {
                            throw new CollectorException($e->getMessage(), $e->getCode(), $e);
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
     * @param array|string $configuration
     * @param string $title
     * @return void
     * @throws CollectorException
     */
    private function validatePatchConfiguration($configuration, string $title)
    {
        if (!is_array($configuration) || empty($configuration)) {
            throw new CollectorException(
                "Patch '{$title}' has invalid configuration. Should be not empty array."
            );
        }
    }

    /**
     * Extract patchId from filename.
     *
     * @param string $patchFile
     * @return string
     * @throws CollectorException
     */
    private function getPatchId(string $patchFile): string
    {
        $result = preg_match(
            '#(?<id>.*?)__(?<description>.*?)__(?<version>.*?)\.patch#',
            $patchFile,
            $patch
        );
        if (!$result) {
            throw new CollectorException(
                "The patch filename '{$patchFile}' has invalid format." . PHP_EOL .
                "Correct format: <TICKET_NUMBER>__<TITLE>__<PACKAGE_VERSION>.patch". PHP_EOL .
                "Example: MAGECLOUD-2899__fix_redis_slave_configuration__2.3.0.patch"
            );
        }

        return $patch['id'];
    }
}
