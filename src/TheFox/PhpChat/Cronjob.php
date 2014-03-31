<?php

namespace TheFox\PhpChat;

use RuntimeException;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
use TheFox\Ipc\ConnectionClient;
use TheFox\Ipc\StreamHandler as IpcStreamHandler;
use TheFox\Dht\Kademlia\Node;

class Cronjob extends Thread{
	
	#const LOOP_USLEEP = 100000;
	
	private $log;
	private $ipcKernelConnection = null;
	private $msgDb;
	private $table;
	private $localNode;
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->log = new Logger('cronjob');
		$this->log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::ERROR));
		$this->log->pushHandler(new LoggerStreamHandler('log/cronjob.log', Logger::DEBUG));
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
			
			if($seconds == 0){
				$this->msgDbInit();
			}
			if($hours == 0 && $minutes == 1 && $seconds == 0){
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
			
			#usleep(static::LOOP_USLEEP);
			sleep(1);
			
			#break; # TODO
		}
		
		$this->shutdown();
	}
	
	private function pingClosestNodes(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		$table = $this->getIpcKernelConnection()->execSync('getTable');
		
		$nodes = $table->getNodesClosest(20);
		#ve($table);
		#ve($nodes);
		
		foreach($nodes as $nodeId => $node){
			#print __CLASS__.'->'.__FUNCTION__.': ping: '.$node->getIpPort()."\n";
			
			$this->getIpcKernelConnection()->execAsync('serverConnect', array($node->getIp(), $node->getPort(), false, true));
		}
	}
	
	private function msgDbInit(){
		$this->msgDb = $this->getIpcKernelConnection()->execSync('getMsgDb');
		$this->table = $this->getIpcKernelConnection()->execSync('getTable');
		$this->localNode = $this->table->getLocalNode();
		
		#ve($this->table);
		
		$this->msgDbInitNodes();
		$this->msgDbSendAll();
	}
	
	private function msgDbInitNodes(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		#ve($this->msgDb);
		
		/*
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if($msg->getDstNodeId() && $msg->getSrcNodeId() == $this->localNode->getIdHexStr()){
				$node = new Node();
				$node->setIdHexStr($msg->getDstNodeId());
				
				$onode = $this->table->nodeEnclose($node);
				if($node == $onode){
					// New node.
					print __CLASS__.'->'.__FUNCTION__.': new node'."\n";
					$this->getIpcKernelConnection()->execAsync('tableNodeEnclose', array($node));
				}
			}
		}
		
		$this->table = $this->getIpcKernelConnection()->execSync('getTable');
		*/
		
		#print __CLASS__.'->'.__FUNCTION__.': done'."\n";
	}
	
	private function msgDbSendAll(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$processedMsgIds = array();
		
		// Send own msgs.
		#print __CLASS__.'->'.__FUNCTION__.': own'."\n";
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(!in_array($msg->getId(), $processedMsgIds) && $msg->getDstNodeId() && $msg->getSrcNodeId() == $this->localNode->getIdHexStr()){
				#print __CLASS__.'->'.__FUNCTION__.': own '.$msg->getId()."\n";
				
				$processedMsgIds[] = $msg->getId();
				$this->msgDbSendMsg($msg);
			}
		}
		
		// Send unsent msgs.
		#print __CLASS__.'->'.__FUNCTION__.': unsent'."\n";
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(!in_array($msg->getId(), $processedMsgIds) && $msg->getDstNodeId() && $msg->getSrcNodeId() != $this->localNode->getIdHexStr()){
				#print __CLASS__.'->'.__FUNCTION__.': unsent '.$msg->getId()."\n";
				
				$processedMsgIds[] = $msg->getId();
				$this->msgDbSendMsg($msg);
			}
		}
		
		// Relay all other msgs.
		#print __CLASS__.'->'.__FUNCTION__.': other'."\n";
		foreach($this->msgDb->getMsgs() as $msgId => $msg){
			if(!in_array($msg->getId(), $processedMsgIds) && $msg->getDstNodeId()){
				#print __CLASS__.'->'.__FUNCTION__.': other '.$msg->getId()."\n";
				
				$processedMsgIds[] = $msg->getId();
				$this->msgDbSendMsg($msg);
			}
		}
		
	}
	
	private function msgDbSendMsg(Msg $msg){
		#ve($msg);
		
		$node = new Node();
		$node->setIdHexStr($msg->getDstNodeId());
		
		$node = $this->table->nodeEnclose($node);
		#ve($node);
		
		$nodes = $this->table->nodeFindClosest($node);
		#ve($nodes);
		
		array_unshift($nodes, $node);
		
		
		foreach($nodes as $nodeId => $node){
			#ve($node);
			
			#print __CLASS__.'->'.__FUNCTION__.': node: '.$node->getIdHexStr().', "'.$node->getIp().'", "'.$node->getPort().'"'."\n";
			
			if($node->getIp() && $node->getPort()){
				$rv = $this->getIpcKernelConnection()->execSync('serverConnect', array($node->getIp(), $node->getPort(), false, false, $msg));
				
				#print __CLASS__.'->'.__FUNCTION__.': node: '.$node->getIdHexStr().', '. (int)$rv .''."\n";
			}
			
		}
		
	}
	
	public function shutdown(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
	}
	
	public function ipcKernelShutdown(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		$this->setExit(1);
	}
	
}
