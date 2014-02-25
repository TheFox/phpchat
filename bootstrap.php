<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);
ini_set('memory_limit', '128M');

chdir(__DIR__);

define('DEBUG', 1, true);
define('PHP_EOL_LEN', strlen(PHP_EOL), true);


if(PHP_SAPI != 'cli'){
	print "FATAL ERROR: you need to run this in your shell\n";
	exit(1);
}
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

# TODO: use DIRECTORY_SEPARATOR
if(!file_exists(__DIR__.'/vendor')){
	print "FATAL ERROR: you must first run 'composer install'.\nVisit https://getcomposer.org\n";
	exit(1);
}

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/functions.php';

