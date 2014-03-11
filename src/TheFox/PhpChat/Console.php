<?php

namespace TheFox\PhpChat;

use RuntimeException;
use DateTime;
use DateTimeZone;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
use TheFox\Ipc\ConnectionClient;
use TheFox\Ipc\StreamHandler as IpcStreamHandler;

class Console extends Thread{
	
	const LOOP_USLEEP = 100000;
	
	private $log = null;
	private $ipcKernelConnection = null;
	private $ps1 = 'phpchat:> ';
	private $tcols = 0;
	private $tlines = 0;
	private $msgStack = array();
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log = new Logger('console');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::INFO));
		$this->log->pushHandler(new LoggerStreamHandler('log/console.log', Logger::DEBUG));
		
		$this->log->info('start');
		
		$this->log->debug('tput setup');
		$this->tcols = (int)exec('tput cols');
		$this->tlines = (int)exec('tput lines');
		$this->log->debug('cols = '.$this->tcols.', lines = '.$this->tlines);
	}
	
	private function getLog(){
		return $this->log;
	}
	
	private function setIpcKernelConnection($ipcKernelConnection){
		$this->ipcKernelConnection = $ipcKernelConnection;
	}
	
	private function getIpcKernelConnection(){
		return $this->ipcKernelConnection;
	}
	
	private function setPs1($ps1){
		$this->ps1 = $ps1;
	}
	
	private function getPs1(){
		return $this->ps1;
	}
	
	public function init(){
		$this->setIpcKernelConnection(new ConnectionClient());
		$this->getIpcKernelConnection()->setHandler(new IpcStreamHandler('127.0.0.1', 20000));
		
		if(!$this->getIpcKernelConnection()->connect()){
			throw new RuntimeException('Could not connect to kernel process.');
		}
		
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log->debug('tty setup');
		#system('stty -icanon && echo icanon ok');
		system('stty -icanon');
		
		print PHP_EOL."Type '/help' for help.".PHP_EOL;
		
		return true;
	}
	
	public function run(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(!$this->getIpcKernelConnection()){
			throw new RuntimeException('You must first run init().');
		}
		
		while(!$this->getExit()){
			#print __CLASS__.'->'.__FUNCTION__.': '.$this->getExit()."\n";
			
			$this->getIpcKernelConnection()->run();
			
			usleep(static::LOOP_USLEEP);
		}
		
		$this->shutdown();
	}
	
	public function shutdown(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->getLog()->info('shutdown');
		
		$this->log->debug('tty restore');
		system('stty sane');
		
	}
	
}
