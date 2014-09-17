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
	}
	
	public function setLog($log){
		$this->log = $log;
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
	
	public function kernelHasConsole(){
		if($this->getKernel() && $this->getKernel()->getIpcConsoleConnection()
			&& $this->getKernel()->getIpcConsoleConnection()->getHandler()){
			return $this->getKernel()->getIpcConsoleConnection()->getHandler()->getClientsNum() > 0;
		}
		return false;
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
	
	public function getMsgDb(){
		if($this->getKernel()){
			return $this->getKernel()->getMsgDb();
		}
		
		return null;
	}
	
	public function getHashcashDb(){
		if($this->getKernel()){
			return $this->getKernel()->getHashcashDb();
		}
		
		return null;
	}
	
	public function init(){
		if(!$this->log){
			$this->log = new Logger('server');
			$this->log->pushHandler(new StreamHandler('php://stdout', Logger::ERROR));
			$this->log->pushHandler(new StreamHandler('log/server.log', Logger::DEBUG));
			
			$this->log->info('start');
		}
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
		
		#$this->log->notice('listen failed');
		return false;
	}
	
	public function run(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#print __CLASS__.'->'.__FUNCTION__.': client '.count($this->clients)."\n";
		
		$readHandles = array();
		$writeHandles = null;
		$exceptHandles = null;
		
		if($this->isListening){
			$readHandles[] = $this->socket->getHandle();
		}
		foreach($this->clients as $clientId => $client){
			#$this->log->debug('client: '.$client->getUri());
			
			if($client->getStatus('hasShutdown')){
				$this->log->debug('remove client, hasShutdown: '.$client->getUri());
				$this->clientRemove($client);
			}
			else{
				if($client instanceof TcpClient){
					// Collect client handles.
					$readHandles[] = $client->getSocket()->getHandle();
				}
				
				// Run client.
				#print __CLASS__.'->'.__FUNCTION__.': client run'."\n";
				$client->run();
				$this->getKernel()->incSettingsTrafficIn($client->resetTrafficIn());
				$this->getKernel()->incSettingsTrafficOut($client->resetTrafficOut());
			}
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
						$client = $this->clientNewTcp($socket);
						$client->setStatus('isInbound', true);
						$client->sendHello();
						
						$this->log->debug('new client: '.$client->getUri());
					}
				}
				else{
					// Client
					$client = $this->clientGetByHandle($readableHandle);
					if($client instanceof TcpClient){
						if(feof($client->getSocket()->getHandle())){
							$this->log->debug('remove client, EOF: '.$client->getUri());
							$this->clientRemove($client);
						}
						else{
							#$this->log->debug('old client: '.$client->getUri());
							$client->dataRecv();
						}
					}
				}
			}
		}
	}
	
	public function shutdown(){
		$this->log->info('shutdown');
		$this->log->info('shutdown clients: '.count($this->clients));
		
		if($this->socket){
			$this->socket->close();
		}
		
		foreach($this->clients as $clientId => $client){
			$this->log->info('shutdown client: '.$clientId);
			$this->clientRemove($client);
		}
	}
	
	private function clientNewTcp($socket){
		$this->clientsId++;
		#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.': '.$this->clientsId."\n");
		#$this->log->debug('new tcp client: '.$this->clientsId);
		
		$client = new TcpClient();
		$client->setSocket($socket);
		
		#$this->log->debug('server ssl setup');
		$client->setSslPrv($this->sslKeyPrvPath, $this->sslKeyPrvPass);
		#$this->log->debug('new tcp client ssl: '.($client->getSsl() ? 'ok' : 'N/A'));
		
		return $this->clientAdd($client);
	}
	
	private function clientNewHttp($uri){
		$this->clientsId++;
		#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.': '.$this->clientsId."\n");
		$this->log->debug('new http client: '.$this->clientsId);
		
		$client = new HttpClient();
		$client->setUri($uri);
		
		return $this->clientAdd($client);
	}
	
	private function clientAdd($client){
		$client->setId($this->clientsId);
		$client->setServer($this);
		
		$this->clients[$this->clientsId] = $client;
		#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.': '.count($this->clients)."\n");
		
		$client->logColor('debug', 'client start', 'white', 'black');
		
		$this->networkBootstrap($client);
		
		return $client;
	}
	
	private function clientGetByHandle($handle){
		foreach($this->clients as $clientId => $client){
			if($client instanceof TcpClient && $client->getSocket()->getHandle() == $handle){
				return $client;
			}
		}
		
		return null;
	}
	
	public function clientTalkResponseSend(Client $client, $rid, $status, $userNickname = ''){
		if(isset($this->clients[$client->getId()])){
			$client = $this->clients[$client->getId()];
			$client->sendTalkResponse($rid, $status, $userNickname);
		}
	}
	
	public function clientTalkMsgSend(Client $client, $rid, $userNickname, $text, $ignore = false){
		if(isset($this->clients[$client->getId()])){
			$client = $this->clients[$client->getId()];
			$client->sendTalkMsg($rid, $userNickname, $text, $ignore);
		}
	}
	
	public function clientTalkUserNicknameChangeSend(Client $client, $userNicknameOld, $userNicknameNew){
		if(isset($this->clients[$client->getId()])){
			$client = $this->clients[$client->getId()];
			$client->sendTalkUserNicknameChange($userNicknameOld, $userNicknameNew);
		}
	}
	
	public function clientTalkCloseSend(Client $client, $rid, $userNickname){
		if(isset($this->clients[$client->getId()])){
			$client = $this->clients[$client->getId()];
			$client->sendTalkClose($rid, $userNickname);
		}
	}
	
	private function clientRemove(Client $client){
		$this->log->debug('client remove: '.$client->getId());
		
		if($client->getStatus('isChannelLocal') || $client->getStatus('isChannelPeer')){
			$this->consoleSetModeChannel(false);
			$this->consoleSetModeChannelClient(null);
			
			#$this->consoleMsgAdd();
			$this->consoleMsgAdd('Connection to '.$client->getUri().' closed.', true, true, true);
		}
		
		$client->shutdown();
		
		$this->getKernel()->incSettingsTrafficIn($client->resetTrafficIn());
		$this->getKernel()->incSettingsTrafficOut($client->resetTrafficOut());
		
		$clientsId = $client->getId();
		unset($this->clients[$clientsId]);
	}
	
	public function clientsInfo(){
		$rv = array(
			'clients' => array(),
			'clientsId' => 0,
			'traffic' => array(
				'in' => $this->getSettings()->data['node']['traffic']['in'],
				'out' => $this->getSettings()->data['node']['traffic']['out'],
			),
			'timeCreated' => $this->getSettings()->data['timeCreated'],
		);
		foreach($this->clients as $clientId => $client){
			#$this->log->debug('client: '.$client->getUri());
			
			$rv['clients'][$clientId] = array(
				'hasId' => $client->getStatus('hasId'),
				'hasTalkRequest' => $client->getStatus('hasTalkRequest'),
				'hasTalk' => $client->getStatus('hasTalk'),
				'hasTalkClose' => $client->getStatus('hasTalkClose'),
				'hasShutdown' => $client->getStatus('hasShutdown'),
				
				'isChannelPeer' => $client->getStatus('isChannelPeer'),
				'isChannelLocal' => $client->getStatus('isChannelLocal'),
				'isOutbound' => $client->getStatus('isOutbound'),
				'isInbound' => $client->getStatus('isInbound'),
				'isBridgeConnection' => $client->getStatus('isBridgeConnection'),
			);
		}
		
		$rv['clientsId'] = $this->clientsId;
		
		return $rv;
	}
	
	public function connect($uri, $clientActions = array()){
		$this->log->debug('connect: '.$uri);
		
		/*foreach($clientActions as $clientActionId => $clientAction){
			$this->log->debug('connect action: '.join(', ', $clientAction->getCriteria()));
		}*/
		
		$onode = $this->getTable()->nodeFindByUri($uri);
		if($onode){
			$this->log->debug('connect '.$uri.': '.$onode->getIdHexStr());
			$onode->incConnectionsOutboundAttempts();
		}
		
		try{
			if(is_object($uri)){
				if($uri->getScheme() == 'tcp'){
					$connected = false;
					if($uri->getHost() && $uri->getPort()){
						$socket = new Socket();
						$connected = $socket->connect($uri->getHost(), $uri->getPort());
						$client = $this->clientNewTcp($socket);
						$client->setStatus('isOutbound', true);
					}
					if($connected){
						$client->actionsAdd($clientActions);
						$client->sendHello();
						
						return true;
					}
				}
				else{
					$this->log->warning('connection to '.$uri.' failed: invalid uri scheme ('.$uri->getScheme().')');
				}
				
				/*elseif($uri->getScheme() == 'http'){
					$client = $this->clientNewHttp($uri);
					$client->actionsAdd($clientActions);
					return true;
				}*/
				
			}
			else{
				$this->log->warning('connection to '.$uri.' failed: uri is no object');
			}
		}
		catch(Exception $e){
			$this->log->warning('connection to '.$uri.' failed: '.$e->getMessage());
		}
		
		return false;
	}
	
	public function consoleMsgAdd($msgText = '', $showDate = false, $printPs1 = false, $clearLine = false){
		if($this->getKernel() && $this->getKernel()->getIpcConsoleConnection()){
			$this->getKernel()->getIpcConsoleConnection()->execAsync('msgAdd',
				array($msgText, $showDate, $printPs1, $clearLine));
		}
	}
	
	public function consoleSetModeChannel($modeChannel){
		if($this->getKernel() && $this->getKernel()->getIpcConsoleConnection()){
			$this->getKernel()->getIpcConsoleConnection()->execAsync('setModeChannel', array($modeChannel));
		}
	}
	
	public function consoleSetModeChannelClient($client){
		if($this->getKernel() && $this->getKernel()->getIpcConsoleConnection()){
			$this->getKernel()->getIpcConsoleConnection()->execAsync('setModeChannelClient', array($client));
		}
	}
	
	public function imapMailAdd(Msg $msg){
		if($this->getKernel() && $this->getKernel()->getIpcImapConnection()){
			
			$version = $msg->getVersion();
			$id = $msg->getId();
			$srcNodeId = $msg->getSrcNodeId();
			$srcUserNickname = $msg->getSrcUserNickname();
			$dstNodeId = $msg->getDstNodeId();
			$subject = $msg->getSubject();
			$text = $msg->getText();
			$checksum = $msg->getChecksum();
			$relayCount = $msg->getRelayCount();
			$encryptionMode = $msg->getEncryptionMode();
			$status = $msg->getStatus();
			$timeCreated = $msg->getTimeCreated();
			$timeReceived = $msg->getTimeReceived();
			
			$args = array();
			$args[] = $version;
			$args[] = $id;
			$args[] = $srcNodeId;
			$args[] = $srcUserNickname;
			$args[] = $dstNodeId;
			$args[] = $subject;
			$args[] = $text;
			$args[] = $checksum;
			$args[] = $relayCount;
			$args[] = $encryptionMode;
			$args[] = $status;
			$args[] = $timeCreated;
			$args[] = $timeReceived;
			
			$this->getKernel()->getIpcImapConnection()->execAsync('mailAdd', $args);
		}
	}
	
	public function nodeFind($nodeIdToFind){
		$settingsBridgeClient = $this->getSettings()->data['node']['bridge']['client']['enabled'];
		
		#fwrite(STDOUT, 'nodeFind: '.$nodeIdToFind.' /'.(int)$settingsBridgeClient.'/'."\n");
		
		if($this->getTable()){
			foreach($this->getTable()->getNodesClosest() as $nodeId => $node){
				$connect = $node->getBridgeServer() && $settingsBridgeClient
					|| !$settingsBridgeClient;
				#fwrite(STDOUT, 'send node find to '.$node->getIdHexStr().': /'.(int)$node->getBridgeServer().'/ /'.(int)$connect.'/'."\n");
				
				if($connect){
					$clientActions = array();
					$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_SUCCESSFULL);
					$action->setName('node_find_after_id');
					$action->functionSet(function($action, $client) use($nodeIdToFind) {
						#fwrite(STDOUT, 'action function: CRITERION_AFTER_ID_SUCCESSFULL, '.$nodeIdToFind.''."\n");
						$client->sendNodeFind($nodeIdToFind);
					});
					$clientActions[] = $action;
					
					// Wait to get response. Don't disconnect instantly after sending.
					$action = new ClientAction(ClientAction::CRITERION_AFTER_NODE_FOUND);
					$action->setName('node_find_node_found');
					$action->functionSet(function($action, $client){
						#fwrite(STDOUT, 'action function: CRITERION_AFTER_NODE_FOUND'."\n");
					});
					$clientActions[] = $action;
					
					$action = new ClientAction(ClientAction::CRITERION_AFTER_PREVIOUS_ACTIONS);
					$action->setName('node_find_after_previous_actions_send_quit');
					$action->functionSet(function($action, $client){
						#fwrite(STDOUT, 'action function: CRITERION_AFTER_PREVIOUS_ACTIONS'."\n");
						
						$client->sendQuit();
						$client->shutdown();
					});
					$clientActions[] = $action;
					
					$this->connect($node->getUri(), $clientActions);
				}
			}
		}
	}
	
	public function networkBootstrap($client){
		// Network Bootstrap
		if($this->getSettings()->data['firstRun'] && !$this->getHasDhtNetworkBootstrapped()){
			$this->setHasDhtNetworkBootstrapped(true);
			
			$this->log->debug('dht network bootstrap');
			
			$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_SUCCESSFULL);
			$action->setName('network_bootstrap_node_find');
			$action->functionSet(function($action, $client){
				$client->sendNodeFind($client->getLocalNode()->getIdHexStr());
			});
			$client->actionAdd($action);
		}
	}
	
}
