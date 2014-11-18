#!/usr/bin/env bash

RM="rm -rf"
MKDIR="mkdir -p"
MV="mv -i"
CP="cp -rp"
COMPOSER="./composer.phar"
COMPOSER_PREFER_SOURCE=--prefer-source
COMPOSER_PREFER_SOURCE=

SCRIPT_BASEDIR=$(dirname $0)
RELEASE_NAME=$(./application.php info --name_lc)
RELEASE_VERSION=$(./application.php info --version_number)
DST="$RELEASE_NAME-$RELEASE_VERSION"


export COMPOSER_PREFER_SOURCE

cd $SCRIPT_BASEDIR
$MKDIR releases/$DST

for file in application.php bootstrap.php composer.json functions.php Makefile README.md src start.sh start-daemon.sh start-mua.sh stop.sh; do
	$CP $file releases/$DST
done

cd releases/$DST
make install_release || exit 1
make clean_release
cd ..
#exit

find $DST -name .DS_Store -delete
tar -cpzf $DST.tar.gz $DST
chmod -R 777 $DST
$RM $DST

echo "release '$RELEASE_NAME-$RELEASE_VERSION' done"
