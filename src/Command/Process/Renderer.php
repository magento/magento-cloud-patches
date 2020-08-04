<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process;

use Magento\CloudPatches\Console\ConfirmationQuestionFactory;
use Magento\CloudPatches\Console\TableFactory;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Data\PatchInterface;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Manages patches status.
 */
class Renderer
{
    const ID = 'Id';

    const TITLE = 'Title';

    const TYPE = 'Type';

    const STATUS = 'Status';

    const DETAILS = 'Details';

    /**
     * @var TableFactory
     */
    private $tableFactory;

    /**
     * @var StatusPool
     */
    private $statusPool;

    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    /**
     * @var ConfirmationQuestionFactory
     */
    private $confirmationQuestionFactory;

    /**
     * @param TableFactory $tableFactory
     * @param StatusPool $statusPool
     * @param QuestionHelper $questionHelper
     * @param ConfirmationQuestionFactory $confirmationQuestionFactory
     */
    public function __construct(
        TableFactory $tableFactory,
        StatusPool $statusPool,
        QuestionHelper $questionHelper,
        ConfirmationQuestionFactory $confirmationQuestionFactory
    ) {
        $this->tableFactory = $tableFactory;
        $this->statusPool = $statusPool;
        $this->questionHelper = $questionHelper;
        $this->confirmationQuestionFactory = $confirmationQuestionFactory;
    }

    /**
     * Renders patches list as a table.
     *
     * @param OutputInterface $output
     * @param AggregatedPatchInterface[] $patchList
     * @return void
     */
    public function printTable(OutputInterface $output, array $patchList)
    {
        $table = $this->tableFactory->create($output);
        $table->setHeaders([self::ID, self::TITLE, self::TYPE, self::STATUS, self::DETAILS]);
        $table->setStyle('box-double');

        $rows = [];
        foreach ($patchList as $patch) {
            $rows[] = $this->createRow($patch);
        }

        usort($rows, function ($a, $b) {
            return strcmp($a[self::STATUS], $b[self::STATUS]);
        });

        $rows = $this->addTableSeparator($rows);
        $table->addRows($rows);
        $table->render();
    }

    /**
     * Print patch info.
     *
     * @param OutputInterface $output
     * @param PatchInterface $patch
     * @param string $prependedMessage
     * @return void
     */
    public function printPatchInfo(
        OutputInterface $output,
        PatchInterface $patch,
        string $prependedMessage = ''
    ) {
        $info = [
            sprintf('<comment>Title:</comment> %s', $patch->getTitle()),
            sprintf('<comment>File:</comment> %s', $patch->getFilename()),
            sprintf(
                '<comment>Affected components:</comment> %s',
                implode(' ', $patch->getAffectedComponents())
            ),
        ];

        if ($patch->getRequire()) {
            $info[] = sprintf(
                '<comment>Require:</comment> %s',
                implode(' ', $patch->getRequire())
            );
        }

        if ($patch->isDeprecated()) {
            $info[] = sprintf(
                '<error>Patch is deprecated!</error>%s',
                $patch->getReplacedWith() ? ' Please, replace it with ' . $patch->getReplacedWith() : ''
            );
        }

        if ($prependedMessage) {
            array_unshift($info, '<info>' . $prependedMessage . '</info>');
        }
        $output->writeln($info);
        $output->writeln('');
    }

    /**
     * Format error output.
     *
     * @param string $errorOutput
     * @return string
     */
    public function formatErrorOutput(string $errorOutput): string
    {
        if (preg_match('#^.*?Error Output:(?<errors>.*?)$#is', $errorOutput, $matches)) {
            $errorOutput = PHP_EOL . 'Error Output:' . $matches['errors'];
        }

        return $errorOutput;
    }

    /**
     * Asks a confirmation question to the user.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $question
     * @return bool
     */
    public function printQuestion(InputInterface $input, OutputInterface $output, string $question): bool
    {
        $question = $this->confirmationQuestionFactory->create(
            '<question>' . $question . ' [y/N]</question> '
        );

        return (bool)$this->questionHelper->ask($input, $output, $question);
    }

    /**
     * Creates table row.
     *
     * @param AggregatedPatchInterface $patch
     * @return array
     */
    private function createRow(AggregatedPatchInterface $patch): array
    {
        $details = '';
        if ($patch->getReplacedWith()) {
            $details .= '<info>Recommended replacement: ' . $patch->getReplacedWith() . '</info>' . PHP_EOL;
        }

        if ($patch->getRequire()) {
            $wrappedRequire = array_map(
                function ($item) {
                    return sprintf('<comment> - %s</comment>', $item);
                },
                $patch->getRequire()
            );
            $details .= 'Required patches:' . PHP_EOL . implode(PHP_EOL, $wrappedRequire) . PHP_EOL;
        }

        if ($patch->getAffectedComponents()) {
            $glue = PHP_EOL . ' - ';
            $details .= 'Affected components:' . $glue . implode($glue, $patch->getAffectedComponents());
        }

        $id = $patch->getType() === PatchInterface::TYPE_CUSTOM ? 'N/A' : $patch->getId();
        $title = chunk_split($patch->getTitle(), 60, PHP_EOL);

        return [
            self::ID => '<comment>' . $id . '</comment>',
            self::TITLE => $title,
            self::TYPE => $patch->isDeprecated() ? '<error>DEPRECATED</error>' : $patch->getType(),
            self::STATUS => $this->statusPool->get($patch->getId()),
            self::DETAILS => $details
        ];
    }

    /**
     * Adds table separator.
     *
     * @param array $rowItems
     * @return array
     */
    private function addTableSeparator(array $rowItems): array
    {
        $result = [];
        foreach ($rowItems as $row) {
            $result[] = $row;
            $result[] = new TableSeparator();
        }
        array_pop($result);

        return $result;
    }
}
