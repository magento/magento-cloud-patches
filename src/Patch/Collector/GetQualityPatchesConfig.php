<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CloudPatches\Patch\Collector;

use Magento\CloudPatches\Patch\SourceProvider;
use Magento\CloudPatches\Patch\SourceProviderException;

class GetQualityPatchesConfig implements GetPatchesConfigInterface
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @var SourceProvider
     */
    private $sourceProvider;

    /**
     * @var ValidatePatchesConfig
     */
    private $validatePatchesConfig;

    /**
     * @param SourceProvider $sourceProvider
     * @param ValidatePatchesConfig $validatePatchesConfig
     */
    public function __construct(SourceProvider $sourceProvider, ValidatePatchesConfig $validatePatchesConfig) {
        $this->sourceProvider = $sourceProvider;
        $this->validatePatchesConfig = $validatePatchesConfig;
    }

    /**
     * @return array
     * @throws CollectorException
     */
    public function execute(): array
    {
        if (empty($this->config)) {
            try {
                $this->config = $this->sourceProvider->getQualityPatches();
            } catch (SourceProviderException $e) {
                throw new CollectorException($e->getMessage(), $e->getCode(), $e);
            }
            $this->validatePatchesConfig->execute($this->config);
        }

        return $this->config;
    }
}
