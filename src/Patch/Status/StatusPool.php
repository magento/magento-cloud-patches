<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Status;

use InvalidArgumentException;

/**
 * Contains statuses of patches.
 */
class StatusPool
{
    /**
     * Patch was applied.
     */
    const APPLIED = 'Applied';

    /**
     * Status of patch can't be identified. There are some conflicts caused by other patches.
     */
    const NA = 'N/A';

    /**
     * Patch is ready to apply.
     */
    const NOT_APPLIED = 'Not applied';

    /**
     * @var array
     */
    private $result;

    /**
     * @param ResolverInterface[] $resolvers
     */
    public function __construct(
        array $resolvers
    ) {
        $result = [];
        foreach ($resolvers as $resolver) {
            if (!$resolver instanceof ResolverInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Instance of %s is expected, got %s instead.',
                        ResolverInterface::class,
                        get_class($resolver)
                    )
                );
            }
            $result[] = $resolver->resolve();
        }
        $this->result = array_merge([], ...$result);
    }

    /**
     * Returns status of patch.
     *
     * @param string $id
     * @return string
     */
    public function get($id)
    {
        return $this->result[$id] ?? self::NA;
    }

    /**
     * Checks if patch is applied.
     *
     * @param string $id
     * @return bool
     */
    public function isApplied($id)
    {
        return $this->result[$id] === self::APPLIED;
    }

    /**
     * Checks if patch is not applied.
     *
     * @param string $id
     * @return bool
     */
    public function isNotApplied($id)
    {
        return $this->result[$id] === self::NOT_APPLIED;
    }
}
