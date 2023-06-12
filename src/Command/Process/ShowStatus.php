<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Command\Process;

use Magento\CloudPatches\Command\Process\Action\ReviewAppliedAction;
use Magento\CloudPatches\Composer\MagentoVersion;
use Magento\CloudPatches\Console\QuestionFactory;
use Magento\CloudPatches\Patch\Data\AggregatedPatch;
use Magento\CloudPatches\Patch\Data\AggregatedPatchInterface;
use Magento\CloudPatches\Patch\Pool\LocalPool;
use Magento\CloudPatches\Patch\Pool\OptionalPool;
use Magento\CloudPatches\Patch\Aggregator;
use Magento\CloudPatches\Patch\Status\StatusPool;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show information about available patches and their statuses.
 */
class ShowStatus implements ProcessInterface
{
    const INTERACTIVE_FILTER_THRESHOLD = 50;

    const FILTER_OPTION_ALL = 'All';

    const FORMAT_JSON = 'json';

    /**
     * @var Aggregator
     */
    private $aggregator;

    /**
     * @var OptionalPool
     */
    private $optionalPool;

    /**
     * @var LocalPool
     */
    private $localPool;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var StatusPool
     */
    private $statusPool;

    /**
     * @var ReviewAppliedAction
     */
    private $reviewAppliedAction;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    /**
     * @var QuestionFactory
     */
    private $questionFactory;

    /**
     * @param Aggregator $aggregator
     * @param OptionalPool $optionalPool
     * @param LocalPool $localPool
     * @param StatusPool $statusPool
     * @param ReviewAppliedAction $reviewAppliedAction
     * @param Renderer $renderer
     * @param QuestionHelper $questionHelper
     * @param QuestionFactory $questionFactory
     */
    public function __construct(
        Aggregator $aggregator,
        OptionalPool $optionalPool,
        LocalPool $localPool,
        StatusPool $statusPool,
        ReviewAppliedAction $reviewAppliedAction,
        Renderer $renderer,
        QuestionHelper $questionHelper,
        QuestionFactory $questionFactory,
        MagentoVersion $magentoVersion
    ) {
        $this->aggregator = $aggregator;
        $this->optionalPool = $optionalPool;
        $this->localPool = $localPool;
        $this->statusPool = $statusPool;
        $this->reviewAppliedAction = $reviewAppliedAction;
        $this->renderer = $renderer;
        $this->questionHelper = $questionHelper;
        $this->questionFactory = $questionFactory;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $isJsonFormat = $input->getOption('format') === self::FORMAT_JSON;
        $patches = $this->aggregator->aggregate(
            array_merge($this->optionalPool->getList(), $this->localPool->getList())
        );

        if (!$isJsonFormat) {
            $this->printDetailsInfo($output);
            $this->reviewAppliedAction->execute($input, $output, []);
            foreach ($patches as $patch) {
                if ($patch->isDeprecated() && $this->isPatchVisible($patch)) {
                    $this->printDeprecatedWarning($output, $patch);
                }
            }
        }

        $patches = $this->filterNotVisiblePatches($patches);

        if (!$isJsonFormat && count($patches) > self::INTERACTIVE_FILTER_THRESHOLD) {
            $this->printPatchProviders($output, $patches);
            $patches = $this->filterByPatchProvider($input, $output, $patches);
            $this->printCategoriesInfo($output, $patches);
            $patches = $this->filterByPatchCategory($input, $output, $patches);
        }

        if ($isJsonFormat) {
            $this->renderer->printJson($output, array_values($patches));
        } else {
            $this->renderer->printTable($output, array_values($patches));
            $output->writeln('<info>' . $this->magentoVersion->get() . '</info>');
        }
    }

    /**
     * @param array $patches
     * @return array
     */
    private function filterNotVisiblePatches(array $patches): array
    {
        return array_filter(
            $patches,
            function ($patch) {
                return !$patch->isDeprecated() || $this->isPatchVisible($patch);
            }
        );
    }

    /**
     * @param OutputInterface $output
     * @param array $patches
     */
    private function printPatchProviders(OutputInterface $output, array $patches)
    {
        $patchProviders = [self::FILTER_OPTION_ALL=> count($patches)];
        /** @var  AggregatedPatch $patch */
        foreach ($patches as $patch) {
            if (!isset($patchProviders[$patch->getOrigin()])) {
                $patchProviders[$patch->getOrigin()] = 0;
            }
            $patchProviders[$patch->getOrigin()]++;
        }

        $providersInfo = PHP_EOL . '<info>Patch providers:</info>' . PHP_EOL;
        $i = 1;
        foreach ($patchProviders as $type => $count) {
            $providersInfo .= sprintf('<info>%d) %s (%s)</info>', $i, $type, $count) . PHP_EOL;
            $i++;
        }

        $output->writeln($providersInfo);
    }

