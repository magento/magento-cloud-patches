<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process\Action;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The pool of actions.
 */
class ActionPool implements ActionInterface
{
    /**
     * @var ActionInterface[]
     */
    private $actions;

    /**
     * @param ActionInterface[] $actions
     */
    public function __construct(array $actions)
    {
        foreach ($actions as $action) {
            if (!$action instanceof ActionInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Instance of %s is expected, got %s instead.',
                        ActionInterface::class,
                        get_class($action)
                    )
                );
            }
        }

        $this->actions = $actions;
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output, array $patchFilter)
    {
        foreach ($this->actions as $action) {
            $action->execute($input, $output, $patchFilter);
        }
    }
}
