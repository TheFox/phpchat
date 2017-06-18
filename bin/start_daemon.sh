#!/usr/bin/env bash

SCRIPT_BASEDIR=$(dirname "$0")


cd "${SCRIPT_BASEDIR}/.."

./application.php kernel -d
./application.php cronjob -d
