#!/bin/sh

SCRIPT_BASEDIR=$(dirname $0)


cd $SCRIPT_BASEDIR

./application.php kernel -d
sleep 1
./application.php cronjob -d
./application.php imap -d -a 127.0.0.1 -p 21143
./application.php smtp -d -a 127.0.0.1 -p 21025
./application.php console
