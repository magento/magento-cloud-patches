<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\App;

use Magento\CloudPatches\App\Logger\LineFormatterFactory;
use Magento\CloudPatches\Filesystem\FileList;
use Magento\CloudPatches\Filesystem\Filesystem;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;

/**
 * @inheritdoc
 */
class Logger extends \Monolog\Logger
{
    /**
     * @param FileList $fileList
     * @param Logger\LineFormatterFactory $lineFormatterFactory
     * @param Filesystem $filesystem
     */
    public function __construct(
        FileList $fileList,
        LineFormatterFactory $lineFormatterFactory,
        Filesystem $filesystem
    ) {
        $handlers = [];
        $logPath = $fileList->getPatchLog();
        $logDir = $filesystem->getDirectory($logPath);
        $filesystem->createDirectory($logDir);

        if ($filesystem->isWritable($logDir)) {
            try {
                $handlerInstance = new StreamHandler($logPath, Logger::DEBUG);
                $formatter = $lineFormatterFactory->create();
                $handlerInstance->setFormatter($formatter);
                $handlers[] = $handlerInstance;
            } catch (\Exception $e) {
                $handlers[] = new NullHandler();
            }
        } else {
            $handlers[] = new NullHandler();
        }

        parent::__construct('default', $handlers);
    }

    /**
     * @inheritDoc
     */
    public function info($message, array $context = [])
    {
        $message = strip_tags($message);

        parent::info($message, $context);
    }
}
