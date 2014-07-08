#!/bin/sh

SCRIPT_BASEDIR=$(dirname $0)


cd $SCRIPT_BASEDIR

./application.php kernel -s
