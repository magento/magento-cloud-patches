<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Console;

use Symfony\Component\Console\Question\Question;

/**
 * Factory method for ConfirmationQuestion.
 */
class QuestionFactory
{
    /**
     * Creates confirmation question.
     *
     * @param string $question The question to ask to the user
     * @param string $default The default answer to return
     *
     * @return Question
     */
    public function create(string $question, string $default = null): Question
    {
        return new Question($question, $default);
    }
}
