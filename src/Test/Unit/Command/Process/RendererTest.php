<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Command\Process;

use Magento\CloudPatches\Command\Process\Renderer;
use Magento\CloudPatches\Console\ConfirmationQuestionFactory;
use Magento\CloudPatches\Console\TableFactory;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Status\StatusPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class RendererTest extends TestCase
{
    const PATCH_ID = 'MC-11111';

    const PATCH_TITLE = 'Patch title';

    const PATCH_FILENAME = 'MC-11111__patch_title__2.2.5.patch';

    /**
     * Test components.
     *
     * @var string[]
     */
    private $affectedComponents = ['magento/framework', 'magento/module-elasticsearch'];

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        /** @var StatusPool|MockObject $statusPool */
        $statusPool = $this->createMock(StatusPool::class);
        /** @var  TableFactory|MockObject $tableFactory */
        $tableFactory = $this->createMock(TableFactory::class);
        /** @var  QuestionHelper|MockObject $questionHelper */
        $questionHelper = $this->createMock(QuestionHelper::class);
        /** @var  ConfirmationQuestionFactory|MockObject $confirmationQuestionFactory */
        $confirmationQuestionFactory = $this->createMock(ConfirmationQuestionFactory::class);

        $this->renderer = new Renderer(
            $tableFactory,
            $statusPool,
            $questionHelper,
            $confirmationQuestionFactory
        );
    }

    /**
     * Tests patch info output.
     *
     * @param PatchInterface $patch
     * @param string $prependedMessage
     * @param array $expectedArray
     * @dataProvider printPatchInfoDataProvider
     */
    public function testPrintPatchInfo(PatchInterface $patch, string $prependedMessage, array $expectedArray)
    {
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $outputMock->expects($this->atLeastOnce())
            ->method('writeln')
            ->withConsecutive([$expectedArray]);

        $this->renderer->printPatchInfo($outputMock, $patch, $prependedMessage);
    }

    /**
     * @return array[]
     */
    public function printPatchInfoDataProvider(): array
    {
        return [
            [
                'patch' => $this->createPatch(false),
                'prependedMessage' => '',
                'expectedArray' => [
                    '<comment>Title:</comment> ' . self::PATCH_TITLE,
                    '<comment>File:</comment> ' . self::PATCH_FILENAME,
                    '<comment>Affected components:</comment> ' . implode(' ', $this->affectedComponents)
                ]
            ],
            [
                'patch' => $this->createPatch(true),
                'prependedMessage' => 'Prepended message',
                'expectedArray' => [
                    '<info>Prepended message</info>',
                    '<comment>Title:</comment> ' . self::PATCH_TITLE,
                    '<comment>File:</comment> ' . self::PATCH_FILENAME,
                    '<comment>Affected components:</comment> ' . implode(' ', $this->affectedComponents),
                    '<error>Patch is deprecated!</error>'
                ]
            ],
            [
                'patch' => $this->createPatch(true, 'MC-22222', ['MC-33333', 'MC-44444']),
                'prependedMessage' => 'Prepended message',
                'expectedArray' => [
                    '<info>Prepended message</info>',
                    '<comment>Title:</comment> ' . self::PATCH_TITLE,
                    '<comment>File:</comment> ' . self::PATCH_FILENAME,
                    '<comment>Affected components:</comment> ' . implode(' ', $this->affectedComponents),
                    '<comment>Require:</comment> MC-33333 MC-44444',
                    '<error>Patch is deprecated!</error> Please, replace it with MC-22222'
                ]
            ]
        ];
    }

    /**
     * Tests error output formatting.
     *
     * @param string $errorOutput
     * @param string $expectedOutput
     * @dataProvider formatErrorOutputDataProvider
     */
    public function testFormatErrorOutput(string $errorOutput, string $expectedOutput)
    {
        $this->assertEquals($expectedOutput, $this->renderer->formatErrorOutput($errorOutput));
    }

    /**
     * @return array
     */
    public function formatErrorOutputDataProvider(): array
    {
        return [
            [
                'error' => 'The command "\'git\' \'apply\' \'/path/to/patch/MC-1111_test_patch_1.1.1_ce.patch\'" failed.

Exit Code: 1(General error)

Working directory: /path/to/patch

Output:
================


Error Output:
================
error: patch failed: vendor/magento/module-admin-analytics/Controller/Adminhtml/Config/DisableAdminUsage.php:23
error: vendor/magento/module-admin-analytics/Controller/Adminhtml/Config/DisableAdminUsage.php: patch does not apply',

                'expectedOutput' => '
Error Output:
================
error: patch failed: vendor/magento/module-admin-analytics/Controller/Adminhtml/Config/DisableAdminUsage.php:23
error: vendor/magento/module-admin-analytics/Controller/Adminhtml/Config/DisableAdminUsage.php: patch does not apply'
            ],
            [
                'error' => 'Some other output',
                'expectedOutput' => 'Some other output'
            ],
        ];
    }

    /**
     * Creates patch mock.
     *
     * @param bool $isDeprecated
     * @param string $replacedWith
     * @param array $require
     *
     * @return PatchInterface|MockObject
     */
    private function createPatch(bool $isDeprecated, string $replacedWith = '', array $require = [])
    {
        $patch = $this->getMockForAbstractClass(PatchInterface::class);

        $patch->method('getId')->willReturn(self::PATCH_ID);
        $patch->method('getTitle')->willReturn(self::PATCH_TITLE);
        $patch->method('getFilename')->willReturn(self::PATCH_FILENAME);
        $patch->method('getAffectedComponents')->willReturn($this->affectedComponents);
        $patch->method('isDeprecated')->willReturn($isDeprecated);
        $patch->method('getReplacedWith')->willReturn($replacedWith);
        $patch->method('getRequire')->willReturn($require);

        return $patch;
    }
}
