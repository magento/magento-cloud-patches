<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\App\RuntimeException;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Status\StatusPool;

/**
 * Validates that patches can be reverted.
 */
class RevertValidator
{
    /**
     * @var OptionalPool
     */
    private $optionalPool;

    /**
     * @var StatusPool
     */
    private $statusPool;

    /**
     * @param OptionalPool $optionalPool
     * @param StatusPool $statusPool
     */
    public function __construct(
        OptionalPool $optionalPool,
        StatusPool $statusPool
    ) {
        $this->optionalPool = $optionalPool;
        $this->statusPool = $statusPool;
    }

    /**
     * Verifies that there are no applied dependent patches.
     *
     * @param string[] $patchFilter
     * @return void
     * @throws RuntimeException
     */
    public function validate(array $patchFilter)
    {
        $errors = [];
        foreach ($patchFilter as $patchId) {
            $appliedDependents = array_filter(
                $this->optionalPool->getDependentOn($patchId),
                function ($patchId) {
                    return $this->statusPool->isApplied($patchId);
                }
            );
            if (array_diff($appliedDependents, $patchFilter)) {
                $errors[] = sprintf(
                    'Patch %1$s is a dependency for %2$s. Please, revert %2$s first',
                    $patchId,
                    implode(' ', $appliedDependents)
                );
            }
        }

        if ($errors) {
            throw new RuntimeException(implode(PHP_EOL, $errors));
        }
    }
}
