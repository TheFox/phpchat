<?php

namespace TheFox\PhpChat;

use Exception;
use RuntimeException;
use Guzzle\Http\Client as GuzzleHttpClient; // v==3
#use GuzzleHttp\Client as GuzzleHttpClient; // v>=4
use TheFox\Ipc\ClientConnection;
use TheFox\Ipc\StreamHandler as IpcStreamHandler;
use TheFox\Dht\Kademlia\Node;

class Cronjob extends Thread{
	
	const MSG_FORWARD_TO_NODES_MIN = 8;
	const MSG_FORWARD_TO_NODES_MAX = 20;
	const MSG_FORWARD_CYCLES_MAX = 100;
	
	private $log;
	private $ipcKernelConnection = null;
	private $msgDb;
	private $settings;
	private $table;
	private $nodesNewDb;
	private $localNode;
	private $hours = 0;
	private $minutes = 0;
	private $seconds = 0;
	
	public function setLog($log){
		$this->log = $log;
	}
	
	public function getLog(){
		return $this->log;
	}
	
	public function setMsgDb($msgDb){
		$this->msgDb = $msgDb;
	}
	
	public function getMsgDb(){
		return $this->msgDb;
	}
	
	public function setSettings($settings){
		$this->settings = $settings;
	}
	
	public function getSettings(){
		return $this->settings;
	}
	
	public function setTable($table){
		$this->table = $table;
		if($this->table){
			$this->localNode = $this->table->getLocalNode();
		}
	}
	
	public function getTable(){
		return $this->table;
	}
	
	public function setNodesNewDb($nodesNewDb){
		$this->nodesNewDb = $nodesNewDb;
	}
	
	public function getNodesNewDb(){
		return $this->nodesNewDb;
	}
	
	public function init(){
		$this->log->info('init');
		
		usleep(100000); // Let the kernel start up.
		$this->ipcKernelConnection = new ClientConnection();
		$this->ipcKernelConnection->setHandler(new IpcStreamHandler('127.0.0.1', 20001));
		$this->ipcKernelConnection->functionAdd('shutdown', $this, 'ipcKernelShutdown');
		$this->ipcKernelConnection->functionAdd('msgDbInit', $this, 'msgDbInit');
		
		if(!$this->ipcKernelConnection->connect()){
			throw new RuntimeException('Could not connect to kernel process.');
		}
	}
	
	public function cycle(){
		if(!$this->ipcKernelConnection){
			throw new RuntimeException('You must first run init().');
		}
		
		$this->pingNodes();
		$this->msgDbInit();
		$this->bootstrapNodesEnclose();
		$this->nodesNewEnclose();
		
		$this->log->debug('save');
		$this->ipcKernelConnection->execAsync('save');
		
		$this->ipcKernelConnection->run();
		
		$this->shutdown();
	}
	
	public function cyclePingNodes(){
		if(!$this->ipcKernelConnection){
			throw new RuntimeException('You must first run init().');
		}
		
		$this->pingNodes();
		
		$this->log->debug('save');
		$this->ipcKernelConnection->execAsync('save');
		
		$this->ipcKernelConnection->run();
		
		$this->shutdown();
	}
	
	public function cycleMsg(){
		if(!$this->ipcKernelConnection){
			throw new RuntimeException('You must first run init().');
		}
		
		$this->msgDbInit();
		
		$this->log->debug('save');
		$this->ipcKernelConnection->execAsync('save');
		
		$this->ipcKernelConnection->run();
		
		$this->shutdown();
	}
	
	public function cycleNodesNew(){
		if(!$this->ipcKernelConnection){
			throw new RuntimeException('You must first run init().');
		}
		
		$this->nodesNewEnclose();
		
		$this->log->debug('save');
		$this->ipcKernelConnection->execAsync('save');
		
		$this->ipcKernelConnection->run();
		
		$this->shutdown();
	}
	
	public function cycleBootstrapNodes(){
		if(!$this->ipcKernelConnection){
			throw new RuntimeException('You must first run init().');
		}
		
		$this->bootstrapNodesEnclose();
		
		$this->log->debug('save');
		$this->ipcKernelConnection->execAsync('save');
		
		$this->ipcKernelConnection->run();
		
		$this->shutdown();
	}
	
