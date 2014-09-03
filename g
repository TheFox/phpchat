#!/bin/sh

grep -nRi --color=always $* *.php src tests/*.php
