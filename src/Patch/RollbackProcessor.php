<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

use Magento\CloudPatches\Patch\Data\PatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Rollback applied patches.
 */
class RollbackProcessor
{
    /**
     * @var Applier
     */
    private $applier;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Applier $applier
     * @param LoggerInterface $logger
     */
    public function __construct(
        Applier $applier,
        LoggerInterface $logger
    ) {
        $this->applier = $applier;
        $this->logger = $logger;
    }

    /**
     * Rollback applied patches.
     *
     * @param PatchInterface[] $appliedPatches
     * @return string[]
     */
    public function process(array $appliedPatches): array
    {
        if (empty($appliedPatches)) {
            return [];
        }

        $message = 'Start of rollback';
        $this->logger->info($message);
        $messages[] = $message;

        foreach (array_reverse($appliedPatches) as $appliedPatch) {
            $message = $this->applier->revert($appliedPatch->getPath(), $appliedPatch->getId());
            $messages[] = $message;
            $this->logger->info($message, ['file' => $appliedPatch->getPath()]);
        }

        $message = 'End of rollback';
        $this->logger->info($message);
        $messages[] = $message;

        return $messages;
    }
}
