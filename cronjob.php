<?php

require_once __DIR__.'/bootstrap.php';

use TheFox\PhpChat\Cronjob;


$cronjob = new Cronjob();

$log->info('signal handler setup');
declare(ticks = 1);
$exit = 0;
if(function_exists('pcntl_signal')){
	function signalHandler($signo){
		global $exit, $log, $cronjob;
		$exit++;
		print "\n";
		$log->notice('main abort ['.$exit.']');
		$cronjob->setExit($exit);
		if($exit >= 2)
			exit(1);
	}
	pcntl_signal(SIGTERM, 'signalHandler');
	pcntl_signal(SIGINT, 'signalHandler');
}

$cronjob->run();
