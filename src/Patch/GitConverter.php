<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudPatches\Patch;

/**
 * Converts patch from composer-based format to git-based.
 *
 * @see https://github.com/magento-sparta/m2-convert-patch-for-composer-install/blob/master/convert-for-composer.php
 */
class GitConverter
{
    const MODULE                = 'Module';
    const ADMINHTML_DESIGN      = 'AdminhtmlDesign';
    const FRONTEND_DESIGN       = 'FrontendDesign';
    const LIBRARY_AMPQ          = 'LibraryAmpq';
    const LIBRARY_BULK          = 'LibraryBulk';
    const LIBRARY_FOREIGN_KEY   = 'LibraryForeignKey';
    const LIBRARY_MESSAGE_QUEUE = 'LibraryMessageQueue';
    const LIBRARY               = 'Library';

    /**
     * @var string[]
     */
    private $nonComposerPath  = [
        self::MODULE                => 'app/code/Magento/',
        self::ADMINHTML_DESIGN      => 'app/design/adminhtml/Magento/',
        self::FRONTEND_DESIGN       => 'app/design/frontend/Magento/',
        self::LIBRARY_AMPQ          => 'lib/internal/Magento/Framework/Amqp/',
        self::LIBRARY_BULK          => 'lib/internal/Magento/Framework/Bulk/',
        self::LIBRARY_FOREIGN_KEY   => 'lib/internal/Magento/Framework/ForeignKey/',
        self::LIBRARY_MESSAGE_QUEUE => 'lib/internal/Magento/Framework/MessageQueue/',
        self::LIBRARY               => 'lib/internal/Magento/Framework/'
    ];

    /**
     * @var string[]
     */
    private $composerPath     = [
        self::MODULE                => 'vendor/magento/module-',
        self::ADMINHTML_DESIGN      => 'vendor/magento/theme-adminhtml-',
        self::FRONTEND_DESIGN       => 'vendor/magento/theme-frontend-',
        self::LIBRARY_AMPQ          => 'vendor/magento/framework-ampq/',
        self::LIBRARY_BULK          => 'vendor/magento/framework-bulk/',
        self::LIBRARY_FOREIGN_KEY   => 'vendor/magento/framework-foreign-key/',
        self::LIBRARY_MESSAGE_QUEUE => 'vendor/magento/framework-message-queue/',
        self::LIBRARY               => 'vendor/magento/framework/'
    ];

    /**
     * Converts patch content from composer-based to git-based.
     *
     * @param string $content
     * @return string
     */
    public function convert(string $content): string
    {
        foreach ($this->composerPath as $type => $path) {
            $escapedPath = addcslashes($path, '/');
            $needProcess = $type !== self::FRONTEND_DESIGN && $type !== self::ADMINHTML_DESIGN;

            /**
             * phpcs:disable
             * Example:
             * (     1     )               (        2        )(         3         )               (        4        )(       5        )
             * diff --git a/vendor/magento/module-some-module/Some/Path/File.ext b/vendor/magento/module-some-module/Some/Path/File.ext
             *
             * (     1     )                                     ()(     3     )                                     ()(    5   )
             * diff --git a/vendor/magento/framework-message-queue/Config.php b/vendor/magento/framework-message-queue/Config.php
             * phpcs:enable
             */
            $regex = '~(^diff -(?:.*?)\s+(?:a\/)?)' . $escapedPath . '([-\w]+\/)?([^\s]+\s+(?:b\/)?)' .
                $escapedPath . '([-\w]+\/)?([^\s]+)$~m';
            $content = preg_replace_callback(
                $regex,
                function ($matches) use ($type, $needProcess) {
                    return $matches[1] . $this->nonComposerPath[$type]
                        . ($needProcess ? $this->dashedStringToCamelCase($matches[2]) : $matches[2])
                        . $matches[3] . $this->nonComposerPath[$type]
                        . ($needProcess ? $this->dashedStringToCamelCase($matches[4]) : $matches[4])
                        . $matches[5];
                },
                $content
            );

            // (  1 )               (        2       )
            // +++ b/vendor/magento/module-some-module...
            $content = preg_replace_callback(
                '~(^(?:---|\+\+\+|Index:)\s+(?:a\/|b\/)?)' . $escapedPath . '([-\w]+)~m',
                function ($matches) use ($type, $needProcess) {
                    return $matches[1] . $this->nonComposerPath[$type]
                        . ($needProcess ? $this->dashedStringToCamelCase($matches[2]) : $matches[2]);
                },
                $content
            );

            // (     1     )              (        2       )
            // rename from vendor/magento/module-some-module...
            $content = preg_replace_callback(
                '~(^rename\s+(?:from|to)\s+)' . $escapedPath . '([-\w]+)~m',
                function ($matches) use ($type, $needProcess) {
                    return $matches[1] . $this->nonComposerPath[$type]
                        . ($needProcess ? $this->dashedStringToCamelCase($matches[2]) : $matches[2]);
                },
                $content
            );
        }

        return $content;
    }

    /**
     * Converts string to camel case.
     *
     * @param string $string
     * @return string
     */
    private function dashedStringToCamelCase(string $string): string
    {
        return str_replace('-', '', ucwords($string, '-'));
    }
}
