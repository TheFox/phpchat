<?php

if(@date_default_timezone_get() == 'UTC') date_default_timezone_set('UTC');

if(version_compare(PHP_VERSION, '5.3.0', '<')){
	print "FATAL ERROR: you need at least PHP 5.3. Your version: ".PHP_VERSION."\n";
	exit(1);
}

// Check modules installed.
if(!extension_loaded('openssl')){
	print "FATAL ERROR: you must first install openssl.\n";
	exit(1);
}
if(!extension_loaded('sockets')){
	print "FATAL ERROR: you must first install sockets.\n";
	exit(1);
}
if(!function_exists('gzcompress')){
	print "FATAL ERROR: you need the PHP gzip functions.\n";
	exit(1);
}
if(!function_exists('mt_rand')){
	print "FATAL ERROR: you need the PHP mt_rand function.\n";
	exit(1);
}

// Check algorythms.
if(!in_array('sha512', hash_algos())){
	print "FATAL ERROR: sha512 is not available.\n";
	exit(1);
}
if(!in_array('ripemd160', hash_algos())){
	print "FATAL ERROR: ripemd160 is not available.\n";
	exit(1);
}

if(!file_exists('vendor')){
	print "FATAL ERROR: you must first run 'composer install'.\nVisit https://getcomposer.org\n";
	exit(1);
}
