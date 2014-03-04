<?php

namespace TheFox\PhpChat;

use Exception;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler;
use TheFox\Network\Socket;

class Server{
	
	private $log;
	
	private $ip;
	private $port;
	
	private $clientsId = 0;
	private $clients = array();
	
	private $sslKeyPrvPath = null;
	private $sslKeyPrvPass = null;
	private $isListening = false;
	private $socket = null;
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log = new Logger('server');
		$this->log->pushHandler(new StreamHandler('php://stdout', Logger::ERROR));
		$this->log->pushHandler(new StreamHandler('log/server.log', Logger::DEBUG));
		
		$this->log->info('start');
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
					}
				}
				catch(Exception $e){
					$this->log->error($e->getMessage());
				}
			}
			
		}
	}
	
	public function run(){
		
		$readHandles = array();
		$writeHandles = NULL; $exceptHandles = NULL;
		
		if($this->isListening){
			$readHandles[] = $this->socket->getHandle();
		}
		foreach($this->clients as $clientId => $client){
			$readHandles[] = $client->getSocket()->getHandle();
		}
		$readHandlesNum = count($readHandles);
		
		$handlesChanged = $this->socket->select($readHandles, $writeHandles, $exceptHandles);
		$this->log->debug('collect readable sockets: '.$handlesChanged.'/'.$readHandlesNum);
		
		if($handlesChanged){
			foreach($readHandles as $readableHandle){
				if($this->isListening && $readableHandle == $this->socket->getHandle()){
					$this->log->debug('server handle: '.$readableHandle);
					
					// Server
					$socket = $this->socket->accept();
					if($socket){
						
						$client = new Client();
						$client->setSocket($socket);
						$client->setSslPrv($this->sslKeyPrvPath, $this->sslKeyPrvPass);
						
						$this->clientAdd($client);
						
						ve($client);
						
						$this->log->debug('new client: '.$client->getId().', '.$client->getIpPort());
					}
				}
				else{
					$this->log->debug('client handle: '.$readableHandle);
					
					// Client
					$client = $this->clientGetByHandle($readableHandle);
					if($client){
						$this->log->debug('old client: '.$client->getId().', '.$client->getIpPort());
						$client->dataRecv();
					}
				}
			}
		}
	}
	
	public function shutdown(){
		$this->log->info('shutdown');
		
		$this->socket->close();
	}
	
	public function clientAdd(Client $client){
		$this->clientsId++;
		
		$client->setId($this->clientsId);
		
		$this->clients[$this->clientsId] = $client;
	}
	
	public function clientGetByHandle($handle){
		foreach($this->clients as $clientId => $client){
			if($client->getSocket()->getHandle() == $handle){
				return $client;
			}
		}
		
		return null;
	}
	
	#public function settingsSet
	
}
