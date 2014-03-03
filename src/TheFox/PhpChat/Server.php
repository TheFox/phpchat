<?php

namespace TheFox\PhpChat;

use Exception;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler;
use TheFox\Network\BsdSocket;

class Server{
	
	#const NODES_PING_NUM = 20;
	#const NODES_FIND_NUM = 8;
	const CLIENT_TTL = 300;
	
	private $log;
	
	private $addr;
	private $port;
	
	private $clientsId;
	private $clients;
	private $clientsBySocket;
	
	#private $actionClientsPing;
	#private $actionConnect;
	
	private $ssl;
	private $isListening;
	private $socket;
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log = new Logger('server');
		$this->log->pushHandler(new StreamHandler('php://stdout', Logger::ERROR));
		$this->log->pushHandler(new StreamHandler('log/server.log', Logger::DEBUG));
		
		$this->log->info('start');
		
		$this->clientsId = 0;
		$this->clients = array();
		$this->clientsBySocket = array();
		
		$this->ssl = null;
		$this->isListening = false;
	}
	
	public function setAddr($addr){
		$this->addr = $addr;
	}
	
	public function setPort($port){
		$this->port = $port;
	}
	
	public function sslInit($keyPrvPath, $keyPrvPass){
		$this->log->debug('ssl: loading keys');
		
		$this->ssl = openssl_pkey_get_private(file_get_contents($keyPrvPath), $keyPrvPass);
		
		$this->log->debug('ssl: '.($this->ssl ? 'ok' : 'N/A'));
	}
	
	public function init(){
		if($this->addr && $this->port){
			$this->log->notice('listen on '.$this->addr.':'.$this->port);
			
			$this->socket = new BsdSocket();
			
			$bind = false;
			try{
				$bind = $this->socket->bind($this->addr, $this->port);
			}
			catch(Exception $e){
				$this->log->error($e->getMessage());
			}
			
			if($bind){
				try{
					if($this->socket->listen()){
						$this->isListening = true;
					}
				}
				catch(Exception $e){
					$this->log->error($e->getMessage());
				}
			}
			
		}
	}
	
}
