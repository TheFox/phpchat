<?php

namespace TheFox\PhpChat;

use Exception;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler;
use TheFox\Network\Socket;
use TheFox\Dht\Kademlia\Node;

class Server{
	
	private $log = null;
	
	private $kernel = null;
	private $ip = '';
	private $port = 0;
	
	private $clientsId = 0;
	private $clients = array();
	
	private $sslKeyPrvPath = null;
	private $sslKeyPrvPass = null;
	private $isListening = false;
	private $socket = null;
	private $hasDhtNetworkBootstrapped = false;
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log = new Logger('server');
		$this->log->pushHandler(new StreamHandler('php://stdout', Logger::ERROR));
		$this->log->pushHandler(new StreamHandler('log/server.log', Logger::DEBUG));
		
		$this->log->info('start');
	}
	
	public function getLog(){
		return $this->log;
	}
	
	public function setKernel($kernel){
		$this->kernel = $kernel;
	}
	
	public function getKernel(){
		return $this->kernel;
	}
	
	public function setIp($ip){
		$this->ip = $ip;
	}
	
	public function setPort($port){
		$this->port = $port;
	}
	
	public function setSslPrv($sslKeyPrvPath, $sslKeyPrvPass){
		$this->sslKeyPrvPath = $sslKeyPrvPath;
		$this->sslKeyPrvPass = $sslKeyPrvPass;
	}
	
	private function setHasDhtNetworkBootstrapped($hasDhtNetworkBootstrapped){
		$this->hasDhtNetworkBootstrapped = $hasDhtNetworkBootstrapped;
	}
	
	private function getHasDhtNetworkBootstrapped(){
		return $this->hasDhtNetworkBootstrapped;
	}
	
	public function getSettings(){
		if($this->getKernel()){
			return $this->getKernel()->getSettings();
		}
		return null;
	}
	
	public function getLocalNode(){
		if($this->getKernel()){
			return $this->getKernel()->getLocalNode();
		}
		return null;
	}
	
	public function getTable(){
		if($this->getKernel()){
			return $this->getKernel()->getTable();
		}
		
		return null;
	}
	
	public function init(){
		if($this->ip && $this->port){
			$this->log->notice('listen on '.$this->ip.':'.$this->port);
			
			$this->socket = new Socket();
			
			$bind = false;
			try{
				$bind = $this->socket->bind($this->ip, $this->port);
			}
			catch(Exception $e){
				$this->log->error($e->getMessage());
			}
			
			if($bind){
				try{
					if($this->socket->listen()){
						$this->log->notice('listen ok');
						$this->isListening = true;
						
						return true;
					}
				}
				catch(Exception $e){
					$this->log->error($e->getMessage());
				}
			}
			
		}
		
		$this->log->notice('listen failed');
		return false;
	}
	
	public function run(){
		
		$readHandles = array();
		$writeHandles = NULL; $exceptHandles = NULL;
		
		if($this->isListening){
			$readHandles[] = $this->socket->getHandle();
		}
		foreach($this->clients as $clientId => $client){
			// Collect client handles.
			$readHandles[] = $client->getSocket()->getHandle();
		}
		$readHandlesNum = count($readHandles);
		
		$handlesChanged = $this->socket->select($readHandles, $writeHandles, $exceptHandles);
		#$this->log->debug('collect readable sockets: '.(int)$handlesChanged.'/'.$readHandlesNum);
		
		if($handlesChanged){
			foreach($readHandles as $readableHandle){
				if($this->isListening && $readableHandle == $this->socket->getHandle()){
					// Server
					$socket = $this->socket->accept();
					if($socket){
						$client = $this->clientNew($socket);
						
						$client->sendHello();
						
						$this->log->debug('new client: '.$client->getId().', '.$client->getIpPort());
					}
				}
				else{
					// Client
					$client = $this->clientGetByHandle($readableHandle);
					if($client){
						if(feof($client->getSocket()->getHandle())){
							$this->clientRemove($client);
						}
						else{
							#$this->log->debug('old client: '.$client->getId().', '.$client->getIpPort());
							$client->dataRecv();
							
							if($client->getStatus('hasShutdown')){
								$this->clientRemove($client);
							}
						}
					}
				}
			}
		}
	}
	
	public function shutdown(){
		$this->log->info('shutdown');
		
		$this->socket->close();
	}
	
	private function clientNew($socket){
		$this->clientsId++;
		
		$client = new Client();
		$client->setSocket($socket);
		$client->setSslPrv($this->sslKeyPrvPath, $this->sslKeyPrvPass);
		
		$client->setId($this->clientsId);
		$client->setServer($this);
		
		if($this->getSettings()->data['firstRun'] && !$this->getHasDhtNetworkBootstrapped()){
			$this->setHasDhtNetworkBootstrapped(true);
			
			$this->log->debug('dht network bootstrap');
			
			$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_OK);
			$action->functionSet(function($client){
				$client->sendNodeFind($client->getLocalNode()->getIdHexStr());
			});
			$client->actionAdd($action);
		}
		
		$this->clients[$this->clientsId] = $client;
		
		return $client;
	}
	
	private function clientGetByHandle($handle){
		foreach($this->clients as $clientId => $client){
			if($client->getSocket()->getHandle() == $handle){
				return $client;
			}
		}
		
		return null;
	}
	
	private function clientRemove(Client $client){
		$this->log->debug('client remove: '.$client->getId());
		
		$client->shutdown();
		
		$clientsId = $client->getId();
		unset($this->clients[$clientsId]);
	}
	
	public function connect($ip, $port, $clientActions = array()){
		$socket = new Socket();
		
		$connected = false;
		try{
			$connected = $socket->connect($ip, $port);
		}
		catch(Exception $e){
			$this->log->debug('connection to '.$ip.', '.$port.' failed: '.$e->getMessage());
		}
		
		if($connected){
			$client = $this->clientNew($socket);
			
			foreach($clientActions as $clientAction){
				$client->actionAdd($clientAction);
			}
			
			$client->sendHello();
			
			#ve($client);
			
			return true;
		}
		
		return false;
	}
	
	
}