	private function run(){
		if(!$this->ipcKernelConnection){
			throw new RuntimeException('You must first run init().');
		}
		
		if($this->hours == 0 && $this->minutes == 1 && $this->seconds == 0){
			$this->pingNodes();
			$this->bootstrapNodesEnclose();
			$this->nodesNewEnclose();
		}
		
		if($this->seconds == 0){
			$this->msgDbInit();
			$this->nodesNewEnclose();
		}
		if($this->minutes % 5 == 0 && $this->seconds == 0){
			$this->log->debug('save');
			$this->tableNodesSort();
			$this->ipcKernelConnection->execAsync('save');
		}
		if($this->minutes % 15 == 0 && $this->seconds == 0){
			$this->pingNodes();
		}
		if($this->minutes % 30 == 0 && $this->seconds == 0){
			$this->bootstrapNodesEnclose();
			$this->tableNodesClean();
		}
		
		$this->ipcKernelConnection->run();
		
		$this->seconds++;
		if($this->seconds >= 60){
			$this->seconds = 0;
			$this->minutes++;
			
			$this->log->debug($this->getExit().' '.$this->hours.':'.$this->minutes.':'.$this->seconds);
		}
		if($this->minutes >= 60){
			$this->minutes = 0;
			$this->hours++;
		}
	}
	
	public function loop(){
		$this->log->debug('loop start');
		while(!$this->getExit()){
			$this->run();
			sleep(1);
		}
		$this->log->debug('loop end');
		
		$this->shutdown();
	}
	
	private function tableNodesClean(){
		$this->ipcKernelConnection->execAsync('tableNodesClean');
	}
	
	private function tableNodesSort(){
		$this->ipcKernelConnection->execAsync('tableNodesSort');
	}
	
	private function pingNodes(){
		$this->log->debug('ping');
		#$this->log->debug(__FUNCTION__);
		$this->settings = $this->ipcKernelConnection->execSync('getSettings');
		#$table = $this->ipcKernelConnection->execSync('getTable');
		$this->setTable($this->ipcKernelConnection->execSync('getTable'));
		#ve($this->table);
		
		if($this->settings->data['node']['bridge']['client']['enabled']){
			// Ping closest bridge server nodes.
			$this->log->debug('ping closest bridge server nodes');
			foreach($this->table->getNodesClosestBridgeServer(20) as $nodeId => $node){
				$this->log->debug('ping: '.$node->getUri());
				$this->ipcKernelConnection->execAsync('serverConnectPingOnly', array($node->getUri()));
			}
		}
		else{
			// Ping nodes with unconfirmed SSL Public Key.
			$this->log->debug('ping unconfirmed nodes');
			foreach($this->table->getNodes() as $nodeId => $node){
				if($node->getSslKeyPubStatus() == 'U'){
					$this->log->debug('ping: /'.$node->getUri().'/ /'.$node->getSslKeyPubStatus().'/');
					$this->ipcKernelConnection->execAsync('serverConnectPingOnly', array($node->getUri()));
				}
			}
			
			// Ping closest nodes.
			$this->log->debug('ping closest nodes');
			foreach($this->table->getNodesClosest(20) as $nodeId => $node){
				$this->log->debug('ping: '.$node->getUri());
				$this->ipcKernelConnection->execAsync('serverConnectPingOnly', array($node->getUri()));
			}
		}
	}
	
	public function msgDbInit(){
		$this->log->debug('msgs');
		#$this->log->debug(__FUNCTION__);
		
		$this->msgDb = $this->ipcKernelConnection->execSync('getMsgDb', array(), 10);
		$this->settings = $this->ipcKernelConnection->execSync('getSettings');
		$this->setTable($this->ipcKernelConnection->execSync('getTable'));
		
		try{
			$this->msgDbInitNodes();
			$this->msgDbSendAll();
		}
		catch(Exception $e){
			$this->log->debug(__FUNCTION__.': '.$e->getMessage());
		}
	}
	
