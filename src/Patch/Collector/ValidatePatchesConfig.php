<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CloudPatches\Patch\Collector;

class ValidatePatchesConfig
{
    /**
     * Validates patch configuration.
     *
     * @param array $config
     *
     * @return void
     * @throws CollectorException
     */
    public function execute(array $config)
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
        if (!isset($patchData[QualityCollector::PROP_FILE])) {
            $errors[] = sprintf(
                "Property '%s' is not found in '%s'",
                QualityCollector::PROP_FILE,
                $packageConstraint
            );
        }

        if (isset($patchData[QualityCollector::PROP_REQUIRE]) &&
            !is_array($patchData[QualityCollector::PROP_REQUIRE])
        ) {
            $errors[] = sprintf(
                "Property '%s' from '%s' should have an array type",
                QualityCollector::PROP_REQUIRE,
                $packageConstraint
            );
        }

        if (isset($patchData[QualityCollector::PROP_REPLACED_WITH]) &&
            !is_string($patchData[QualityCollector::PROP_REPLACED_WITH])
        ) {
            $errors[] = sprintf(
                "Property '%s' from '%s' should have a string type",
                QualityCollector::PROP_REPLACED_WITH,
                $packageConstraint
            );
        }

        if (isset($patchData[QualityCollector::PROP_DEPRECATED]) &&
            !is_bool($patchData[QualityCollector::PROP_DEPRECATED])
        ) {
            $errors[] = sprintf(
                "Property '%s' from '%s' should have a boolean type",
                QualityCollector::PROP_DEPRECATED,
                $packageConstraint
            );
        }

        return $errors;
    }
}
