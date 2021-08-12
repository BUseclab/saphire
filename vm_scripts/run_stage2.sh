#!/bin/bash
# Usage
if [ $# -lt 2 ]; then
	echo "Usage $0 PATH_TO_STAGE1_OUTPUT PATH_TO_OUTPUT"
    exit 1
fi

INPATH=$(realpath $1)
OUTPATH=$(realpath $2)

set -o xtrace
#####################################################################
# Setup tmp dir
rm -rf /tmp/stage2.tmp
mkdir /tmp/stage2.tmp

#####################################################################
# Build the profile of syscalls for the webapp 
cd ~/stage2/php-api-deps
make
./profile_application.sh /var/www/html/ /tmp/stage2.tmp/filter $INPATH

#####################################################################
# Copy the profile to the output
cp /tmp/stage2.tmp/filter $OUTPATH

