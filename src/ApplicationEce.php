<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches;

use Composer\Composer;
use Composer\InstalledVersions;
use Magento\CloudPatches\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Application as SymfonyApplication;
use Throwable;

/**
 * @inheritDoc
 */
class ApplicationEce extends SymfonyApplication
{
    /**
     * Default package name to look for in InstalledVersions and composer.json.
     */
    private const DEFAULT_PACKAGE_NAME = 'magento/magento-cloud-patches';

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * Command classes registered by default in the application.
     *
     * @var class-string<SymfonyCommand>[]
     */
    private const DEFAULT_COMMAND_CLASSES = [
        Command\Ece\Apply::class,
        Command\Ece\Revert::class,
        Command\Status::class,
        Command\Verify::class
    ];

    /**
     * Initialize the application and resolve its name and version.
     *
     * Version information is resolved from InstalledVersions,
     * composer.json, or Composer package metadata.
     *
     * @param ContainerInterface $container Application container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container  = $container;
        [$name, $version] = $this->resolveApplicationMetadata($container);

        parent::__construct($name, $version);
    }

    /**
     * Read composer.json safely.
     * This method attempts to read the composer.json file and decode its contents.
     * It handles potential errors gracefully, returning null if the file cannot be read or parsed.
     *
     * @return array|null Decoded composer.json data or null if unavailable.
     */
    private function readComposerJson(): ?array
    {
        if (class_exists(InstalledVersions::class)
            && InstalledVersions::isInstalled(self::DEFAULT_PACKAGE_NAME)
        ) {
            $installPath = InstalledVersions::getInstallPath(self::DEFAULT_PACKAGE_NAME);

            if ($installPath) {
                $path = $installPath . '/composer.json';

                if (is_file($path)) {
                    try {
                        return json_decode(
                            (string) file_get_contents($path),
                            true,
                            512,
                            JSON_THROW_ON_ERROR
                        );
                    } catch (Throwable $e) {
                        return null;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Resolve application name and version.
     * This method ensures that the application can accurately report its own name and
     * version regardless of how it is installed or executed.
     *
     * @param ContainerInterface $container The application container used to create Composer instance if needed.
     * @return array An array containing the resolved application name and version.
     */
    private function resolveApplicationMetadata(ContainerInterface $container): array
    {
        // Read composer.json to get the default package name dynamically
        $composerJson   = $this->readComposerJson();
        $defaultPackage = $composerJson['name'] ?? self::DEFAULT_PACKAGE_NAME;

        // 1️. Composer InstalledVersions: If the package is installed via Composer,
        //                                 it retrieves the name and version from InstalledVersions.
        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled($defaultPackage)) {
            return [
                $defaultPackage,
                InstalledVersions::getPrettyVersion($defaultPackage)
                ?? InstalledVersions::getVersion($defaultPackage)
                ?? 'unknown'
            ];
        }

        // 2. composer.json: If InstalledVersions is not available,
        //                   it uses the already-read composer.json data.
        if ($composerJson) {
            return [
                $composerJson['name'] ?? self::DEFAULT_PACKAGE_NAME,
                $composerJson['version'] ?? 'unknown'
            ];
        }

        // 3️. Composer runtime metadata: If both previous methods fail, it falls back to using the Composer
        //                                runtime metadata to determine the name and version.
        $composer = $container->get(Composer::class);
        $package  = $composer->getPackage();

        return [
            $package->getPrettyName(),
            $package->getPrettyVersion()
        ];
    }

    /**
     * Get the default commands that should always be available.
     *
     * @return SymfonyCommand[]
     */
    protected function getDefaultCommands(): array
    {
        $instances = array_map(
            fn (string $commandClass): SymfonyCommand =>
                $this->container->get($commandClass),
            self::DEFAULT_COMMAND_CLASSES
        );

        return array_merge(parent::getDefaultCommands(), $instances);
    }
}
