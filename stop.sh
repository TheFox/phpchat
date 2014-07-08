#!/bin/sh

SCRIPT_BASEDIR=$(dirname $0)


cd $SCRIPT_BASEDIR

./application.php console -s
./application.php cronjob -s
./application.php kernel -s
