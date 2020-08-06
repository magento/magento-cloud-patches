#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

case $TRAVIS_PHP_VERSION in
    7.1)
        ./vendor/bin/codecept run -g php71cePart$PART_INDEX --steps
        ;;
    7.2)
        ./vendor/bin/codecept run -g php72ce --steps
        ;;
    7.3)
        ./vendor/bin/codecept run -g php73ce --steps
        ;;
    7.4)
        ./vendor/bin/codecept run -g php74ce --steps
        ;;
esac
