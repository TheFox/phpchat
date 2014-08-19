<?php

namespace TheFox\PhpChat;

use Exception;
use RuntimeException;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
use TheFox\Ipc\ConnectionClient;
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
	
	private function setIpcKernelConnection($ipcKernelConnection){
		$this->ipcKernelConnection = $ipcKernelConnection;
	}
	
	private function getIpcKernelConnection(){
		return $this->ipcKernelConnection;
	}
	
	public function init(){
		$this->log->info('init');
		
		usleep(100000); // Let the kernel start up.
		$this->setIpcKernelConnection(new ConnectionClient());
		$this->getIpcKernelConnection()->setHandler(new IpcStreamHandler('127.0.0.1', 20001));
		$this->getIpcKernelConnection()->functionAdd('shutdown', $this, 'ipcKernelShutdown');
		$this->getIpcKernelConnection()->functionAdd('msgDbInit', $this, 'msgDbInit');
		
		if(!$this->getIpcKernelConnection()->connect()){
			throw new RuntimeException('Could not connect to kernel process.');
		}
	}
	
	public function cycle(){
		if(!$this->getIpcKernelConnection()){
			throw new RuntimeException('You must first run init().');
		}
		
		$this->pingClosestNodes();
		$this->msgDbInit();
		$this->bootstrapNodesEnclose();
		$this->nodesNewEnclose();
		
		$this->log->debug('save');
		$this->getIpcKernelConnection()->execAsync('save');
		
		$this->getIpcKernelConnection()->run();
		
		$this->shutdown();
	}
	
	public function cycleMsg(){
		if(!$this->getIpcKernelConnection()){
			throw new RuntimeException('You must first run init().');
		}
		
		$this->msgDbInit();
		
		$this->log->debug('save');
		$this->getIpcKernelConnection()->execAsync('save');
		
		$this->getIpcKernelConnection()->run();
		
		$this->shutdown();
	}
	
	private function run(){
		if(!$this->getIpcKernelConnection()){
			throw new RuntimeException('You must first run init().');
		}
		
		if($this->hours == 0 && $this->minutes == 1 && $this->seconds == 0){
			#print 'ping'."\n";  # TODO
			$this->pingClosestNodes();
			$this->bootstrapNodesEnclose();
			$this->nodesNewEnclose();
		}
		
		if($this->seconds == 0){
			#print 'msgs'."\n";  # TODO
			$this->msgDbInit();
			$this->nodesNewEnclose();
		}
		if($this->minutes % 5 == 0 && $this->seconds == 0){
			#print 'save'."\n";  # TODO
			$this->log->debug('save');
			$this->getIpcKernelConnection()->execAsync('save');
		}
		if($this->minutes % 15 == 0 && $this->seconds == 0){
			#print 'ping'."\n";  # TODO
			$this->pingClosestNodes();
			$this->bootstrapNodesEnclose();
		}
		
		$this->getIpcKernelConnection()->run();
		
		$this->seconds++;
		if($this->seconds >= 60){
			$this->seconds = 0;
			$this->minutes++;
			
			#print __FUNCTION__.': '.$this->getExit().', '.$this->hours.':'.$this->minutes.':'.$this->seconds."\n";  # TODO
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
	
	private function pingClosestNodes(){
		$this->log->debug('ping');
		#print __FUNCTION__.''."\n";
		#$this->log->debug(__FUNCTION__);
		$table = $this->getIpcKernelConnection()->execSync('getTable');
		#ve($table);
		
		$nodes = $table->getNodesClosest(20);
		#ve($nodes);
		
		foreach($nodes as $nodeId => $node){
			#ve($node->getUri());
			$this->getIpcKernelConnection()->execAsync('serverConnect', array($node->getUri(), false, true));
		}
	}
	
	public function msgDbInit(){
		$this->log->debug('msgs');
		#$this->log->debug(__FUNCTION__);
		#print __FUNCTION__.''."\n"; # TODO
		
		$this->msgDb = $this->getIpcKernelConnection()->execSync('getMsgDb', array(), 10);
		$this->settings = $this->getIpcKernelConnection()->execSync('getSettings');
		$this->setTable($this->getIpcKernelConnection()->execSync('getTable'));
		
		#ve($this->table);
		
		#print __FUNCTION__.': msgDb A '.(int)($this->msgDb===null)."\n";
		#ve($this->msgDb);
		
		try{
			$this->msgDbInitNodes();
			$this->msgDbSendAll();
		}
		catch(Exception $e){
			$this->log->debug(__FUNCTION__.': '.$e->getMessage());
			print __FUNCTION__.': '.$e->getMessage()."\n";  # TODO
		}
	}
	
	public function msgDbInitNodes(){
		#$this->log->debug(__FUNCTION__);
		#print __FUNCTION__.''."\n";
		
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
				#fwrite(STDOUT, __METHOD__.' find node: '.$msg->getId().' -> '.$msg->getDstNodeId()."\n"); # TODO
				
				$node = new Node();
				$node->setIdHexStr($msg->getDstNodeId());
				$onode = $this->table->nodeFindInBuckets($node);
				if($onode && $onode->getSslKeyPub()){
					#fwrite(STDOUT, __METHOD__.'     found node: '.$onode->getIdHexStr()."\n"); # TODO
					
					$msg->setSrcSslKeyPub($this->localNode->getSslKeyPub());
					$msg->setDstSslPubKey($this->localNode->getSslKeyPub());
					$msg->setSslKeyPrvPath($this->settings->data['node']['sslKeyPrvPath'],
						$this->settings->data['node']['sslKeyPrvPass']);
					
					$msg->setText($msg->decrypt());
					$msg->setEncryptionMode('D');
					$msg->setDstSslPubKey($onode->getSslKeyPub());
					$msg->encrypt();
					
					if($this->getIpcKernelConnection()){
						$this->getIpcKernelConnection()->execAsync('msgDbMsgUpdate', array($msg));
					}
					
				}
				
			}
		}
		
		if($this->getIpcKernelConnection()){
			#print __FUNCTION__.': reset msgDb'."\n";
			$this->msgDb = $this->getIpcKernelConnection()->execSync('getMsgDb', array(), 10);
		}
	}
	
	public function msgDbSendAll(){
		#$this->log->debug(__FUNCTION__);
		#print __FUNCTION__.''."\n";
		
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
		#print __FUNCTION__.': unsent own'."\n";
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'O'
				&& $msg->getSrcNodeId() == $this->localNode->getIdHexStr()
			){
				#fwrite(STDOUT, __METHOD__.' unsent own: '.$msg->getId()."\n"); # TODO
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		// Send foreign unsent msgs.
		#print __FUNCTION__.': unsent foreign'."\n";
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'U'
				&& $msg->getDstNodeId() != $this->localNode->getIdHexStr()
			){
				#fwrite(STDOUT, __METHOD__.' unsent foreign: '.$msg->getId()."\n"); # TODO
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		// Relay all other msgs.
		#print __FUNCTION__.': other'."\n";
		foreach($this->msgDb->getMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'S'
			){
				#fwrite(STDOUT, __METHOD__.' other: '.$msg->getId()."\n"); # TODO
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		$processedMsgs = array_unique($processedMsgs);
		#print __FUNCTION__.': processedMsgs: '.count($processedMsgs)."\n";
		
		foreach($processedMsgs as $msgId => $msg){
			#print __FUNCTION__.': processedMsg A: '. $msg->getId() ."\n";
			
			$sentNodesC = count($msg->getSentNodes());
			$forwardCycles = $msg->getForwardCycles();
			
			if(
				$sentNodesC >= static::MSG_FORWARD_TO_NODES_MIN
					&& $forwardCycles >= static::MSG_FORWARD_CYCLES_MAX
				
				|| $sentNodesC >= static::MSG_FORWARD_TO_NODES_MAX
			){
				#fwrite(STDOUT, __METHOD__.': set X: '.$msg->getId()."\n"); # TODO
				
				$msg->setStatus('X');
				
				if($this->getIpcKernelConnection()){
					$this->getIpcKernelConnection()->execAsync('msgDbMsgSetStatusById', array($msg->getId(), 'X'));
				}
				
				unset($processedMsgs[$msgId]);
			}
			
			elseif( in_array($msg->getDstNodeId(), $msg->getSentNodes()) ){
				#fwrite(STDOUT, __METHOD__.': set D: '.$msg->getId()."\n"); # TODO
				
				$msg->setStatus('D');
				
				if($this->getIpcKernelConnection()){
					$this->getIpcKernelConnection()->execAsync('msgDbMsgSetStatusById', array($msg->getId(), 'D'));
				}
				
				unset($processedMsgs[$msgId]);
			}
		}
		
		
		$nodes = array();
		$nodeIds = array();
		foreach($processedMsgs as $msgId => $msg){
			#fwrite(STDOUT, __METHOD__.' msg: '.$msg->getId().', '.$msg->getStatus().', '.$msg->getEncryptionMode()."\n"); # TODO
			#fwrite(STDOUT, __METHOD__.'      dst:   '.$msg->getDstNodeId()."\n"); # TODO
			#fwrite(STDOUT, __METHOD__.'      relay: '.$msg->getRelayNodeId()."\n");
			
			$dstNode = new Node();
			$dstNode->setIdHexStr($msg->getDstNodeId());
			
			if(!isset($nodes[$dstNode->getIdHexStr()])){
				$nodes[$dstNode->getIdHexStr()] = $dstNode;
			}
			
			$onode = $this->table->nodeFindInBuckets($dstNode);
			if($onode && $onode->getUri()->getHost() && $onode->getUri()->getPort()){
				#fwrite(STDOUT, __METHOD__.'      dst node found in table'."\n"); # TODO
				
				$nodes[$onode->getIdHexStr()] = $onode;
				
				if(!isset($nodeIds[$onode->getIdHexStr()])){
					$nodeIds[$onode->getIdHexStr()] = array();
				}
				$nodeIds[$onode->getIdHexStr()][$msg->getId()] = $msg;
			}
			
			#fwrite(STDOUT, __METHOD__.'      close nodes'."\n"); # TODO
			$closestNodes = $this->table->nodeFindClosest($dstNode, static::MSG_FORWARD_TO_NODES_MAX);
			foreach($closestNodes as $nodeId => $node){
				if(
					$msg->getRelayNodeId() != $node->getIdHexStr()
					&& !in_array($node->getIdHexStr(), $msg->getSentNodes())
					&& $node->getUri()->getHost() && $node->getUri()->getPort()
				){
					#fwrite(STDOUT, __METHOD__.'             n '.$node->getIdHexStr()."\n"); # TODO
					
					$nodes[$node->getIdHexStr()] = $node;
					
					if(!isset($nodeIds[$node->getIdHexStr()])){
						$nodeIds[$node->getIdHexStr()] = array();
					}
					$nodeIds[$node->getIdHexStr()][$msg->getId()] = $msg;
				}
			}
		}
		
		$updateMsgs = array();
		foreach($nodeIds as $nodeId => $msgs){
			$node = $nodes[$nodeId];
			#fwrite(STDOUT, __METHOD__.' node: '.$node->getIdHexStr().', '.$node->getIpPort()."\n"); # TODO
			
			$msgs = array_unique($msgs);
			$msgIds = array();
			
			foreach($msgs as $msgId => $msg){
				$direct = (int)($msg->getDstNodeId() == $node->getIdHexStr()
					&& $node->getUri()->getHost() && $node->getUri()->getPort());
				$tmp = $msg->getId().', '.$msg->getStatus().', '.$msg->getEncryptionMode(); # TODO
				$tmp .= ', direct='.$direct; # TODO
				#fwrite(STDOUT, __METHOD__.'      msg: '.$tmp."\n"); # TODO
				
				$updateMsgs[$msg->getId()] = $msg;
				$msgIds[] = $msg->getId();
			}
			
			if($msgs && $this->getIpcKernelConnection()){
				#fwrite(STDOUT, __METHOD__.'      msgs: '.count($msgs)."\n"); # TODO
				
				$serverConnectArgs = array($node->getUri(), false, false, $msgIds);
				$this->getIpcKernelConnection()->execSync('serverConnect', $serverConnectArgs);
			}
		}
		
		foreach($updateMsgs as $msgId => $msg){
			#fwrite(STDOUT, __METHOD__.' update msg: '.$msg->getId()."\n"); # TODO
			if($this->getIpcKernelConnection()){
				$this->getIpcKernelConnection()->execAsync('msgDbMsgIncForwardCyclesById', array($msg->getId()));
			}
		}
		
		return $updateMsgs;
	}
	
	private function bootstrapNodesEnclose(){
		$this->log->debug('bootstrap nodes enclose');
		
		$urls = array(
			'http://phpchat.fox21.at/nodes.json',
		);
		
		foreach($urls as $url){
			$client = new \GuzzleHttp\Client();
			#$success = false;
			$response = null;
			
			try{
				$this->log->debug('get url "'.$url.'"');
				$response = $client->get($url, array(
					'headers' => array(
						'User-Agent' => 'PHPChat/'.Settings::VERSION.' PHP/'.PHP_VERSION.' curl/'.curl_version()['version'],
						'Accept' => 'application/json',
					),
					'connect_timeout' => 3,
					'timeout' => 5,
				));
				
				#$success = true;
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
											$this->getIpcKernelConnection()->execAsync('tableNodeEnclose', array($nodeObj));
										}
										else{
											$this->getIpcKernelConnection()->execAsync('nodesNewDbNodeAdd', array((string)$nodeObj->getUri()));
										}
									}
									
									$this->log->debug('node: '.$nodeObj->getUri());
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
		
		$nodesNewDb = $this->getIpcKernelConnection()->execSync('getNodesNewDb', array(), 10);
		#ve($nodesNewDb);
		
		foreach($nodesNewDb->getNodes() as $nodeId => $node){
			if($node['connectAttempt'] >= 4){
				$this->log->debug('node remove: '.$nodeId);
				$this->getIpcKernelConnection()->execAsync('nodesNewDbNodeRemove', array($nodeId));
			}
			else{
				$nodeObj = new Node();
				$nodeObj->setUri($node['uri']);
				
				$this->log->debug('node connect: '.(string)$nodeObj->getUri());
				$connected = $this->getIpcKernelConnection()->execSync('serverConnect', array($nodeObj->getUri(), false, true));
				if($connected){
					$this->log->debug('node remove: '.$nodeId);
					$this->getIpcKernelConnection()->execAsync('nodesNewDbNodeRemove', array($nodeId));
				}
				else{
					$this->log->debug('node inc connect attempt: '.$nodeId);
					$this->getIpcKernelConnection()->execAsync('nodesNewDbNodeIncConnectAttempt', array($nodeId));
				}
			}
		}
	}
	
	public function shutdown(){
		#print __FUNCTION__.''."\n";  # TODO
		$this->log->debug('shutdown');
	}
	
	public function ipcKernelShutdown(){
		#print __FUNCTION__.''."\n";  # TODO
		$this->setExit(1);
	}
	
}
