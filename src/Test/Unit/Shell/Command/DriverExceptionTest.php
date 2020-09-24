<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Test\Unit\Shell\Command;

use Magento\CloudPatches\Shell\Command\DriverException;
use PHPUnit\Framework\TestCase;

class DriverExceptionTest extends TestCase
{
    /**
     * Tests exception message formatting.
     *
     * @param string $errorOutput
     * @param string $expectedOutput
     * @dataProvider formatMessageDataProvider
     */
    public function testFormatMessage(string $errorOutput, string $expectedOutput)
    {
        $exception = new DriverException($errorOutput);
        $this->assertEquals($expectedOutput, $exception->getMessage());
    }

    /**
     * @return array
     */
    public function formatMessageDataProvider(): array
    {
        return [
            [
                'error' => 'The command "\'patch\' \'--silent\' \'-p1\' \'--dry-run\'" failed.

Exit Code: 1(General error)

Working directory: /var/www/html

Output:
================


Error Output:
================
error: patch failed: path/to/path/file2.php b/path/to/path/file2.php:23
error: path/to/path/file2.php b/path/to/path/file2.php: patch does not apply',

                'expectedOutput' => '
Error Output:
================
error: patch failed: path/to/path/file2.php b/path/to/path/file2.php:23
error: path/to/path/file2.php b/path/to/path/file2.php: patch does not apply'
            ],
            [
                'error' => 'The command "\'patch\' \'--silent\' \'-p1\' \'--dry-run\'" failed.

Exit Code: 1(General error)

Working directory: /var/www/html

Output:
================
Hmm...  Looks like a unified diff to me...
The text leading up to this was:
--------------------------
|diff --git a/path/to/path/file1.php b/path/to/path/file1.php
|index 320e0adc29b..576281861d3 100644
|--- a/path/to/path/file1.php
|+++ b/path/to/path/file1.php
--------------------------
Patching file path/to/path/file1.php using Plan A...
Hunk #1 succeeded at 30.
Hunk #2 succeeded at 54.
Hunk #3 succeeded at 76.
Hunk #4 succeeded at 113.
Hmm...  The next patch looks like a unified diff to me...
The text leading up to this was:
--------------------------
|diff --git a/path/to/path/file2.php b/path/to/path/file2.php
|index 0ec65c88024..e550de9cb03 100644
|--- a/path/to/path/file2.php
|+++ b/path/to/path/file2.php
--------------------------
Patching file path/to/path/file2.php using Plan A...
Hunk #1 succeeded at 71.
Hunk #2 FAILED at 136.
Hunk #3 succeeded at 154.
1 out of 3 hunks FAILED -- saving rejects to file path/to/path/file2.php.rej
done


Error Output:
================

',

                'expectedOutput' => '
Output:
================
Hmm...  Looks like a unified diff to me...
The text leading up to this was:
--------------------------
|diff --git a/path/to/path/file1.php b/path/to/path/file1.php
|index 320e0adc29b..576281861d3 100644
|--- a/path/to/path/file1.php
|+++ b/path/to/path/file1.php
--------------------------
Patching file path/to/path/file1.php using Plan A...
Hunk #1 succeeded at 30.
Hunk #2 succeeded at 54.
Hunk #3 succeeded at 76.
Hunk #4 succeeded at 113.
Hmm...  The next patch looks like a unified diff to me...
The text leading up to this was:
--------------------------
|diff --git a/path/to/path/file2.php b/path/to/path/file2.php
|index 0ec65c88024..e550de9cb03 100644
|--- a/path/to/path/file2.php
|+++ b/path/to/path/file2.php
--------------------------
Patching file path/to/path/file2.php using Plan A...
Hunk #1 succeeded at 71.
Hunk #2 FAILED at 136.
Hunk #3 succeeded at 154.
1 out of 3 hunks FAILED -- saving rejects to file path/to/path/file2.php.rej
done

'
            ],
            [
                'error' => 'Some other output',
                'expectedOutput' => 'Some other output'
            ],
        ];
    }
}
