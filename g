#!/bin/sh

grep -nR --color=always $* *.php src tests

