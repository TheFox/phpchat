<?php

namespace TheFox\PhpChat;

use Exception;
use RuntimeException;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
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
	private $localNode;
	private $hours = 0;
	private $minutes = 0;
	private $seconds = 0;
	
	public function __construct(){
		#print __FUNCTION__.''."\n";
		
		$this->log = new Logger('cronjob');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		$this->log->pushHandler(new LoggerStreamHandler('log/cronjob.log', Logger::DEBUG));
		
		$this->log->info('start');
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
		
		$this->pingClosestNodes();
		$this->msgDbInit();
		$this->bootstrapNodesEnclose();
		$this->nodesNewEnclose();
		
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
			#print 'ping'."\n";
			$this->pingClosestNodes();
			$this->bootstrapNodesEnclose();
			$this->nodesNewEnclose();
		}
		
		if($this->seconds == 0){
			#print 'msgs'."\n";
			$this->msgDbInit();
			$this->nodesNewEnclose();
		}
		if($this->minutes % 5 == 0 && $this->seconds == 0){
			#print 'save'."\n";
			$this->log->debug('save');
			$this->tableNodesSort();
			$this->ipcKernelConnection->execAsync('save');
		}
		if($this->minutes % 15 == 0 && $this->seconds == 0){
			#print 'ping'."\n";
			$this->pingClosestNodes();
			$this->bootstrapNodesEnclose();
		}
		if($this->minutes % 30 == 0 && $this->seconds == 0){
			$this->tableNodesClean();
		}
		
		$this->ipcKernelConnection->run();
		
		$this->seconds++;
		if($this->seconds >= 60){
			$this->seconds = 0;
			$this->minutes++;
			
			#print __FUNCTION__.': '.$this->getExit().', '.$this->hours.':'.$this->minutes.':'.$this->seconds."\n";
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
			#print __FUNCTION__.': '.$this->getExit().', '.$this->hours.', '.$this->minutes.', '.$this->seconds."\n";
			
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
	
	private function pingClosestNodes(){
		$this->log->debug('ping');
		#print __FUNCTION__.''."\n";
		#$this->log->debug(__FUNCTION__);
		$table = $this->ipcKernelConnection->execSync('getTable');
		#ve($table);
		
		$nodes = $table->getNodesClosest(20);
		#ve($nodes);
		
		foreach($nodes as $nodeId => $node){
			#ve($node->getUri());
			$this->ipcKernelConnection->execAsync('serverConnect', array($node->getUri(), false, true));
		}
	}
	
	public function msgDbInit(){
		$this->log->debug('msgs');
		#$this->log->debug(__FUNCTION__);
		#print __FUNCTION__.''."\n";
		
		$this->msgDb = $this->ipcKernelConnection->execSync('getMsgDb', array(), 10);
		$this->settings = $this->ipcKernelConnection->execSync('getSettings');
		$this->setTable($this->ipcKernelConnection->execSync('getTable'));
		
		#ve($this->table);
		
		#print __FUNCTION__.': msgDb A '.(int)($this->msgDb===null)."\n";
		#ve($this->msgDb);
		
		try{
			$this->msgDbInitNodes();
			$this->msgDbSendAll();
		}
		catch(Exception $e){
			$this->log->debug(__FUNCTION__.': '.$e->getMessage());
			#print __FUNCTION__.': '.$e->getMessage()."\n";
		}
	}
	
	public function msgDbInitNodes(){
		#$this->log->debug(__FUNCTION__);
		#print __FUNCTION__.''."\n";
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
				#fwrite(STDOUT, 'msg db, init nodes: find node: '.$msg->getId().' -> '.$msg->getDstNodeId()."\n");
				
				$node = new Node();
				$node->setIdHexStr($msg->getDstNodeId());
				$onode = $this->table->nodeFind($node);
				if($onode && $onode->getSslKeyPub()){
					#fwrite(STDOUT, 'msg db, init nodes:     found node: '.$onode->getIdHexStr()."\n");
					
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
					#fwrite(STDOUT, 'msg db, init nodes:     unknown node: '.$node->getIdHexStr()."\n");
					
					if($this->ipcKernelConnection){
						$this->ipcKernelConnection->execAsync('nodesNewDbNodeAddId', array($node->getIdHexStr()));
					}
				}
			}
		}
		
		if($this->ipcKernelConnection){
			#print __FUNCTION__.': reset msgDb'."\n";
			$this->msgDb = $this->ipcKernelConnection->execSync('getMsgDb', array(), 10);
		}
	}
	
	public function msgDbSendAll(){
		#$this->log->debug(__FUNCTION__);
		#fwrite(STDOUT, __FUNCTION__.''."\n");
		$this->log->debug('msgs send all');
		
		if(!$this->msgDb){
			throw new RuntimeException('msgDb not set', 1);
		}
		/*if(!$this->settings){
			throw new RuntimeException('settings not set', 2);
		}*/
		if(!$this->table){
			throw new RuntimeException('table not set', 3);
		}
		/*if(!$this->localNode){
			throw new RuntimeException('localNode not set', 4);
		}*/
		
		$processedMsgIds = array();
		$processedMsgs = array();
		
		// Send own unsent msgs.
		#$this->log->debug(__FUNCTION__.' unsent own');
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'O'
				&& $msg->getSrcNodeId() == $this->localNode->getIdHexStr()
			){
				#$this->log->debug(__FUNCTION__.'       unsent own: '.$msg->getId());
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		// Send foreign unsent msgs.
		#$this->log->debug(__FUNCTION__.' unsent foreign');
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'U'
				&& $msg->getDstNodeId() != $this->localNode->getIdHexStr()
			){
				#$this->log->debug(__FUNCTION__.'       unsent foreign: '.$msg->getId());
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		// Relay all other msgs.
		#$this->log->debug(__FUNCTION__.' other');
		foreach($this->msgDb->getMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'S'
			){
				#$this->log->debug(__FUNCTION__.'      other: '.$msg->getId());
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		$processedMsgs = array_unique($processedMsgs);
		#$this->log->debug(__FUNCTION__.' processedMsgs: '.count($processedMsgs));
		
		// Don't use messages which reached MSG_FORWARD_TO_NODES_MIN or MSG_FORWARD_TO_NODES_MAX.
		foreach($processedMsgs as $msgId => $msg){
			#$this->log->debug(__FUNCTION__.' search for unset: '.$msg->getId());
			
			$sentNodesC = count($msg->getSentNodes());
			$forwardCycles = $msg->getForwardCycles();
			
			if(
				$sentNodesC >= static::MSG_FORWARD_TO_NODES_MIN
					&& $forwardCycles >= static::MSG_FORWARD_CYCLES_MAX
				
				|| $sentNodesC >= static::MSG_FORWARD_TO_NODES_MAX
			){
				#$this->log->debug(__FUNCTION__.'      set X: '.$msg->getId());
				
				$msg->setStatus('X');
				
				if($this->ipcKernelConnection){
					$this->ipcKernelConnection->execAsync('msgDbMsgSetStatusById', array($msg->getId(), 'X'));
				}
				
				unset($processedMsgs[$msgId]);
			}
			
			elseif( in_array($msg->getDstNodeId(), $msg->getSentNodes()) ){
				#$this->log->debug(__FUNCTION__.'      set D: '.$msg->getId());
				
				$msg->setStatus('D');
				
				if($this->ipcKernelConnection){
					$this->ipcKernelConnection->execAsync('msgDbMsgSetStatusById', array($msg->getId(), 'D'));
				}
				
				unset($processedMsgs[$msgId]);
			}
		}
		
		// Process valid messages.
		$nodes = array();
		$nodeIds = array();
		foreach($processedMsgs as $msgId => $msg){
			#$this->log->debug(__FUNCTION__.' msg: /'.$msg->getId().'/ /'.$msg->getStatus().'/ /'.$msg->getEncryptionMode().'/');
			#$this->log->debug(__FUNCTION__.'      dst:   /'.$msg->getDstNodeId().'/');
			#$this->log->debug(__FUNCTION__.'      relay: /'.$msg->getRelayNodeId().'/');
			
			$dstNode = new Node();
			$dstNode->setIdHexStr($msg->getDstNodeId());
			
			if(!isset($nodes[$dstNode->getIdHexStr()])){
				$nodes[$dstNode->getIdHexStr()] = $dstNode;
			}
			
			// Send it direct.
			$onode = $this->table->nodeFind($dstNode);
			#$this->log->debug(__FUNCTION__.'      onode: '.(int)(is_object($onode)).' ('.(is_object($onode) ? $onode->getUri() : 'N/A').')');
			if($onode && $onode->getUri()->getHost() && $onode->getUri()->getPort()){
				#$this->log->debug(__FUNCTION__.'      dst node found in table');
				
				$nodes[$onode->getIdHexStr()] = $onode;
				
				if(!isset($nodeIds[$onode->getIdHexStr()])){
					$nodeIds[$onode->getIdHexStr()] = array();
				}
				$nodeIds[$onode->getIdHexStr()][$msg->getId()] = $msg;
			}
			
			// Send it to close nodes.
			#$this->log->debug(__FUNCTION__.'      close nodes');
			$closestNodes = $this->table->nodeFindClosest($dstNode, static::MSG_FORWARD_TO_NODES_MAX);
			foreach($closestNodes as $nodeId => $node){
				if(
					$msg->getRelayNodeId() != $node->getIdHexStr()
					&& !in_array($node->getIdHexStr(), $msg->getSentNodes())
					&& $node->getUri()->getHost() && $node->getUri()->getPort()
				){
					#$this->log->debug(__FUNCTION__.'             node: '.$node->getIdHexStr());
					
					$nodes[$node->getIdHexStr()] = $node;
					
					if(!isset($nodeIds[$node->getIdHexStr()])){
						$nodeIds[$node->getIdHexStr()] = array();
					}
					$nodeIds[$node->getIdHexStr()][$msg->getId()] = $msg;
				}
			}
		}
		
		// Nodes for messages.
		$updateMsgs = array();
		foreach($nodeIds as $nodeId => $msgs){
			$node = $nodes[$nodeId];
			#$this->log->debug(__FUNCTION__.' node: /'.$node->getIdHexStr().'/ /'.$node->getUri().'/');
			
			$msgs = array_unique($msgs);
			$msgIds = array();
			
			// Messages per node.
			foreach($msgs as $msgId => $msg){
				$direct = (int)(
					$msg->getDstNodeId() == $node->getIdHexStr()
					&& $node->getUri()->getHost() && $node->getUri()->getPort()
					#&& $this->settings->data['message']['directDelivery']
				);
				$deliver = false;
				if(
					#$direct && $this->settings->data['message']['directDelivery']
					$direct && $this->settings->data['message']['directDelivery']
					#|| $direct && $this->settings->data['message']['directDelivery']
					|| !$direct
					|| $msg->getSrcNodeId() != $this->localNode->getIdHexStr()
				){
					$deliver = true;
				}
				#$deliver = true;
				
				$logMsg = '/'.$msg->getId().'/ /'.$msg->getStatus().'/ /'.$msg->getEncryptionMode().'/';
				$logMsg .= ' /'.$msg->getDstNodeId().'/';
				$logMsg .= ' direct='.$direct.' ('.(int)$this->settings->data['message']['directDelivery'].')';
				$logMsg .= ' source='.(int)($msg->getSrcNodeId() == $this->localNode->getIdHexStr());
				$logMsg .= ' deliver='.(int)$deliver;
				#$this->log->debug(__FUNCTION__.'      msg: '.$logMsg);
				
				if($deliver){
					#$updateMsgs[$msg->getId()] = $msg;
					if(!isset($updateMsgs[$msg->getId()])){
						$updateMsgs[$msg->getId()] = array(
							'obj' => $msg,
							'nodes' => array(
								$node->getIdHexStr() => 1,
							),
						);
					}
					else{
						if(!isset($updateMsgs[$msg->getId()]['nodes'][$node->getIdHexStr()])){
							$updateMsgs[$msg->getId()]['nodes'][$node->getIdHexStr()] = 1;
						}
						else{
							$updateMsgs[$msg->getId()]['nodes'][$node->getIdHexStr()]++;
						}
						#$updateMsgs[$msg->getId()]['nodes'][$node->getIdHexStr()] = 1;
						#$updateMsgs[$msg->getId()]['nodes'][$node->getIdHexStr()]++;
						#ve($updateMsgs[$msg->getId()]['nodes']);
						#$this->log->debug(__FUNCTION__.'      id: '.$msg->getId());
					}
					$msgIds[] = $msg->getId();
				}
			}
			
			#$this->log->debug(__FUNCTION__.'      msgs sending: '.count($msgIds));
			if($msgs && $this->ipcKernelConnection){
				$serverConnectArgs = array($node->getUri(), false, false, $msgIds);
				$this->ipcKernelConnection->execSync('serverConnect', $serverConnectArgs);
			}
		}
		
		foreach($updateMsgs as $msgId => $msg){
			#$this->log->debug(__FUNCTION__.' update msg: '.$msg['obj']->getId());
			#$this->log->debug(__FUNCTION__.' update msg: '.(int)is_object($msg['obj']));
			if($this->ipcKernelConnection){
				$this->ipcKernelConnection->execAsync('msgDbMsgIncForwardCyclesById', array($msg['obj']->getId()));
			}
		}
		
		return $updateMsgs;
	}
	
	private function bootstrapNodesEnclose(){
		$this->log->debug('bootstrap nodes enclose');
		
		$urls = array(
			'http://phpchat.fox21.at/nodes.json',
		);
		$userAgent = PhpChat::NAME.'/'.PhpChat::VERSION.' PHP/'.PHP_VERSION.' curl/'.curl_version()['version'];
		
		if(!$this->table){
			$this->log->debug('get table');
			$this->setTable($this->ipcKernelConnection->execSync('getTable'));
		}
		
		$this->log->debug('local node: /'.$this->table->getLocalNode()->getIdHexStr().'/');
		
		foreach($urls as $url){
			$client = new \GuzzleHttp\Client();
			$response = null;
			
			try{
				$this->log->debug('get url "'.$url.'"');
				$response = $client->get($url, array(
					'headers' => array(
						'User-Agent' => $userAgent,
						'Accept' => 'application/json',
					),
					'connect_timeout' => 3,
					'timeout' => 5,
				));
			}
			catch(Exception $e){
				$this->log->error('url failed, "'.$url.'": '.$e->getMessage());
			}
			
			if($response){
				if($response->getStatusCode() == 200){
					if($response->getHeader('content-type') == 'application/json'){
						$data = array();
						try{
							$data = $response->json();
						}
						catch(Exception $e){
							$this->log->error('JSON: '.$e->getMessage());
						}
						
						if(isset($data['nodes']) && is_array($data['nodes'])){
							foreach($data['nodes'] as $node){
								#ve($node);
								
								$nodeObj = new Node();
								
								$active = true;
								if(isset($node['active'])){
									$active = (bool)$node['active'];
								}
								if($active){
									if(isset($node['uri'])){
										$nodeObj->setUri($node['uri']);
										
										if(isset($node['id'])){
											$nodeObj->setIdHexStr($node['id']);
											
											$this->log->debug('node: /'.$nodeObj->getUri().'/ /'.$nodeObj->getIdHexStr().'/');
											
											if(!$nodeObj->isEqual($this->table->getLocalNode())){
												$this->ipcKernelConnection->execAsync('tableNodeEnclose', array($nodeObj));
											}
											else{
												$this->log->debug('ignore local node');
											}
										}
										else{
											$this->log->debug('node: /'.$nodeObj->getUri().'/');
											$this->ipcKernelConnection->execAsync('nodesNewDbNodeAddUri', array((string)$nodeObj->getUri()));
										}
									}
									
									
								}
							}
						}
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
	
	private function nodesNewEnclose(){
		$this->log->debug('nodes new enclose');
		
		if(!$this->table){
			$this->setTable($this->ipcKernelConnection->execSync('getTable'));
		}
		
		$this->log->debug('nodes new enclose, table');
		
		$nodesNewDb = $this->ipcKernelConnection->execSync('getNodesNewDb', array(), 10);
		#ve($nodesNewDb);
		
		foreach($nodesNewDb->getNodes() as $nodeId => $node){
			if($node['type'] == 'connect'){
				if($node['connectAttempts'] >= 10){
					$this->log->debug('node remove: '.$nodeId);
					$this->ipcKernelConnection->execAsync('nodesNewDbNodeRemove', array($nodeId));
				}
				else{
					$nodeObj = new Node();
					$nodeObj->setUri($node['uri']);
					
					$this->log->debug('node connect: '.(string)$nodeObj->getUri());
					$connected = $this->ipcKernelConnection->execSync('serverConnect', array($nodeObj->getUri(), false, true));
					if($connected){
						$this->log->debug('node remove: '.$nodeId);
						$this->ipcKernelConnection->execAsync('nodesNewDbNodeRemove', array($nodeId));
					}
					else{
						$this->log->debug('node inc connect attempt: '.$nodeId);
						$this->ipcKernelConnection->execAsync('nodesNewDbNodeIncConnectAttempt', array($nodeId));
					}
				}
			}
			elseif($node['type'] == 'find'){
				$nodeObj = new Node();
				$nodeObj->setIdHexStr($node['id']);
				
				if($this->table->nodeFind($nodeObj)){
					$this->log->debug('node remove: '.$nodeId);
					$this->ipcKernelConnection->execAsync('nodesNewDbNodeRemove', array($nodeId));
				}
				elseif($node['findAttempts'] >= 5){
					$this->log->debug('node remove: '.$nodeId);
					$this->ipcKernelConnection->execAsync('nodesNewDbNodeRemove', array($nodeId));
				}
				else{
					$this->log->debug('node find: '.$node['id']);
					$this->ipcKernelConnection->execAsync('serverNodeFind', array($node['id']));
					$this->ipcKernelConnection->execAsync('nodesNewDbNodeIncFindAttempt', array($nodeId));
				}
			}
		}
	}
	
	public function shutdown(){
		#print __FUNCTION__.''."\n";
		$this->log->info('shutdown');
	}
	
	public function ipcKernelShutdown(){
		#print __FUNCTION__.''."\n";
		$this->setExit(1);
	}
	
}
