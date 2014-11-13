#!/usr/bin/env bash

SCRIPT_BASEDIR=$(dirname $0)


cd $SCRIPT_BASEDIR

php bootstrap.php
./application.php kernel -d
./application.php cronjob -d
