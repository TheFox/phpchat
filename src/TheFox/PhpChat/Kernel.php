<?php

namespace TheFox\PhpChat;

use TheFox\Ipc\Connection;
use TheFox\Ipc\StreamHandler;
use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;

class Kernel extends Thread{
	
	private $log;
	private $settings;
	private $server;
	
	public function __construct(){
		$this->log = new Logger('kernel');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::ERROR));
		$this->log->pushHandler(new LoggerStreamHandler('log/kernel.log', Logger::DEBUG));
		
		$settings = new Settings(getcwd().'/settings.yml');
		$this->setSettings($settings);
		
		$this->server = new Server();
		$this->server->setSslPrv($this->settings->data['node']['sslKeyPrvPath'], $this->settings->data['node']['sslKeyPrvPass']);
		$this->server->setIp($settings->data['node']['ip']);
		$this->server->setPort($settings->data['node']['port']);
		$this->server->init();
		
		#ve($this->server);
	}
	
	public function setSettings($settings){
		$this->settings = $settings;
	}
	
	public function localNodeSetIp($ip){
		$settings->data['node']['ip'] = $ip;
	}
	
	public function run(){
		while(!$this->getExit()){
			#print __CLASS__.'->'.__FUNCTION__.''."\n";
			
			$this->server->run();
			
			sleep(1);
		}
		
		$this->shutdown();
	}
	
	public function shutdown(){
		$this->log->info('shutdown');
		
		$this->server->shutdown();
		$this->settings->save();
	}
	
}