    /**
     * @param array $patches
     * @return array
     */
    private function getPatchProviders(array $patches): array
    {
        $patchTypes = [self::FILTER_OPTION_ALL];
        /** @var  AggregatedPatch $patch */
        foreach ($patches as $patch) {
            if (!in_array($patch->getOrigin(), $patchTypes)) {
                $patchTypes[] = $patch->getOrigin();
            }
        }
        return $patchTypes;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $patches
     * @return array
     */
    private function filterByPatchProvider(InputInterface $input, OutputInterface $output, array $patches): array
    {
        $typeQuestion = $this->questionFactory->create('Please, select patch provider: ', self::FILTER_OPTION_ALL);
        $selectedType = $this->questionHelper->ask($input, $output, $typeQuestion);

        if (is_numeric($selectedType)) {
            $patchTypes = $this->getPatchProviders($patches);
            $selectedType = $patchTypes[(int)$selectedType - 1] ?? self::FILTER_OPTION_ALL;
        }

        $output->writeln('<info>Selected patch provider: ' . $selectedType . '</info>' . PHP_EOL);
        return $selectedType === self::FILTER_OPTION_ALL
            ? $patches
            : array_filter(
                $patches,
                function ($patch) use ($selectedType) {
                    return strtolower($patch->getOrigin()) === strtolower($selectedType);
                }
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $patches
     * @return array
     */
    private function filterByPatchCategory(InputInterface $input, OutputInterface $output, array $patches): array
    {
        $categoryQuestion = $this->questionFactory->create('Please, select patch category: ', self::FILTER_OPTION_ALL);
        $selectedCategory = $this->questionHelper->ask($input, $output, $categoryQuestion);

        if (is_numeric($selectedCategory)) {
            $allPatchCategories = $this->getPatchCategories($patches);
            $selectedCategory = $allPatchCategories[(int)$selectedCategory - 1] ?? $allPatchCategories[0];
        }

        $output->writeln('<info>Selected patch category: ' . $selectedCategory . '</info>' . PHP_EOL);
        return $selectedCategory === self::FILTER_OPTION_ALL
            ? $patches
            : array_filter(
                $patches,
                function ($patch) use ($selectedCategory) {
                    $patchCategories = $patch->getCategories();
                    $patchCategories = array_map('strtolower', $patchCategories);
                    $selectedCategory = strtolower($selectedCategory);
                    return in_array($selectedCategory, $patchCategories);
                }
            );
    }

    /**
     * @param array $patches
     * @return string[]
     */
    private function getPatchCategories(array $patches): array
    {
        $categories = [self::FILTER_OPTION_ALL];

        /** @var  AggregatedPatch $patch */
        foreach ($patches as $patch) {
            foreach ($patch->getCategories() as $patchCategory) {
                if (!in_array($patchCategory, $categories)) {
                    $categories[] = $patchCategory;
                }
            }
        }
        return $categories;
    }

    /**
     * Prints information where to find more details about patches.
     *
     * @param OutputInterface $output
     * @return void
     */
    private function printDetailsInfo(OutputInterface $output)
    {
        // phpcs:ignore
        $releaseNotesUrl = 'https://experienceleague.adobe.com/docs/commerce-operations/tools/quality-patches-tool/release-notes.html';
        $supportUrl = 'https://experienceleague.adobe.com/tools/commerce-quality-patches/index.html';

        $output->writeln(
            '<info>Patch details you can find on </info>' .
            sprintf('<href=%1$s>%1$s</> <info>(search for patch id, ex. MDVA-30265)</info>', $supportUrl) .
            PHP_EOL .
            sprintf('<info>Release notes</info> <href=%1$s>%1$s</>', $releaseNotesUrl)
        );
    }

    /**
     * Prints patches category information
     *
     * @param OutputInterface $output
     * @param array $patches
     * @return void
     */
    private function printCategoriesInfo(OutputInterface $output, array $patches)
    {
        $categories = [self::FILTER_OPTION_ALL => count($patches)];

        /** @var  AggregatedPatch $patch */
        foreach ($patches as $patch) {
            foreach ($patch->getCategories() as $patchCategory) {
                if (!isset($categories[$patchCategory])) {
                    $categories[$patchCategory] = 0;
                }
                $categories[$patchCategory]++;
            }
        }

        $categoriesInfo = PHP_EOL . '<info>Patch categories:</info>' . PHP_EOL;
        $i = 1;
        foreach ($categories as $category => $count) {
            $categoriesInfo .= sprintf('<info>%d) %s (%s)</info>', $i, $category, $count) . PHP_EOL;
            $i++;
        }

        $output->writeln($categoriesInfo);
    }

    /**
     * Prints warning message about applied deprecated patch.
     *
     * @param OutputInterface $output
     * @param AggregatedPatchInterface $patch
     * @return void
     */
    private function printDeprecatedWarning(OutputInterface $output, AggregatedPatchInterface $patch)
    {
        $message = sprintf(
            '<error>Deprecated patch %s is currently applied. Please, consider to revert it%s</error>',
            $patch->getId(),
            $patch->getReplacedWith() ? ' and replace with ' . $patch->getReplacedWith() : '.'
        );
        $output->writeln($message);
    }

    /**
     * Defines if the patch should be visible in the status table.
     *
     * @param AggregatedPatchInterface $patch
     * @return bool
     */
    private function isPatchVisible(AggregatedPatchInterface $patch): bool
    {
        return $patch->getReplacedWith() ?
            $this->statusPool->isApplied($patch->getId()) && !$this->statusPool->isApplied($patch->getReplacedWith()) :
            $this->statusPool->isApplied($patch->getId());
    }
}
