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
		
		#$this->actionClientsPing = false;
		#$this->actionConnect = array();
		
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
	
	public function runInit(){
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
	
	/*public function runClients(){
		#$this->log->debug('clients: '.count($this->clients));
		
		$read = array();
		if($this->isListening){
			$read[] = $this->socket->getHandle();
		}
		$write = NULL; $except = NULL;
		foreach($this->clients as $clientId => $client){
			if($this->exit) break;
			
			if($client->getBootstrapSuccess() === false){ // default = null
				$this->log->debug('remove client BOOTSTRAP: '.$client->getId());
				$this->clientRemove($client);
				continue;
			}
			if($client->getTimeLastSeen() < time() - Server::CLIENT_TTL){
				$this->log->debug('remove client TTL: '.$client->getId());
				$this->clientRemove($client);
				continue;
			}
			if($client->getIsOnlyNodeFindFound()){
				$this->log->debug('remove client FIND: '.$client->getId().' ('.(int)$client->getIsNetworkBootstrap().')');
				$this->clientRemove($client);
				continue;
			}
			
			#$this->log->debug('client '.$clientId.': actionsExec');
			if($client->actionsExec()){
				$this->clientRemove($client);
				continue;
			}
			
			$read[] = $client->getSocket();
		}
		
		#$this->log->debug('collect readable sockets: '.count($read));
		
		$socketsChanged = 0;
		$socketsChanged = Socket::select($read, $write, $except);
		
		if($socketsChanged){
			foreach($read as $readableSocket){
				if($this->isListening && $readableSocket == $this->socket->getHandle()){
					
					// Server
					$socket = Socket::accept($readableSocket);
					if($socket){
						Socket::getPeerName($socket, $ip, $port);
						
						$client = $this->clientNew();
						$client->setSocket($socket);
						$client->setSocketConnected(true);
						$client->setIp($ip);
						$client->setPort($port);
						
						$this->clientSetBySocket($client);
						
						$this->log->debug('client connects: '.$client->getIp().':'.$client->getPort().' ('.$client->getSocket().')');
						
						$client->sendHello();
					}
					
				}
				else{
					
					// Client
					
					$buffer = '';
					try{
						$buffer = Socket::read($readableSocket, 2048, PHP_BINARY_READ);
						if($buffer === false){
							$this->log->warning( 'socket_read: '. Socket::lastErrorByLastError($readableSocket) );
							Socket::clearError($readableSocket);
						}
					}
					catch(Exception $e){
						$this->log->error($e->getMessage());
					}
					
					if(isset($this->clientsBySocket[$readableSocket])){
						$client = $this->clientsBySocket[$readableSocket];
						
						$client->setTimeLastSeen(time());
						if($client->nodeIsSet()){
							$client->getNode()->setTimeLastSeen(time());
						}
						
						
						$breakit = false;
						$bufferLen = 0;
						if($buffer){
							$bufferLen = strlen($buffer);
							$buffer = str_replace("\r", '', $buffer);
							
							if(ord($buffer[0]) == 4){
								$this->log->debug('socket rcev '.$client->getIp().':'.$client->getPort().': EOF');
								$breakit = true;
							}
							
							while(!$this->exit && $buffer && !$breakit){
								$nlpos = strpos($buffer, "\n");
								if($nlpos === false){
									$client->appendSocketBuffer($buffer);
									$buffer = '';
								}
								else{
									$line = $client->getSocketBuffer().substr($buffer, 0, $nlpos);
									$client->setSocketBuffer('');
									
									$rest = substr($buffer, $nlpos + 1);
									if($rest){
										$buffer = $rest;
									}
									else{
										$buffer = '';
									}
									#$this->log->debug('socket '.$client->getIp().':'.$client->getPort().' handle line: '.$client->getSocket());
									$breakit = $breakit || $client->handleLine($line);
								}
							}
						}
						
						if($buffer !== false && !$bufferLen){
							$this->log->debug('socket disconnect '.$client->getIp().':'.$client->getPort());
							$breakit = true;
						}
						if($breakit){
							$this->clientRemove($client);
						}
					}
					else{
						$this->log->warning('client by socket not set');
						$this->clientRemove($client);
					}
				}
			}
		}
	}
	
	public function runActions(){
		
		// Action Connect
		foreach($this->actionConnect as $actionId => $action){
			#print "".$this->name.": action connect: $actionId, ".$action['ip'].":".$action['port']."\n";
			$this->log->debug('action connect: '.$actionId.', '.$action['ip'].':'.$action['port']);
			#ve($action); sleep(5);
			
			$client = $this->clientNew();
			$client->setIsChannel($action['isChannel']);
			
			if( $client->connect($action['ip'], $action['port']) ){
				// Connection established
				$this->log->debug('action connect: connection established');
				
				$this->clientSetBySocket($client);
				
				// Followup Actions
				foreach($action['followupActions'] as $followupActionId => $followupAction){
					if($followupAction['function'] == 'clientActionNodeFindAdd'){
						$client->setIsOnlyNodeFind(true);
						
						$distance = isset($followupAction['arguments']['distance']) ? $followupAction['arguments']['distance'] : null;
						$nodesFoundIds = isset($followupAction['arguments']['nodesFoundIds']) ? $followupAction['arguments']['nodesFoundIds'] : null;
						$client->actionNodeFindAdd($followupAction['arguments']['nodeId'], $distance, $nodesFoundIds);
					}
					elseif($followupAction['function'] == 'clientActionTalkRequestAdd'){
						$client->actionSslInitAdd();
						$client->actionTalkRequestAdd();
					}
					elseif($followupAction['function'] == 'clientActionSslKeyPublicGetAdd'){
						$client->actionSslKeyPublicGetAdd($followupAction['arguments']['nodeSslKeyPubFingerprint']);
					}
					unset($action['followupActions'][$followupActionId]);
				}
			}
			else{
				// Connection failed
				$this->log->debug('action connect: connection failed');
				
				if($client->getIsChannel()){
					$this->consoleMsgConnectionFailed($action['ip'], $action['port']);
				}
				
				$this->clientRemove($client);
			}
			
			unset($this->actionConnect[$actionId]);
		}
		
		// Action Clients PING
		if($this->actionClientsPing){
			$this->actionClientsPing = false;
			
			$this->clientsSendPing();
		}
		
	}
	
	public function runShutdown(){
		$this->log->info('close socket');
		#Socket::close($serverSocket);
		#Socket::close($this->socket);
		$this->socket->close();
		
		$this->clientsSendQuit();
		
		if($this->ssl !== null){
			openssl_free_key($this->ssl);
		}
		
		// Leave the thread running until we saved all data.
		$this->log->debug('wait for shutdown');
		$this->waitForShutdown = true;
		while(!$this->canShutdown){
			sleep(1);
		}
		
		$this->log->debug('memory peak: '.memory_get_peak_usage(true));
		
		$this->log->notice('exit');
	}
	
	public function run(){
		$this->runInit();
		
		while(!$this->exit){
			
			#$this->log->debug('runClients');
			$this->runClients();
			
			#$this->log->debug('runActions');
			$this->runActions();
			
			#sleep(1); # TODO
			usleep(100000);
		}
		
		$this->runShutdown();
	}
	
	#public function clientNew($socket = null, $ip = '', $port = 0){
	public function clientNew(){
		$this->clientsId = $this->clientsId + 1;
		
		$this->log->debug('client new: '.$this->clientsId);
		
		$client = new Client();
		
		$client->setId($this->clientsId);
		#$client->setSocket($socket);
		#$client->setIp($ip);
		#$client->setPort($port);
		$client->setSettings($this->settings);
		$client->setSsl($this->ssl);
		$client->setLog($this->log);
		$client->setServer($this);
		
		$this->clients[$this->clientsId] = $client;
		
		return $client;
	}
	
	public function clientSetBySocket($client){
		$this->clientsBySocket[$client->getSocket()] = $client;
	}
	
	public function clientRemove($client){
		$this->log->debug('client remove: '.$client->getId().', '.(int)$client->getIsChannel());
		
		if($client->getIsChannel()){
			$client->setIsChannel(false);
			
			$this->consoleSetModeChannel(false);
			$this->consoleSetChannelServerClientId(0);
			$this->consoleMsgAdd('Connection to '.$client->getIp().':'.$client->getPort().' closed.');
		}
		
		$client->socketShutdown();
		
		unset($this->clientsBySocket[$client->getSocket()]);
		unset($this->clients[$client->getId()]);
	}
	
	public function clientActionTalkResponseAdd($clientId, $rid, $status, $userNickname = ''){
		if(isset($this->clients[$clientId])){
			$client = $this->clients[$clientId];
			
			$this->log->debug('client action, talk response');
			
			$action = array();
			$action['name'] = 'talkResponse';
			$action['exec'] = false;
			$action['timeCreated'] = time();
			
			$action['rid'] = $rid;
			$action['status'] = $status;
			$action['userNickname'] = $userNickname;
			
			$client->actionAdd($action);
		}
	}
	
	public function clientActionTalkMsgAdd($clientId, $ignore, $userNickname, $text){
		if(isset($this->clients[$clientId])){
			$client = $this->clients[$clientId];
			
			$this->log->debug('client action, talk msg');
			
			$action = array();
			$action['name'] = 'talkMsg';
			$action['exec'] = false;
			$action['timeCreated'] = time();
			
			$action['ignore'] = $ignore; // For fake msgs.
			$action['userNickname'] = $userNickname;
			$action['text'] = $text;
			
			$client->actionAdd($action);
		}
	}
	
	public function clientActionTalkCloseAdd($clientId, $userNickname){
		if(isset($this->clients[$clientId])){
			$client = $this->clients[$clientId];
			
			$this->log->debug('client action, talk close');
			
			$action = array();
			$action['name'] = 'talkClose';
			$action['exec'] = false;
			$action['timeCreated'] = time();
			
			$action['userNickname'] = $userNickname;
			
			$client->actionAdd($action);
		}
	}
	
	public function clientsSendPing(){
		foreach($this->clients as $id => $client){
			$client->sendPing();
		}
	}
	
	public function clientsSendQuit(){
		$this->log->debug('send QUIT to all clients');
		foreach($this->clients as $id => $client){
			$client->sendQuit();
		}
	}
	
	
	
	
	
	
	
	public function setActionClientsPing(){
		$this->actionClientsPing = true;
	}
	
	public function actionConnectAdd($ip, $port, $isChannel = false, $followupActions = null){
		#$this->log->debug('action connect: '.$ip.':'.$port.', '.(int)$isChannel);
		
		if(!is_object($followupActions)){
			$followupActions = array();
		}
		
		$action = array();
		$action['ip'] = $ip;
		$action['port'] = $port;
		$action['isChannel'] = $isChannel;
		$action['followupActions'] = $followupActions;
		
		$this->actionConnect[] = $action;
	}
	
	
	
	public function consoleMsgAdd($text, $modeRead = true){
		if(isset($this->settings['tmp']['console'])){
			$this->settings['tmp']['console']->msgAdd($text, $modeRead);
		}
	}
	
	public function consoleMsgConnectionFailed($ip, $port){
		$this->consoleMsgAdd('Connection to '.$ip.':'.$port.' failed.');
	}
	
	public function consoleSetModeChannel($modeChannel = true){
		if(isset($this->settings['tmp']['console'])){
			$this->settings['tmp']['console']->setModeChannel($modeChannel);
		}
	}
	
	public function consoleSetChannelServerClientId($clientId){
		if(isset($this->settings['tmp']['console'])){
			$this->settings['tmp']['console']->setChannelServerClientId($clientId);
		}
	}
	*/
}
