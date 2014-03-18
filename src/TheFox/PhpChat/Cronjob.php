<?php

namespace TheFox\PhpChat;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
use TheFox\Ipc\ConnectionServer;
use TheFox\Ipc\StreamHandler as IpcStreamHandler;

class Cronjob extends Thread{
	
	const LOOP_USLEEP = 100000;
	
	private $log;
	private $ipcKernelConnection = null;
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log = new Logger('cronjob');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::ERROR));
		$this->log->pushHandler(new LoggerStreamHandler('log/cronjob.log', Logger::DEBUG));
		
		$this->ipcKernelConnection = new ConnectionServer();
		$this->ipcKernelConnection->setHandler(new IpcStreamHandler('127.0.0.1', 20001));
		$this->ipcKernelConnection->functionAdd('shutdown', $this, 'ipcConsoleShutdown');
		$this->ipcKernelConnection->connect();
		
	}
	
	public function run(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		while(!$this->getExit()){
			print __CLASS__.'->'.__FUNCTION__.': '.$this->getExit()."\n";
			
			$this->ipcKernelConnection->run();
			
			usleep(static::LOOP_USLEEP);
		}
		
		$this->shutdown();
	}
	
	public function shutdown(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
	}
	
	public function ipcConsoleShutdown(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		$this->setExit(1);
	}
	
}
