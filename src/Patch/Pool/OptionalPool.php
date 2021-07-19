<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Pool;

use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\Collector\SupportCollector;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\PatchIntegrityException;
use Magento\CloudPatches\Patch\Collector\CommunityCollector;
use Magento\CloudPatches\Patch\CollectorInterface;

/**
 * Contains all optional patches.
 */
class OptionalPool
{
    /**
     * @var PatchInterface[]
     */
    private $items = [];

    /**
     * @param array $collectors
     * @throws CollectorException
     */
    public function __construct(array $collectors = [])
    {
        /** @var CollectorInterface $collector */
        foreach ($collectors as $collector) {
            $this->items = array_merge($this->items, $collector->collect());
        }
    }

    /**
     * Returns list of patches.
     *
     * @param string[] $filter
     * @param bool $useRequire
     *
     * @return PatchInterface[]
     * @throws PatchIntegrityException
     * @throws PatchNotFoundException
     */
    public function getList($filter = [], $useRequire = true)
    {
        if (empty($filter)) {
            return $this->items;
        }

        $result = [];
        foreach ($this->items as $patch) {
            if (in_array($patch->getId(), $filter)) {
                if ($patch->getRequire() && $useRequire) {
                    $result[] = $this->getRequireList($patch->getRequire());
                }
                $result[] = [$patch];
            }
        }

        $result = $result ? array_unique(array_merge(...$result)) : [];
        $this->validateSearchResult($filter, $result);

        return $result;
    }

    /**
     * Returns list of all optional patches.
     *
     * @return PatchInterface[]
     * @throws PatchIntegrityException
     */
    public function getOptionalAll(): array
    {
        return $this->getOptionalListByOrigin(
            [
                SupportCollector::ORIGIN,
                CommunityCollector::ORIGIN,
            ]
        );
    }

    /**
     * Returns list of all optional patches.
     *
     * @param array $listOfOrigins
     * @return PatchInterface[]
     * @throws PatchIntegrityException
     */
    public function getOptionalListByOrigin(array $listOfOrigins): array
    {
        $items = array_filter(
            $this->items,
            function ($patch) use ($listOfOrigins) {
                return $patch->getType() === PatchInterface::TYPE_OPTIONAL
                    && in_array($patch->getOrigin(), $listOfOrigins);
            }
        );

        $result = [];
        foreach ($items as $patch) {
            if ($patch->getRequire()) {
                $result[] = $this->getRequireList($patch->getRequire());
            }
            $result[] = [$patch];
        }
        return $result ? array_unique(array_merge(...$result)) : [];
    }

    /**
     * Returns patch ids dependent on provided patch if any.
     *
     * @param string $patchId
     * @return string[]
     */
    public function getDependentOn(string $patchId): array
    {
        if (!$patchId) {
            return [];
        }

        $result = [];
        foreach ($this->items as $patch) {
            if (in_array($patchId, $patch->getRequire())) {
                $result[] = $this->getDependentOn($patch->getId());
                $result[] = [$patch->getId()];
            }
        }

        $result = array_unique(array_merge([], ...$result));

        return $result;
    }

    /**
     * Returns patch dependency ids.
     *
     * @param string $patchId
     * @return string[]
     */
    public function getDependencies(string $patchId): array
    {
        $result = array_map(
            function (PatchInterface $patch) {
                return $patch->getId();
            },
            $this->getAdditionalRequiredPatches([$patchId])
        );

        return array_unique($result);
    }

    /**
     * Returns required patches which are not included in patch filter.
     *
     * @param string[] $filter
     *
     * @return PatchInterface[]
     * @throws PatchNotFoundException
     * @throws PatchIntegrityException
     */
    public function getAdditionalRequiredPatches($filter)
    {
        if (empty($filter)) {
            return [];
        }
        $patches = $this->getList($filter);

        return array_filter(
            $patches,
            function ($patch) use ($filter) {
                return !in_array($patch->getId(), $filter);
            }
        );
    }

    /**
     * Returns patch ids replaced by provided patch if any.
     *
     * @param string $patchId
     * @return string[]
     */
    public function getReplacedBy($patchId)
    {
        if (!$patchId) {
            return [];
        }

        $result = [];
        foreach ($this->items as $patch) {
            if ($patchId === $patch->getReplacedWith()) {
                $result[] = $patch->getId();
            }
        }

        return array_unique($result);
    }

    /**
     * Returns not deprecated patch ids by type.
     *
     * @param string $type
     * @return string[]
     */
    public function getIdsByType($type)
    {
        $items = array_filter(
            $this->items,
            function ($patch) use ($type) {
                return !$patch->isDeprecated() && $patch->getType() === $type;
            }
        );

        $result = array_map(
            function (PatchInterface $patch) {
                return $patch->getId();
            },
            $items
        );

        return array_unique($result);
    }

    /**
     * Validates search result.
     *
     * @param string[] $filter
     * @param PatchInterface[] $searchResult
     *
     * @return void
     * @throws PatchNotFoundException
     */
    private function validateSearchResult(array $filter, array $searchResult)
    {
        $resultIds = array_map(
            function (PatchInterface $patch) {
                return $patch->getId();
            },
            $searchResult
        );

        $diff = array_diff($filter, $resultIds);
        if (count($diff) > 0) {
            throw new PatchNotFoundException(
                'Next patches weren\'t found: ' . implode(' ', $diff)  . '. ' .
                'Please, check with "status" command availability of these patches for the current Magento version.'
            );
        }
    }

    /**
     * Returns a list of patches for 'require' configuration option.
     *
     * @param array $require
     * @return PatchInterface[]
     * @throws PatchIntegrityException
     */
    private function getRequireList(array $require): array
    {
        try {
            return $this->getList($require);
        } catch (PatchNotFoundException $e) {
            throw new PatchIntegrityException('Configuration error - ' . $e->getMessage(), $e->getCode());
        }
    }
}
