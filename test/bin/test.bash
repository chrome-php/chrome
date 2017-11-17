#!/bin/bash

SCRIPTFILE=$(readlink -f "$0")
SCRIPTDIR=$(dirname "$SCRIPTFILE")


if [ -n "$1" ]; then filter=$1; else filter="."; fi
cd $SCRIPTDIR/../.. && $SCRIPTDIR/../../vendor/bin/phpunit -c phpunit.dist.xml --filter $filter
