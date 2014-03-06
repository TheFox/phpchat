<?php

namespace TheFox\PhpChat;

use TheFox\Ipc\Connection;
use TheFox\Ipc\StreamHandler;
use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
use TheFox\Dht\Kademlia\Table;
use TheFox\Dht\Kademlia\Node;

class Kernel extends Thread{
	
	const LOOP_USLEEP = 100000;
	
	private $log;
	private $settings;
	private $localNode;
	private $server;
	private $table;
	
	public function __construct(){
		$this->log = new Logger('kernel');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::ERROR));
		$this->log->pushHandler(new LoggerStreamHandler('log/kernel.log', Logger::DEBUG));
		
		$this->settings = new Settings(getcwd().'/settings.yml');
		
		$this->localNode = new Node();
		$this->localNode->setIdHexStr($this->settings->data['node']['id']);
		$this->localNode->setPort($this->settings->data['node']['port']);
		
		$this->server = new Server();
		$this->server->setKernel($this);
		$this->server->setIp($this->settings->data['node']['ip']);
		$this->server->setPort($this->settings->data['node']['port']);
		$this->server->setSslPrv($this->settings->data['node']['sslKeyPrvPath'], $this->settings->data['node']['sslKeyPrvPass']);
		$this->server->init();
		
		$this->table = new Table($this->settings->data['datadir'].'/table.yml');
		$this->table->setLocalNode($this->getLocalNode());
		
		#ve($this->server);
		$this->shutdown();
		
	}
	
	public function getLocalNode(){
		return $this->localNode;
	}
	
	public function setSettingsNodeIpPub($ipPub){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		if($ipPub != '127.0.0.1'){
			$this->settings->data['node']['ipPub'] = $ipPub;
			$this->settings->setDataChanged(true);
		}
	}
	
	public function run(){
		while(!$this->getExit()){
			#print __CLASS__.'->'.__FUNCTION__.''."\n";
			
			$this->server->run();
			
			usleep(static::LOOP_USLEEP);
		}
		
		$this->shutdown();
	}
	
	public function shutdown(){
		$this->log->info('shutdown');
		
		$this->server->shutdown();
		$this->table->save();
		$this->settings->save();
	}
	
}
