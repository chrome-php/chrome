#!/bin/bash

SCRIPTFILE=$(readlink -f "$0")
SCRIPTDIR=$(dirname "$SCRIPTFILE")

cd $SCRIPTDIR/../.. && $SCRIPTDIR/../../vendor/bin/phpcbf --standard="$SCRIPTDIR/../../phpcs.xml"

exit 0
