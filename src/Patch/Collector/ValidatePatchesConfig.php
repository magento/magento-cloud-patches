<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Collector;

/**
 * Patch configuration validator.
 */
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
            foreach ($patchGeneralConfig['packages'] as $packageConfiguration) {
                foreach ($packageConfiguration as $packageConstraint => $patchData) {
                    $errors = $this->validateProperties($patchData, $packageConstraint, $errors);
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function validateProperties(
        array $patchData,
        string $packageConstraint,
        array $errors
    ): array {
        if (!isset($patchData[SupportCollector::PROP_FILE])) {
            $errors[] = sprintf(
                "Property '%s' is not found in '%s'",
                SupportCollector::PROP_FILE,
                $packageConstraint
            );
        }

        if (isset($patchData[SupportCollector::PROP_REQUIRE]) &&
            !is_array($patchData[SupportCollector::PROP_REQUIRE])
        ) {
            $errors[] = sprintf(
                "Property '%s' from '%s' should have an array type",
                SupportCollector::PROP_REQUIRE,
                $packageConstraint
            );
        }

        if (isset($patchData[SupportCollector::PROP_REPLACED_WITH]) &&
            !is_string($patchData[SupportCollector::PROP_REPLACED_WITH])
        ) {
            $errors[] = sprintf(
                "Property '%s' from '%s' should have a string type",
                SupportCollector::PROP_REPLACED_WITH,
                $packageConstraint
            );
        }

        if (isset($patchData[SupportCollector::PROP_DEPRECATED]) &&
            !is_bool($patchData[SupportCollector::PROP_DEPRECATED])
        ) {
            $errors[] = sprintf(
                "Property '%s' from '%s' should have a boolean type",
                SupportCollector::PROP_DEPRECATED,
                $packageConstraint
            );
        }

        if (isset($patchData[SupportCollector::PROP_CATEGORIES]) &&
            !is_array($patchData[SupportCollector::PROP_CATEGORIES])
        ) {
            $errors[] = sprintf(
                "Property '%s' from '%s' should have a array type",
                SupportCollector::PROP_DEPRECATED,
                $packageConstraint
            );
        }

        return $errors;
    }
}
