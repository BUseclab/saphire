#!/bin/bash
# Usage
if [ $# -lt 1 ]; then
    echo "Usage $0 PATH_TO_OUTPUT"
    exit 1
fi

OUTPATH=$(realpath $1)

set -o xtrace
#####################################################################
# Setup tmp dir
rm -rf /tmp/stage1.tmp
mkdir /tmp/stage1.tmp

#####################################################################
# Build and install PHP 7.1

cd ~/php-src
./configure --prefix=/opt/php-7.1 --with-pdo-pgsql --with-zlib-dir --with-freetype-dir --enable-mbstring --with-libxml-dir=/usr --enable-soap --enable-calendar --with-curl --with-mcrypt --with-zlib --with-pgsql --disable-rpath --enable-inline-optimization --with-bz2 --with-zlib --enable-sockets --enable-sysvsem --enable-sysvshm --enable-pcntl --enable-mbregex --enable-exif --enable-bcmath --with-mhash --enable-zip --with-pcre-regex --with-pdo-mysql --with-mysqli=/usr/bin/mysql_config  --with-mysql-sock=/var/run/mysqld/mysqld.sock --with-jpeg-dir=/usr --with-png-dir=/usr --enable-gd-native-ttf --with-openssl --with-fpm-user=www-data --with-fpm-group=www-data --with-libdir=/lib/x86_64-linux-gnu --enable-ftp --with-imap --with-imap-ssl --with-kerberos --with-gettext --with-xmlrpc --with-xsl --enable-opcache --enable-fpm --enable-debug

make -j`nproc`
sudo make install

#####################################################################
# Build and install [TE]/Tracing extension stage 1 component
cd ~/stage1/interpreter-tracing/TE_xdebug/
/opt/php-7.1/bin/phpize
./configure --with-php-config=/opt/php-7.1/bin/php-config
make -j`nproc`
sudo make install

#####################################################################
# Build modified strace [TR] stage 1 component
cd ~/stage1/interpreter-tracing/TR_strace/
./configure --enable-mpers=check
make -j`nproc`

#####################################################################
# Run the PHP test-suite with the tracing extension(TE) enabled, while tracing
# with TR. Output the trace files to /tmp/traces (these will take up multiple
# gigabytes)

cd ~/php-src/

export MYSQL_TEST_USER=test 
export MYSQL_TEST_PASS=test 
export MYSQL_TEST_PASSWD=test 
export MYSQL_TEST_DB=test 
export PDO_MYSQL_TEST_DSN="mysql:dbname=test;host=localhost;port=3306" 
export PDO_MYSQL_TEST_USER=test 
export PDO_MYSQL_TEST_PASS=test 
export PDO_MYSQL_TEST_DB=test
export TEST_PHP_EXECUTABLE=/opt/php-7.1/bin/php

mkdir /tmp/stage1.tmp/traces

~/stage1/interpreter-tracing/TR_strace/strace -o /tmp/stage1.tmp/traces/trace -ff  /opt/php-7.1/bin/php -d zend_extension=xdebug.so ./run-tests.php -q -d zend_extension=xdebug.so

#####################################################################
# Parse the traces output by TR to build an initial mapping of PHP function to syscalls
cd ~/stage1/process-traces
go build
cd ..
./process-traces/process-traces /tmp/stage1.tmp/traces/ > /tmp/stage1.tmp/dynamic-syscalls

#####################################################################
# Collect another mapping of PHP functions to syscalls, statically

# Build a simple extension for obtaining binary offsets of PHP API functions
# Write the offsets to a file
cd ~/stage1/interpreter-static-analysis/enum
/opt/php-7.1/bin/phpize
./configure --with-php-config=/opt/php-7.1/bin/php-config
make

cd ~/stage1/interpreter-static-analysis/
/opt/php-7.1/bin/php -d "extension=enum/modules/enum.so" enum/do-enum.php  > /tmp/stage1.tmp/func_to_addr

# Disassemble and analyze the PHP binary and all of its dynamic libraries to collect another mapping of PHP API function to syscalls
python3 analyze_interpreter.py /tmp/stage1.tmp/func_to_addr /opt/php-7.1/bin/php > /tmp/stage1.tmp/static-syscalls-nr

# Convert the list of syscall numbers to readable names
python3 readablesyscalls.py /tmp/stage1.tmp/static-syscalls-nr > /tmp/stage1.tmp/static-syscalls


#####################################################################
# Merge the Dynamically collected mapping with the statically-collected mapping. This is the output of stage 1
python3 util/mergemappings.py /tmp/stage1.tmp/static-syscalls /tmp/stage1.tmp/dynamic-syscalls > $OUTPATH

