<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Filesystem;

use Magento\CloudPatches\Patch\SourceProviderException;

/**
 * Json Reader.
 */
class JsonConfigReader
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $configPath
     * @return array
     * @throws SourceProviderException
     */
    public function read(string $configPath): array
    {
        try {
            $content = $this->filesystem->get($configPath);
        } catch (FileSystemException $e) {
            throw new SourceProviderException($e->getMessage(), $e->getCode(), $e);
        }
        $result = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SourceProviderException(
                "Unable to unserialize configuration '{$configPath}'. Error: " . json_last_error_msg()
            );
        }
        return $result;
    }
}
