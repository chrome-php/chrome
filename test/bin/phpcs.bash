#!/bin/bash

SCRIPTFILE=$(readlink -f "$0")
SCRIPTDIR=$(dirname "$SCRIPTFILE")


if [ -n "$1" ]; then report=$1; else report="summary"; fi
cd $SCRIPTDIR/../.. && $SCRIPTDIR/../../vendor/bin/phpcs --standard="$SCRIPTDIR/../../phpcs.xml" --report=$report -s

