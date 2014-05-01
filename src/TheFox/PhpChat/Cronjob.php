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
	
	#const LOOP_USLEEP = 100000;
	const MSG_FORWARD_TO_NODES_MIN = 8;
	const MSG_FORWARD_TO_NODES_MAX = 20;
	const MSG_FORWARD_CYCLES_MAX = 100;
	
	private $log;
	private $ipcKernelConnection = null;
	private $msgDb;
	private $settings;
	private $table;
	private $localNode;
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log = new Logger('cronjob');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::ERROR));
		$this->log->pushHandler(new LoggerStreamHandler('log/cronjob.log', Logger::DEBUG));
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
		$this->setIpcKernelConnection(new ConnectionClient());
		$this->getIpcKernelConnection()->setHandler(new IpcStreamHandler('127.0.0.1', 20001));
		$this->getIpcKernelConnection()->functionAdd('shutdown', $this, 'ipcKernelShutdown');
		
		if(!$this->getIpcKernelConnection()->connect()){
			throw new RuntimeException('Could not connect to kernel process.');
		}
	}
	
	public function run(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		if(!$this->getIpcKernelConnection()){
			throw new RuntimeException('You must first run init().');
		}
		
		$hours = 0;
		$minutes = 0;
		$seconds = 0;
		
		while(!$this->getExit()){
			#print __CLASS__.'->'.__FUNCTION__.': '.$this->getExit().', '.$hours.', '.$minutes.', '.$seconds."\n";
			
			if($hours == 0 && $minutes == 0 && $seconds == 0){
				#$this->pingClosestNodes();
				$this->msgDbInit();
			}
			elseif($hours == 0 && $minutes == 1 && $seconds == 0){
				$this->pingClosestNodes();
			}
			if($minutes % 5 == 0 && $seconds == 0){
				print __CLASS__.'->'.__FUNCTION__.': save'."\n";
				$this->getIpcKernelConnection()->execAsync('save');
			}
			if($minutes % 15 == 0 && $seconds == 0){
				$this->pingClosestNodes();
			}
			
			$this->getIpcKernelConnection()->run();
			
			
			$seconds++;
			if($seconds >= 60){
				$seconds = 0;
				$minutes++;
				
				print __CLASS__.'->'.__FUNCTION__.': '.$this->getExit().', '.$hours.', '.$minutes.', '.$seconds."\n";
			}
			if($minutes >= 60){
				$minutes = 0;
				$hours++;
			}
			sleep(1);
			
		}
		
		$this->shutdown();
	}
	
	private function pingClosestNodes(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		$this->log->debug(__FUNCTION__);
		$table = $this->getIpcKernelConnection()->execSync('getTable');
		
		$nodes = $table->getNodesClosest(20);
		#ve($table);
		#ve($nodes);
		
		foreach($nodes as $nodeId => $node){
			if($node->getIp() && $node->getPort()){
				$this->getIpcKernelConnection()->execAsync('serverConnect',
					array($node->getIp(), $node->getPort(), false, true));
			}
		}
	}
	
	private function msgDbInit(){
		$this->log->debug(__FUNCTION__);
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->msgDb = $this->getIpcKernelConnection()->execSync('getMsgDb', array(), 10);
		$this->settings = $this->getIpcKernelConnection()->execSync('getSettings');
		$this->setTable($this->getIpcKernelConnection()->execSync('getTable'));
		
		#ve($this->table);
		
		#print __CLASS__.'->'.__FUNCTION__.': msgDb A '.(int)($this->msgDb===null)."\n";
		#ve($this->msgDb);
		
		try{
			$this->msgDbInitNodes();
			$this->msgDbSendAll();
		}
		catch(Exception $e){
			$this->log->debug(__FUNCTION__.': '.$e->getMessage());
			print __CLASS__.'->'.__FUNCTION__.': '.$e->getMessage()."\n";
		}
		
	}
	
	public function msgDbInitNodes(){
		$this->log->debug(__FUNCTION__);
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
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
				fwrite(STDOUT, __METHOD__.' find node: '.$msg->getId().' -> '.$msg->getDstNodeId()."\n");
				
				$node = new Node();
				$node->setIdHexStr($msg->getDstNodeId());
				$onode = $this->table->nodeFindInBuckets($node);
				if($onode && $onode->getSslKeyPub()){
					fwrite(STDOUT, __METHOD__.'     found node: '.$onode->getIdHexStr()."\n");
					
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
			#print __CLASS__.'->'.__FUNCTION__.': reset msgDb'."\n";
			$this->msgDb = $this->getIpcKernelConnection()->execSync('getMsgDb', array(), 10);
		}
	}
	
	public function msgDbSendAll(){
		$this->log->debug(__FUNCTION__);
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
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
		#print __CLASS__.'->'.__FUNCTION__.': unsent own'."\n";
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'O'
			){
				fwrite(STDOUT, __METHOD__.': unsent own '.$msg->getId()."\n");
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		// Send foreign unsent msgs.
		#print __CLASS__.'->'.__FUNCTION__.': unsent foreign'."\n";
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'U'
			){
				fwrite(STDOUT, __METHOD__.': unsent foreign '.$msg->getId()."\n");
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		// Relay all other msgs.
		#print __CLASS__.'->'.__FUNCTION__.': other'."\n";
		foreach($this->msgDb->getMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
				&& $msg->getStatus() == 'S'
			){
				fwrite(STDOUT, __METHOD__.': other '.$msg->getId()."\n");
				
				$processedMsgIds[] = $msg->getId();
				$processedMsgs[] = $msg;
			}
		}
		
		$processedMsgs = array_unique($processedMsgs);
		#print __CLASS__.'->'.__FUNCTION__.': processedMsgs: '.count($processedMsgs)."\n";
		
		foreach($processedMsgs as $msgId => $msg){
			#print __CLASS__.'->'.__FUNCTION__.': processedMsg A: '. $msg->getId() ."\n";
			
			$sentNodesC = count($msg->getSentNodes());
			$forwardCycles = $msg->getForwardCycles();
			
			if(
				$sentNodesC >= static::MSG_FORWARD_TO_NODES_MIN
					&& $forwardCycles >= static::MSG_FORWARD_CYCLES_MAX
				
				|| $sentNodesC >= static::MSG_FORWARD_TO_NODES_MAX
			){
				fwrite(STDOUT, __METHOD__.': set X: '.$msg->getId()."\n");
				
				$msg->setStatus('X');
				
				if($this->getIpcKernelConnection()){
					$this->getIpcKernelConnection()->execAsync('msgDbMsgSetStatusById', array($msg->getId(), 'X'));
				}
				
				unset($processedMsgs[$msgId]);
			}
			
			elseif( in_array($msg->getDstNodeId(), $msg->getSentNodes()) ){
				fwrite(STDOUT, __METHOD__.': set D: '.$msg->getId()."\n");
				
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
			fwrite(STDOUT, __METHOD__.' msg: '.$msg->getId().', '.$msg->getStatus().', '.$msg->getEncryptionMode()."\n");
			fwrite(STDOUT, __METHOD__.'      dst:   '.$msg->getDstNodeId()."\n");
			#fwrite(STDOUT, __METHOD__.'      relay: '.$msg->getRelayNodeId()."\n");
			
			$dstNode = new Node();
			$dstNode->setIdHexStr($msg->getDstNodeId());
			
			if(!isset($nodes[$dstNode->getIdHexStr()])){
				$nodes[$dstNode->getIdHexStr()] = $dstNode;
			}
			
			$onode = $this->table->nodeFindInBuckets($dstNode);
			if($onode && $onode->getIp() && $onode->getPort()){
				fwrite(STDOUT, __METHOD__.'      dst node found in table'."\n");
				
				$nodes[$onode->getIdHexStr()] = $onode;
				
				if(!isset($nodeIds[$onode->getIdHexStr()])){
					$nodeIds[$onode->getIdHexStr()] = array();
				}
				$nodeIds[$onode->getIdHexStr()][$msg->getId()] = $msg;
			}
			
			fwrite(STDOUT, __METHOD__.'      close nodes'."\n");
			$closestNodes = $this->table->nodeFindClosest($dstNode, static::MSG_FORWARD_TO_NODES_MAX);
			foreach($closestNodes as $nodeId => $node){
				if(
					$msg->getRelayNodeId() != $node->getIdHexStr()
					&& !in_array($node->getIdHexStr(), $msg->getSentNodes())
					&& $node->getIp() && $node->getPort()
				){
					fwrite(STDOUT, __METHOD__.'             n '.$node->getIdHexStr()."\n");
					
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
			fwrite(STDOUT, __METHOD__.' node: '.$node->getIdHexStr().', '.$node->getIpPort()."\n");
			
			$msgs = array_unique($msgs);
			$msgIds = array();
			
			foreach($msgs as $msgId => $msg){
				$direct = (int)($msg->getDstNodeId() == $node->getIdHexStr() && $node->getIp() && $node->getPort());
				$tmp = $msg->getId().', '.$msg->getStatus().', '.$msg->getEncryptionMode(); # TODO
				$tmp .= ', direct='.$direct; # TODO
				fwrite(STDOUT, __METHOD__.'      msg: '.$tmp."\n"); # TODO
				
				$updateMsgs[$msg->getId()] = $msg;
				$msgIds[] = $msg->getId();
			}
			
			if($msgs && $this->getIpcKernelConnection()){
				fwrite(STDOUT, __METHOD__.'      msgs: '.count($msgs)."\n"); # TODO
				
				$serverConnectArgs = array($node->getIp(), $node->getPort(), false, false, $msgIds);
				$this->getIpcKernelConnection()->execSync('serverConnect', $serverConnectArgs);
			}
		}
		
		foreach($updateMsgs as $msgId => $msg){
			fwrite(STDOUT, __METHOD__.' update msg: '.$msg->getId()."\n");
			if($this->getIpcKernelConnection()){
				$this->getIpcKernelConnection()->execAsync('msgDbMsgIncForwardCyclesById', array($msg->getId()));
			}
		}
		
		return $updateMsgs;
	}
	
	public function shutdown(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
	}
	
	public function ipcKernelShutdown(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		$this->setExit(1);
	}
	
}
