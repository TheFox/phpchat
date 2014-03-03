<?php

namespace TheFox\PhpChat;

use TheFox\Ipc\Connection;
use TheFox\Ipc\StreamHandler;
use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;

class Kernel extends Thread{
	
	private $log;
	private $settings;
	
	public function __construct(){
		$this->log = new Logger('kernel');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::ERROR));
		$this->log->pushHandler(new LoggerStreamHandler('log/kernel.log', Logger::DEBUG));
		
		$settings = new Settings(__DIR__.'/settings.yml');
	}
	
	public function run(){
		while(!$this->getExit()){
			print __CLASS__.'->'.__FUNCTION__.''."\n";
			sleep(1);
		}
		
		$this->shutdown();
	}
	
	public function shutdown(){
		
	}
	
}
