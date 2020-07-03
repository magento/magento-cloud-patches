<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Console;

use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Factory method for ConfirmationQuestion.
 */
class ConfirmationQuestionFactory
{
    /**
     * Creates confirmation question.
     *
     * @param string $question The question to ask to the user
     * @param bool $default The default answer to return, true or false
     *
     * @return ConfirmationQuestion
     */
    public function create(string $question, bool $default = true)
    {
        return new ConfirmationQuestion($question, $default);
    }
}
