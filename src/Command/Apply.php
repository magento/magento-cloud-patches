<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command;

use Magento\CloudPatches\Command\Patch\Manager;
use Magento\CloudPatches\Command\Patch\ManagerException;
use Magento\CloudPatches\Patch\ApplierException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class Apply extends Command
{
    const NAME = 'apply';

    const OPT_GIT_INSTALLATION = 'git-installation';

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;

        parent::__construct(self::NAME);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Apply patches')
            ->addOption(
                self::OPT_GIT_INSTALLATION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Is git installation',
                false
            );

        parent::configure();
    }

    /**
     * {@inheritDoc}
     *
     * @throws ManagerException
     * @throws ApplierException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->manager->applyComposerPatches($input, $output);
        $this->manager->applyHotFixes($input, $output);
    }
}
