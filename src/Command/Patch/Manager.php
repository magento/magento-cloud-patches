<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Patch;

use Magento\CloudPatches\Command\Apply;
use Magento\CloudPatches\Filesystem\DirectoryList;
use Magento\CloudPatches\Filesystem\FileList;
use Magento\CloudPatches\Filesystem\FileNotFoundException;
use Magento\CloudPatches\Filesystem\Filesystem;
use Magento\CloudPatches\Patch\Applier;
use Magento\CloudPatches\Patch\ApplierException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Manages patches appliance.
 */
class Manager
{
    /**
     * Directory for hot-fixes.
     */
    const HOT_FIXES_DIR = 'm2-hotfixes';

    /**
     * @var Applier
     */
    private $applier;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param Applier $applier
     * @param Filesystem $filesystem
     * @param FileList $fileList
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Applier $applier,
        Filesystem $filesystem,
        FileList $fileList,
        DirectoryList $directoryList
    ) {
        $this->applier = $applier;
        $this->filesystem = $filesystem;
        $this->fileList = $fileList;
        $this->directoryList = $directoryList;
    }

    /**
     * Applies patches from composer.json file.
     * Patches are applying from top to bottom of config list.
     *
     * ```
     *  "colinmollenhour/credis" : {
     *      "Fix Redis issue": {
     *          "1.6": "patches/redis-pipeline.patch"
     *      }
     *  }
     *
     * Each patch must have corresponding constraint of target package,
     * in one of the following format:
     * - 1.6
     * - 1.6.*
     * - ^1.6
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws ManagerException
     * @throws ApplierException
     */
    public function applyComposerPatches(InputInterface $input, OutputInterface $output)
    {
        try {
            $content = $this->filesystem->get($this->fileList->getPatches());
        } catch (FileNotFoundException $exception) {
            throw new ManagerException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $patches = json_decode($content, true);

        if (!$patches) {
            $output->writeln('Composer patches not found');

            return;
        }

        $deployedFromGit = $input->getOption(Apply::OPT_GIT_INSTALLATION);

        foreach ($patches as $packageName => $patchesInfo) {
            foreach ($patchesInfo as $patchName => $packageInfo) {
                if (!is_array($packageInfo)) {
                    throw new ManagerException('Wrong patch constraints');
                }

                foreach ($packageInfo as $constraint => $path) {
                    $message = $this->applier->apply(
                        (string)$path,
                        (string)$patchName,
                        (string)$packageName,
                        (string)$constraint,
                        $deployedFromGit
                    );

                    if (null !== $message) {
                        $output->writeln($message);
                    }
                }
            }
        }
    }

    /**
     * Applies patches from root directory m2-hotfixes.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws ApplierException
     */
    public function applyHotFixes(InputInterface $input, OutputInterface $output)
    {
        $hotFixesDir = $this->directoryList->getMagentoRoot() . '/' . static::HOT_FIXES_DIR;

        if (!$this->filesystem->isDirectory($hotFixesDir)) {
            $output->writeln('Hot-fixes directory was not found. Skipping');

            return;
        }

        $files = glob($hotFixesDir . '/*.patch');
        sort($files);

        $deployedFromGit = $input->getOption(Apply::OPT_GIT_INSTALLATION);

        $output->writeln('Applying hot-fixes');

        foreach ($files as $file) {
            $output->writeln(
                $this->applier->applyFile($file, $deployedFromGit)
            );
        }
    }
}
