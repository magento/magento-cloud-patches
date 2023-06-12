<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch\Data;

/**
 * Patch data interface.
 */
interface PatchInterface
{
    /**
     * Patch is required (Cloud patches on Cloud).
     */
    const TYPE_REQUIRED = 'Required';

    /**
     * Patch is optional.
     */
    const TYPE_OPTIONAL = 'Optional';

    /**
     * Patch is client specific (m2-hotfixes on Cloud).
     */
    const TYPE_CUSTOM = 'Custom';

    /**
     * Patch ID
     *
     * Used Jira issue number as patch unique identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Patch type.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Short patch description.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Patch categories.
     *
     * @return array
     */
    public function getCategories(): array;

    /**
     * Patch origin.
     *
     * @return string
     */
    public function getOrigin(): string;

    /**
     * Patch filename.
     *
     * @return string
     */
    public function getFilename(): string;

    /**
     * Patch path.
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Name of the composer package associated with patch.
     *
     * @return string
     */
    public function getPackageName(): string;

    /**
     * Version constraint of the composer package associated with patch.
     *
     * @return string
     */
    public function getPackageConstraint(): string;

    /**
     * List of affected components.
     *
     * @return string[]
     */
    public function getAffectedComponents(): array;

    /**
     * Required patches.
     *
     * @return string[]
     */
    public function getRequire(): array;

    /**
     * Id of patch that current patch was replaced.
     *
     * @return string
     */
    public function getReplacedWith(): string;

    /**
     * Is patch deprecated.
     *
     * @return bool
     */
    public function isDeprecated(): bool;

    /**
     * Patch requirements.
     *
     * @return string
     */
    public function getRequirements(): string;
}
