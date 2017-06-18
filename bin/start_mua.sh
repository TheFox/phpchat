#!/usr/bin/env bash

SCRIPT_BASEDIR=$(dirname "$0")


cd "${SCRIPT_BASEDIR}/.."

./application.php kernel -d
./application.php cronjob -d
./application.php imap -a 127.0.0.1 -p 21143 -d
./application.php smtp -a 127.0.0.1 -p 21025 -d
