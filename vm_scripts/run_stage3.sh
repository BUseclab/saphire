#!/bin/bash
# Usage
if [ $# -lt 1 ]; then
	echo "Usage $0 PATH_TO_STAGE2_OUTPUT"
	echo "Optionally, set DISABLE_PROTECTION=1 to test out exploit"
    exit 1
fi

INPATH=$(realpath $1)
INI_EXTRA=""
if [ $DISABLE_PROTECTION == "1" ]
then
    INI_EXTRA="-d seccomp.enable=0"
else
    INI_EXTRA="-d seccomp.enable=1"
fi


set -o xtrace
#####################################################################
# Copy the filter to /var/www
sudo cp $INPATH /var/www/filter

#####################################################################
# Build the seccomp extension
cd ~/stage3/extension/
/opt/php-7.1/bin/phpize
./configure --with-php-config=/opt/php-7.1/bin/php-config
make
sudo make install

#####################################################################
# Run the webapp
sudo pkill -9 php-fpm;
sudo /opt/php-7.1/sbin/php-fpm -d "extension=seccomp.so" $INI_EXTRA
