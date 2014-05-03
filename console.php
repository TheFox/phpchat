<?php

require_once __DIR__.'/bootstrap.php';

use TheFox\PhpChat\Console;

$console = null;

try{
	$console = new Console();
}
catch(Exception $e){
	$log->error('console create: '.$e->getMessage());
	exit(1);
}

$log->info('signal handler setup');
declare(ticks = 1);
$exit = 0;
if(function_exists('pcntl_signal')){
	function signalHandler($signo){
		global $exit, $log, $console;
		$exit++;
		print "\n";
		$log->notice('main abort ['.$exit.']');
		$console->setExit($exit);
		if($exit >= 2)
			exit(1);
	}
	pcntl_signal(SIGTERM, 'signalHandler');
	pcntl_signal(SIGINT, 'signalHandler');
}

try{
	$console->init();
}
catch(Exception $e){
	$log->error('init: '.$e->getMessage());
	exit(1);
}

try{
	$console->run();
}
catch(Exception $e){
	$log->error('run: '.$e->getMessage());
	exit(1);
}
