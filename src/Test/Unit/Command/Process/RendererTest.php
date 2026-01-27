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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
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
    private const AFFECTED_COMPONENTS = ['magento/framework', 'magento/module-elasticsearch'];

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
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
     * @param bool $isDeprecated
     * @param string $replacedWith
     * @param array $require
     * @param string $prependedMessage
     * @param array $expectedArray
     * @dataProvider printPatchInfoDataProvider
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    #[DataProvider('printPatchInfoDataProvider')]
    public function testPrintPatchInfo(
        bool $isDeprecated,
        string $replacedWith,
        array $require,
        string $prependedMessage,
        array $expectedArray
    ): void {
        $patch = $this->createPatch($isDeprecated, $replacedWith, $require);

        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->expects($this->atLeastOnce())
            ->method('writeln')
            ->willReturnCallback(function ($filter) use ($expectedArray) {
                if ($filter === $expectedArray) {
                    return $expectedArray;
                }
                return [];
            });

        $this->renderer->printPatchInfo($outputMock, $patch, $prependedMessage);
    }

    /**
     * Tests patch info for deprecation, replacement, requirements, and prepended messages.
     *
     * @return array[]
     */
    public static function printPatchInfoDataProvider(): array
    {
        return [
            [
                'isDeprecated' => false,
                'replacedWith' => '',
                'require' => [],
                'prependedMessage' => '',
                'expectedArray' => [
                    '<comment>Title:</comment> ' . self::PATCH_TITLE,
                    '<comment>File:</comment> ' . self::PATCH_FILENAME,
                    '<comment>Affected components:</comment> ' . implode(' ', self::AFFECTED_COMPONENTS)
                ]
            ],
            [
                'isDeprecated' => true,
                'replacedWith' => '',
                'require' => [],
                'prependedMessage' => 'Prepended message',
                'expectedArray' => [
                    '<info>Prepended message</info>',
                    '<comment>Title:</comment> ' . self::PATCH_TITLE,
                    '<comment>File:</comment> ' . self::PATCH_FILENAME,
                    '<comment>Affected components:</comment> ' . implode(' ', self::AFFECTED_COMPONENTS),
                    '<error>Patch is deprecated!</error>'
                ]
            ],
            [
                'isDeprecated' => true,
                'replacedWith' => 'MC-22222',
                'require' => ['MC-33333', 'MC-44444'],
                'prependedMessage' => 'Prepended message',
                'expectedArray' => [
                    '<info>Prepended message</info>',
                    '<comment>Title:</comment> ' . self::PATCH_TITLE,
                    '<comment>File:</comment> ' . self::PATCH_FILENAME,
                    '<comment>Affected components:</comment> ' . implode(' ', self::AFFECTED_COMPONENTS),
                    '<comment>Require:</comment> MC-33333 MC-44444',
                    '<error>Patch is deprecated!</error> Please, replace it with MC-22222'
                ]
            ]
        ];
    }

    /**
     * Creates patch mock.
     *
     * @param bool $isDeprecated
     * @param string $replacedWith
     * @param array $require
     * @return PatchInterface|MockObject
     */
    private function createPatch(bool $isDeprecated, string $replacedWith = '', array $require = []): PatchInterface
    {
        $patch = $this->createMock(PatchInterface::class);

        $patch->method('getId')->willReturn(self::PATCH_ID);
        $patch->method('getTitle')->willReturn(self::PATCH_TITLE);
        $patch->method('getFilename')->willReturn(self::PATCH_FILENAME);
        $patch->method('getAffectedComponents')->willReturn(self::AFFECTED_COMPONENTS);
        $patch->method('isDeprecated')->willReturn($isDeprecated);
        $patch->method('getReplacedWith')->willReturn($replacedWith);
        $patch->method('getRequire')->willReturn($require);

        return $patch;
    }
}
