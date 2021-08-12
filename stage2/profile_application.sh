#!/bin/sh
RED='\033[0;31m'
NC='\033[0m' 

FUNCS="$3"

HEADER='data/unistd_64.h'

printf "[${RED} Removing $2 if it exists ${NC}]\n"
rm -rf $2 $2.sqlite

printf "[${RED} Registering syscalls from unistd.h ${NC}]\n"
build/register-syscalls $2.sqlite $HEADER

printf "[${RED} Registering functions from $FUNCS ${NC}]\n"
build/register-functions $2.sqlite $FUNCS

printf "[${RED} Scanning php files in $1 ${NC}]\n"
build/scan-project $1 $2.sqlite

printf "[${RED} Building flat profile ${NC}]\n"
build/build-filters $2.sqlite > $2 

printf "[${RED} Done. Profile in $2 ${NC}]\n"
