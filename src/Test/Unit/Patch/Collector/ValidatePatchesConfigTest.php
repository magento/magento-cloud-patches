<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Patch\Collector;

use Magento\CloudPatches\Patch\Collector\CollectorException;
use Magento\CloudPatches\Patch\Collector\ValidatePatchesConfig;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ValidatePatchesConfigTest extends TestCase
{
    /** @var ValidatePatchesConfig */
    private ValidatePatchesConfig $validator;

    /**
     * Sets up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->validator = new ValidatePatchesConfig();
    }

    /**
     * Tests executing validation with valid config.
     *
     * @return void
     * @throws CollectorException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteValidConfig(): void
    {
        $config = [
            'patch-1' => [
                'packages' => [
                    'package/name' => [
                        '1.0.0' => [
                            'file' => 'patch.diff',
                            'require' => [],
                            'replaced-with' => '',
                            'deprecated' => false,
                            'categories' => []
                        ]
                    ]
                ]
            ]
        ];

        $this->validator->execute($config);
        $this->expectNotToPerformAssertions();
    }

    /**
     * Tests validation fails when file property is missing.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteMissingFileProperty(): void
    {
        $config = [
            'patch-1' => [
                'packages' => [
                    'package/name' => [
                        '1.0.0' => []
                    ]
                ]
            ]
        ];

        $this->expectException(CollectorException::class);
        $this->expectExceptionMessage(
            "Patch patch-1 has invalid configuration:\n - Property 'file' is not found in '1.0.0'"
        );

        $this->validator->execute($config);
    }

    /**
     * Tests validation fails when require property has invalid type.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteInvalidRequireType(): void
    {
        $config = [
            'patch-1' => [
                'packages' => [
                    'package/name' => [
                        '1.0.0' => [
                            'file' => 'patch.diff',
                            'require' => 'invalid'
                        ]
                    ]
                ]
            ]
        ];

        $this->expectException(CollectorException::class);
        $this->expectExceptionMessage("Property 'require' from '1.0.0' should have an array type");

        $this->validator->execute($config);
    }

    /**
     * Tests validation fails when replaced-with property is not a string.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteInvalidReplacedWithType(): void
    {
        $config = [
            'patch-1' => [
                'packages' => [
                    'package/name' => [
                        '1.0.0' => [
                            'file' => 'patch.diff',
                            'replaced-with' => ['PATCH-2'] // Should be string, not array
                        ]
                    ]
                ]
            ]
        ];

        $this->expectException(CollectorException::class);
        $this->expectExceptionMessage("Property 'replaced-with' from '1.0.0' should have a string type");

        $this->validator->execute($config);
    }

    /**
     * Tests validation fails when deprecated property is not a boolean.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteInvalidDeprecatedType(): void
    {
        $config = [
            'patch-1' => [
                'packages' => [
                    'package/name' => [
                        '1.0.0' => [
                            'file' => 'patch.diff',
                            'deprecated' => 1 // Should be boolean, not int
                        ]
                    ]
                ]
            ]
        ];

        $this->expectException(CollectorException::class);
        $this->expectExceptionMessage("Property 'deprecated' from '1.0.0' should have a boolean type");

        $this->validator->execute($config);
    }

    /**
     * Tests validation fails when categories property is not an array.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteInvalidCategoriesType(): void
    {
        $config = [
            'patch-1' => [
                'packages' => [
                    'package/name' => [
                        '1.0.0' => [
                            'file' => 'patch.diff',
                            'categories' => 'Performance' // Should be array, not string
                        ]
                    ]
                ]
            ]
        ];

        $this->expectException(CollectorException::class);
        $this->expectExceptionMessage("Property 'categories' from '1.0.0' should have an array type");

        $this->validator->execute($config);
    }

    /**
     * Tests validation passes with all valid optional properties.
     *
     * @return void
     * @throws CollectorException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteValidConfigWithAllOptionalProperties(): void
    {
        $config = [
            'patch-1' => [
                'packages' => [
                    'package/name' => [
                        '1.0.0' => [
                            'file' => 'patch.diff',
                            'require' => ['patch-2', 'patch-3'],
                            'replaced-with' => 'patch-4',
                            'deprecated' => true,
                            'categories' => ['Performance', 'Security']
                        ]
                    ]
                ]
            ]
        ];

        $this->validator->execute($config);
        $this->expectNotToPerformAssertions();
    }

    /**
     * Tests validation accumulates multiple errors for the same patch.
     *
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteMultipleValidationErrors(): void
    {
        $config = [
            'patch-1' => [
                'packages' => [
                    'package/name' => [
                        '1.0.0' => [
                            // Missing 'file' property
                            'require' => 'invalid', // Should be array
                            'deprecated' => 'yes' // Should be boolean
                        ]
                    ]
                ]
            ]
        ];

        $this->expectException(CollectorException::class);

        try {
            $this->validator->execute($config);
        } catch (CollectorException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString("Property 'file' is not found", $message);
            $this->assertStringContainsString("Property 'require'", $message);
            $this->assertStringContainsString("Property 'deprecated'", $message);
            throw $e;
        }
    }
}
