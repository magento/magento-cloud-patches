<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Shell\Command;

use Magento\CloudPatches\Patch\PatchCommandException;
use Throwable;

/**
 * Patch command driver exception
 */
class DriverException extends PatchCommandException
{
    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($this->formatMessage($message), $code, $previous);
    }

    /**
     * Format error message
     *
     * @param string $message
     * @return string
     */
    private function formatMessage(string $message): string
    {
        $result = $message;
        $errorMsg = null;
        $generalMsg = null;
        if (preg_match('#^.*?Error Output:(?<errors>.*?)$#is', $result, $matches)) {
            $errorMsg = 'Error Output:' . $matches['errors'];
            $result = str_replace($errorMsg, '', $result);
            if (!trim(str_replace('=', '', $matches['errors']))) {
                $errorMsg = null;
            }
        }
        if (empty($errorMsg) && preg_match('#^.*?Output:(?<errors>.*?)$#is', $result, $matches)) {
            $generalMsg = 'Output:' . $matches['errors'];
            if (!trim(str_replace('=', '', $matches['errors']))) {
                $generalMsg = null;
            }
        }

        return $errorMsg ?? $generalMsg ?? $message;
    }
}
