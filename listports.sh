#!/bin/sh

while true; do
	pids=$(ps -eo pid,command | grep php | grep -v grep | grep -v listthreads.sh | grep -v listports.sh | awk '{ print $1 }')
	if [ "$pids" != "" ]; then
		for pid in $pids; do
			#echo "check pid '$pid'"
			lsof -P -n -i | grep $pid
		done
	fi
	sleep 1
	echo
done
