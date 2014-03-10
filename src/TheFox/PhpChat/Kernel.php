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
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log = new Logger('kernel');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::ERROR));
		$this->log->pushHandler(new LoggerStreamHandler('log/kernel.log', Logger::DEBUG));
		
		$this->getLog()->info('setup settings');
		$this->settings = new Settings(getcwd().'/settings.yml');
		$this->getLog()->info('setup settings: done');
		
		$this->getLog()->info('setup local node');
		$this->localNode = new Node();
		$this->localNode->setIdHexStr($this->settings->data['node']['id']);
		$this->localNode->setPort($this->settings->data['node']['port']);
		$this->localNode->setSslKeyPub(file_get_contents($this->settings->data['node']['sslKeyPubPath']));
		$this->getLog()->info('setup local node: done');
		
		$this->getLog()->info('setup table');
		$this->table = new Table($this->settings->data['datadir'].'/table.yml');
		$this->table->setDatadirBasePath($this->settings->data['datadir']);
		$this->table->setLocalNode($this->getLocalNode());
		$this->getLog()->info('setup table: done');
		
		$this->getLog()->info('setup server');
		$this->server = new Server();
		$this->server->setKernel($this);
		$this->server->setIp($this->settings->data['node']['ip']);
		$this->server->setPort($this->settings->data['node']['port']);
		$this->server->setSslPrv($this->settings->data['node']['sslKeyPrvPath'], $this->settings->data['node']['sslKeyPrvPass']);
		$init = $this->server->init();
		$this->getLog()->info('setup server: done');
		if(!$init){
			#print __CLASS__.'->'.__FUNCTION__.': failed'."\n";
			$this->setExit(1);
		}
		
		
		
		#ve($this->server);
		#$this->shutdown();
		
	}
	
	private function getLog(){
		return $this->log;
	}
	
	public function getSettings(){
		return $this->settings;
	}
	
	public function getLocalNode(){
		return $this->localNode;
	}
	
	private function getServer(){
		return $this->server;
	}
	
	public function getTable(){
		return $this->table;
	}
	
	public function run(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		while(!$this->getExit()){
			#print __CLASS__.'->'.__FUNCTION__.': '.$this->getExit()."\n";
			
			$this->server->run();
			
			usleep(static::LOOP_USLEEP);
		}
		
		$this->shutdown();
	}
	
	public function shutdown(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->getLog()->info('shutdown');
		
		$this->getLog()->debug('getNodesNum: '.(int)$this->getTable()->getNodesNum().', '.(int)$this->getSettings()->data['firstRun']);
		$this->getSettings()->data['firstRun'] = $this->getTable()->getNodesNum() <= 0;
		$this->getSettings()->setDataChanged(true);
		
		$this->getServer()->shutdown();
		$this->getTable()->save();
		$this->getSettings()->save();
	}
	
}
