#!/bin/bash

set -e

SCRIPTFILE=$(readlink -f "$0")
SCRIPTDIR=$(dirname "$SCRIPTFILE")


echo -e "\e[34m"
echo "======================"
echo -e "= \e[1m\e[33mRunning unit tests\e[0m\e[34m ="
echo -e "======================\e[39m"

php $SCRIPTDIR/../../vendor/bin/phpunit -c "$SCRIPTDIR/../../phpunit.dist.xml"


echo -e "\e[34m"
echo "================================="
echo -e "= \e[1m\e[33mChecking code style standards\e[0m\e[34m ="
echo -e "=================================\e[39m"

$SCRIPTDIR/phpcs.bash $1

echo "Code standards: OK"


echo -e "\e[34m"
echo "============================"
echo -e "= \e[1m\e[33mResporting code coverage\e[0m\e[34m ="
echo -e "============================\e[39m"
if [ "$PROCESS_CODECLIMATE" = true ] && [ "${TRAVIS_PULL_REQUEST}" = "false" ] && [ "${TRAVIS_BRANCH}" = "master" ]
then
    composer require codeclimate/php-test-reporter:dev-master
    ./vendor/bin/test-reporter
else
    echo "Skip code coverage report..."
fi