	public function msgDbInitNodes(){
		#$this->log->debug(__FUNCTION__);
		$this->log->debug('msgs init nodes');
		
		if(!$this->msgDb){
			throw new RuntimeException('msgDb not set', 1);
		}
		if(!$this->settings){
			throw new RuntimeException('settings not set', 2);
		}
		if(!$this->table){
			throw new RuntimeException('table not set', 3);
		}
		if(!$this->localNode){
			throw new RuntimeException('localNode not set', 4);
		}
		
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				$msg->getDstNodeId()
				&& $msg->getStatus() == 'O'
				&& $msg->getEncryptionMode() == 'S'
			){
				
				$node = new Node();
				$node->setIdHexStr($msg->getDstNodeId());
				$onode = $this->table->nodeFind($node);
				if($onode && $onode->getSslKeyPub()){
					
					$msg->setSrcSslKeyPub($this->localNode->getSslKeyPub());
					$msg->setDstSslPubKey($this->localNode->getSslKeyPub());
					$msg->setSslKeyPrvPath($this->settings->data['node']['sslKeyPrvPath'],
						$this->settings->data['node']['sslKeyPrvPass']);
					
					$msg->setText($msg->decrypt());
					$msg->setEncryptionMode('D');
					$msg->setDstSslPubKey($onode->getSslKeyPub());
					$msg->encrypt();
					
					if($this->ipcKernelConnection){
						$this->ipcKernelConnection->execAsync('msgDbMsgUpdate', array($msg));
					}
				}
				else{
					if($this->ipcKernelConnection){
						$this->ipcKernelConnection->execAsync('nodesNewDbNodeAddFind', array($node->getIdHexStr()));
					}
				}
			}
		}
		
		if($this->ipcKernelConnection){
			$this->msgDb = $this->ipcKernelConnection->execSync('getMsgDb', array(), 10);
		}
	}
	
	public function msgDbSendAll(){
		#$this->log->debug(__FUNCTION__);
		$this->log->debug('msgs send all');
		
		if(!$this->msgDb){
			throw new RuntimeException('msgDb not set', 1);
		}
		if(!$this->settings){
			throw new RuntimeException('settings not set', 2);
		}
		if(!$this->table){
			throw new RuntimeException('table not set', 3);
		}
		if(!$this->localNode){
			throw new RuntimeException('localNode not set', 4);
		}
		
		$processedMsgIds = array();
		$processedMsgs = array();
		
		// Send own unsent msgs.
		#$this->log->debug('unsent own');
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'O'
				&& $msg->getSrcNodeId() == $this->localNode->getIdHexStr()
			){
				#$this->log->debug('      unsent own: '.$msg->getId());
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		// Send foreign unsent msgs.
		#$this->log->debug('unsent foreign');
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'U'
				&& $msg->getDstNodeId() != $this->localNode->getIdHexStr()
			){
				#$this->log->debug('      unsent foreign: '.$msg->getId());
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		// Relay all other msgs.
		#$this->log->debug('other');
		foreach($this->msgDb->getMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'S'
			){
				#$this->log->debug('     other: '.$msg->getId());
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		$processedMsgs = array_unique($processedMsgs);
		#$this->log->debug('processedMsgs: '.count($processedMsgs));
		
		// Don't use messages which reached MSG_FORWARD_TO_NODES_MIN or MSG_FORWARD_TO_NODES_MAX.
		foreach($processedMsgs as $msgId => $msg){
			#$this->log->debug('search for unset: '.$msg->getId());
			
			$sentNodesC = count($msg->getSentNodes());
			$forwardCycles = $msg->getForwardCycles();
			
			if(
				$sentNodesC >= static::MSG_FORWARD_TO_NODES_MIN
					&& $forwardCycles >= static::MSG_FORWARD_CYCLES_MAX
				
				|| $sentNodesC >= static::MSG_FORWARD_TO_NODES_MAX
			){
				#$this->log->debug('     set X: '.$msg->getId());
				
				$msg->setStatus('X');
				
				if($this->ipcKernelConnection){
					$this->ipcKernelConnection->execAsync('msgDbMsgSetStatusById', array($msg->getId(), 'X'));
				}
				
				unset($processedMsgs[$msgId]);
			}
			
			elseif( in_array($msg->getDstNodeId(), $msg->getSentNodes()) ){
				#$this->log->debug('     set D: '.$msg->getId());
				
				$msg->setStatus('D');
				
				if($this->ipcKernelConnection){
					$this->ipcKernelConnection->execAsync('msgDbMsgSetStatusById', array($msg->getId(), 'D'));
				}
				
				unset($processedMsgs[$msgId]);
			}
		}
		
		// Collect Nodes.
		$nodes = array();
		$nodeIds = array();
		if($this->settings->data['node']['bridge']['client']['enabled']){
			$this->log->debug('bridge delivery');
			
			$nodesBridgeServer = $this->table->getNodesClosestBridgeServer();
			foreach($nodesBridgeServer as $nodeId => $node){
				if((string)$node->getUri()){
					$nodes[$node->getIdHexStr()] = $node;
					$nodeIds[$node->getIdHexStr()] = array();
				}
			}
			foreach($processedMsgs as $msgId => $msg){
				#$msgOut = '/'.$msg->getId().'/ /'.$msg->getStatus().'/ /'.$msg->getEncryptionMode().'/';
				#$this->log->debug('msg: '.$msgOut);
				
				foreach($nodeIds as $nodeId => $msgs){
					if($msg->getRelayNodeId() != $nodeId && !in_array($nodeId, $msg->getSentNodes())){
						$nodeIds[$nodeId][$msg->getId()] = $msg;
					}
				}
			}
		}
		else{
			$this->log->debug('normal delivery');
			foreach($processedMsgs as $msgId => $msg){
				#$msgOut = '/'.$msg->getId().'/ /'.$msg->getStatus().'/ /'.$msg->getEncryptionMode().'/';
				#$this->log->debug('msg: '.$msgOut);
				#$this->log->debug('     dst:   /'.$msg->getDstNodeId().'/');
				#$this->log->debug('     relay: /'.$msg->getRelayNodeId().'/');
				
				$dstNode = new Node();
				$dstNode->setIdHexStr($msg->getDstNodeId());
				
				if(!isset($nodes[$dstNode->getIdHexStr()])){
					$nodes[$dstNode->getIdHexStr()] = $dstNode;
				}
				
				// Send it direct.
				$onode = $this->table->nodeFind($dstNode);
				#$msgOut = (int)(is_object($onode)).' ('.(is_object($onode) ? $onode->getUri() : 'N/A').')';
				#$this->log->debug('     onode: '.$msgOut);
				#if($onode && $onode->getUri()->getHost() && $onode->getUri()->getPort()){
				if($onode && (string)$onode->getUri()){
					#$this->log->debug('     dst node found in table');
					
					$nodes[$onode->getIdHexStr()] = $onode;
					
					if(!isset($nodeIds[$onode->getIdHexStr()])){
						$nodeIds[$onode->getIdHexStr()] = array();
					}
					$nodeIds[$onode->getIdHexStr()][$msg->getId()] = $msg;
				}
				
				// Send it to close nodes.
				#$this->log->debug('     close nodes');
				$closestNodes = $this->table->nodeFindClosest($dstNode, static::MSG_FORWARD_TO_NODES_MAX);
				foreach($closestNodes as $nodeId => $node){
					if(
						$msg->getRelayNodeId() != $node->getIdHexStr()
						&& !in_array($node->getIdHexStr(), $msg->getSentNodes())
						&& (string)$node->getUri()
					){
						#$this->log->debug('            node: '.$node->getIdHexStr());
						
						$nodes[$node->getIdHexStr()] = $node;
						
						if(!isset($nodeIds[$node->getIdHexStr()])){
							$nodeIds[$node->getIdHexStr()] = array();
						}
						$nodeIds[$node->getIdHexStr()][$msg->getId()] = $msg;
					}
				}
			}
		}
		
		// Nodes for messages.
		$updateMsgs = array();
		foreach($nodeIds as $nodeId => $msgs){
			$node = $nodes[$nodeId];
			#$this->log->debug('node: /'.$node->getIdHexStr().'/ /'.$node->getUri().'/');
			
			$msgs = array_unique($msgs);
			$msgIds = array();
			
			// Messages per node.
			foreach($msgs as $msgId => $msg){
				$direct = (int)(
					$msg->getDstNodeId() == $node->getIdHexStr()
					&& $node->getUri()->getHost() && $node->getUri()->getPort()
				);
				
				#$logMsg = '/'.$msg->getId().'/ /'.$msg->getStatus().'/ /'.$msg->getEncryptionMode().'/';
				#$logMsg .= ' /'.$msg->getDstNodeId().'/';
				#$logMsg .= ' direct='.$direct.'';
				#$logMsg .= ' source='.(int)($msg->getSrcNodeId() == $this->localNode->getIdHexStr());
				#$this->log->debug('     msg: '.$logMsg);
				
				if(!isset($updateMsgs[$msg->getId()])){
					$updateMsgs[$msg->getId()] = array(
						'obj' => $msg,
						'nodes' => array(
							$node->getIdHexStr() => $node,
						),
					);
				}
				else{
					if(!isset($updateMsgs[$msg->getId()]['nodes'][$node->getIdHexStr()])){
						$updateMsgs[$msg->getId()]['nodes'][$node->getIdHexStr()] = $node;
					}
				}
				$msgIds[] = $msg->getId();
			}
			
			#$this->log->debug('     msgs sending: '.count($msgIds));
			if($msgIds && $this->ipcKernelConnection){
				$serverConnectArgs = array($node->getUri(), $msgIds);
				$this->ipcKernelConnection->execSync('serverConnectTransmitMsgs', $serverConnectArgs);
			}
		}
		
		foreach($updateMsgs as $msgId => $msg){
			#$this->log->debug('update msg: '.$msg['obj']->getId());
			#$this->log->debug('update msg: '.(int)is_object($msg['obj']));
			if($this->ipcKernelConnection){
				$this->ipcKernelConnection->execAsync('msgDbMsgIncForwardCyclesById', array($msg['obj']->getId()));
			}
		}
		
		return $updateMsgs;
	}
	
	public function bootstrapNodesEnclose(){
		$this->log->debug('bootstrap nodes enclose');
		
		if(!$this->settings){
			$this->log->debug('get settings');
			$this->settings = $this->ipcKernelConnection->execSync('getSettings');
		}
		
		$urls = array(
			'http://phpchat.fox21.at/nodes.json',
		);
		
		if(!$this->table){
			$this->log->debug('get table');
			$this->setTable($this->ipcKernelConnection->execSync('getTable'));
		}
		
		$this->log->debug('local node: /'.$this->table->getLocalNode()->getIdHexStr().'/');
		
		foreach($urls as $url){
			$client = $this->createGuzzleHttpClient();
			$response = null;
			
			try{
				$this->log->debug('get url "'.$url.'"');
				$request = $client->get($url);
				$response = $request->send();
			}
			catch(Exception $e){
				$this->log->error('url failed, "'.$url.'": '.$e->getMessage());
			}
			
			if($response){
				if($response->getStatusCode() == 200){
					if($response->getHeader('content-type') == 'application/json'){
						$json = array();
						try{
							$json = $response->json();
						}
						catch(Exception $e){
							$this->log->error('JSON: '.$e->getMessage());
						}
						
						$this->bootstrapNodesEncloseJson($json);
					}
					else{
						$this->log->warning('response type for "'.$url.'": '.$response->getHeader('content-type'));
					}
				}
				else{
					$this->log->warning('response code for "'.$url.'": '.$response->getStatusCode());
				}
			}
		}
	}
	
	public function createGuzzleHttpClient(){
		$curlVersion = curl_version();
		$userAgent = PhpChat::NAME.'/'.PhpChat::VERSION.' PHP/'.PHP_VERSION.' curl/'.$curlVersion['version'];
		$clientOptions = array(
			'headers' => array(
				'User-Agent' => $userAgent,
				'Accept' => 'application/json',
			),
			'connect_timeout' => 3,
			'timeout' => 5,
			'verify' => false,
		);
		$client = new GuzzleHttpClient('', $clientOptions);
		
		return $client;
	}
	
	public function bootstrapNodesEncloseJson($json){
		$settingsBridgeClient = $this->settings->data['node']['bridge']['client']['enabled'];
		
		$nodes = array();
		
		if(isset($json['nodes']) && is_array($json['nodes'])){
			foreach($json['nodes'] as $node){
				#$this->log->debug('node');
				
				$nodeObj = new Node();
				
				$active = false;
				if(isset($node['active'])){
					$active = (bool)$node['active'];
				}
				if($active){
					if(isset($node['id'])){
						$nodeObj->setIdHexStr($node['id']);
					}
					if(isset($node['uri'])){
						$nodeObj->setUri($node['uri']);
					}
					if(isset($node['bridgeServer'])){
						$nodeObj->setBridgeServer($node['bridgeServer']);
					}
					
					$this->log->debug('node: /'.$nodeObj->getIdHexStr().'/ /'.$nodeObj->getUri().'/');
					
					if(!$nodeObj->isEqual($this->table->getLocalNode())){
						if($nodeObj->getIdHexStr() == '00000000-0000-4000-8000-000000000000'){
							/*if(!$nodeObj->getBridgeServer() && !$this->settings->data['node']['bridge']['client']['enabled']){
								$this->log->debug('no bridge server');
							}*/
							
							if((string)$nodeObj->getUri()){
								#$logTmp = '('.(int)$nodeObj->getBridgeServer().',';
								#$logTmp .= (int)$this->settings->data['node']['bridge']['client']['enabled'].')';
								#$this->log->debug('    NO ID, URI '.$logTmp);
								
								$noBridge = !$nodeObj->getBridgeServer() && !$settingsBridgeClient;
								$isBridgeServer = $nodeObj->getBridgeServer() && !$settingsBridgeClient;
								$isBridgeService = $nodeObj->getBridgeServer() && $settingsBridgeClient;
								if($noBridge || $isBridgeServer || $isBridgeService){
									$nodes[] = array('type' => 'connect', 'node' => $nodeObj);
									#$this->log->debug('    add connect');
								}
							}
							/*else{
								$this->log->debug('    NO ID, NO URI');
							}*/
						}
						else{
							if((string)$nodeObj->getUri()){
								#$this->log->debug('    ID, URI');
								$nodes[] = array('type' => 'enclose', 'node' => $nodeObj);
							}
							else{
								#$this->log->debug('    ID, NO URI');
								$nodes[] = array('type' => 'find', 'node' => $nodeObj);
							}
						}
					}
					/*else{
						$this->log->debug('    ignore local node');
					}*/
				}
			}
		}
		
		foreach($nodes as $nodeId => $node){
			#$msgOut = $nodeId.' '.$node['type'];
			#$msgOut .= ' /'.$node['node']->getIdHexStr().'/ /'.$node['node']->getUri().'/';
			#$this->log->debug('node: '.$msgOut);
			
			$functionName = '';
			$functionArgs = array();
			if($node['type'] == 'enclose'){
				$functionName = 'tableNodeEnclose';
				$functionArgs = array($node['node']);
			}
			elseif($node['type'] == 'connect'){
				$functionName = 'nodesNewDbNodeAddConnect';
				$functionArgs = array((string)$node['node']->getUri());
			}
			elseif($node['type'] == 'find'){
				$functionName = 'nodesNewDbNodeAddFind';
				$functionArgs = array($node['node']->getIdHexStr());
			}
			
			$functionArgs[] = $node['node']->getBridgeServer();
			
			if($this->ipcKernelConnection && $functionName){
				$this->ipcKernelConnection->execAsync($functionName, $functionArgs);
			}
		}
		
		return $nodes; // Return only for tests.
	}
	
	public function nodesNewEnclose(){
		$this->log->debug('nodes new enclose');
		
		if($this->ipcKernelConnection){
			$this->setTable($this->ipcKernelConnection->execSync('getTable'));
		}
		if($this->ipcKernelConnection){
			$this->nodesNewDb = $this->ipcKernelConnection->execSync('getNodesNewDb', array(), 10);
		}
		
		$settingsBridgeClient = $this->settings->data['node']['bridge']['client']['enabled'];
		
		$nodes = array();
		
		foreach($this->nodesNewDb->getNodes() as $nodeId => $node){
			#$this->log->debug('node: '.$nodeId.' '.(int)$node['bridgeServer']);
			
			if($node['type'] == 'connect'){
				if($node['connectAttempts'] >= 10){
					$this->log->debug('node remove: '.$nodeId);
					#$nodes[] = array('type' => 'remove', 'node' => null);
					if($this->ipcKernelConnection){
						$this->ipcKernelConnection->execAsync('nodesNewDbNodeRemove', array($nodeId));
					}
					else{
						$this->nodesNewDb->nodeRemove($nodeId);
					}
				}
				else{
					$nodeObj = new Node();
					$nodeObj->setUri($node['uri']);
					$nodeObj->setBridgeServer($node['bridgeServer']);
					
					if($settingsBridgeClient){
						if($nodeObj->getBridgeServer()){
							$nodes[] = array('type' => 'connect', 'node' => $nodeObj);
							$this->nodesNewEncloseServerConnect($nodeObj, $nodeId);
						}
						else{
							$this->log->debug('node remove: '.$nodeId);
							$nodes[] = array('type' => 'remove', 'node' => $nodeObj);
							
							if($this->ipcKernelConnection){
								$this->ipcKernelConnection->execAsync('nodesNewDbNodeRemove', array($nodeId));
							}
							else{
								$this->nodesNewDb->nodeRemove($nodeId);
							}
						}
					}
					else{
						$nodes[] = array('type' => 'connect', 'node' => $nodeObj);
						$this->nodesNewEncloseServerConnect($nodeObj, $nodeId);
					}
				}
			}
			elseif($node['type'] == 'find'){
				$nodeObj = new Node();
				$nodeObj->setIdHexStr($node['id']);
				$nodeObj->setBridgeServer($node['bridgeServer']);
				
				if($this->table->nodeFind($nodeObj)){
					$this->log->debug('node remove: '.$nodeId);
					#$nodes[] = array('type' => 'remove', 'node' => $nodeObj);
					if($this->ipcKernelConnection){
						$this->ipcKernelConnection->execAsync('nodesNewDbNodeRemove', array($nodeId));
					}
					else{
						$this->nodesNewDb->nodeRemove($nodeId);
					}
				}
				elseif($node['findAttempts'] >= 5){
					$this->log->debug('node remove: '.$nodeId);
					#$nodes[] = array('type' => 'remove', 'node' => $nodeObj);
					if($this->ipcKernelConnection){
						$this->ipcKernelConnection->execAsync('nodesNewDbNodeRemove', array($nodeId));
					}
					else{
						$this->nodesNewDb->nodeRemove($nodeId);
					}
				}
				else{
					$this->log->debug('node find: '.$node['id']);
					$nodes[] = array('type' => 'find', 'node' => $nodeObj);
					if($this->ipcKernelConnection){
						$this->ipcKernelConnection->execAsync('serverNodeFind', array($node['id']));
						$this->ipcKernelConnection->execAsync('nodesNewDbNodeIncFindAttempt', array($nodeId));
					}
					else{
						$this->nodesNewDb->nodeIncFindAttempt($nodeId);
					}
				}
			}
		}
		
		/*foreach($nodes as $nodeId => $node){
			$logTmp = '/'.(int)is_object($node['node']).'/ /'.(int)$node['node']->getBridgeServer().'/';
			fwrite(STDOUT, 'node: '.$node['type'].' '.$logTmp.PHP_EOL);
		}*/
		
		return $nodes; // Return only for tests.
	}
	
	private function nodesNewEncloseServerConnect($node, $nodeId){
		$this->log->debug('node connect: '.(string)$node->getUri().' /'.(int)$node->getBridgeServer().'/');
		
		if($this->ipcKernelConnection){
			$connected = $this->ipcKernelConnection->execSync('serverConnectPingOnly', array($node->getUri()));
			if($connected){
				$this->log->debug('node remove: '.$nodeId);
				$this->ipcKernelConnection->execAsync('nodesNewDbNodeRemove', array($nodeId));
			}
			else{
				$this->log->debug('node inc connect attempt: '.$nodeId);
				$this->ipcKernelConnection->execAsync('nodesNewDbNodeIncConnectAttempt', array($nodeId));
			}
		}
		else{
			$this->nodesNewDb->nodeIncConnectAttempt($nodeId);
		}
	}
	
	public function shutdown(){
		$this->log->info('shutdown');
	}
	
	public function ipcKernelShutdown(){
		$this->setExit(1);
	}
	
}
