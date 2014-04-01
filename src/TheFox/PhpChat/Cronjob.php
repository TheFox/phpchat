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
	const MSG_FORWARD_TO_NODES = 8;
	const MSG_FORWARD_CYCLES_MAX = 10;
	
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
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		$this->log->debug(__FUNCTION__);
		$table = $this->getIpcKernelConnection()->execSync('getTable');
		
		$nodes = $table->getNodesClosest(20);
		#ve($table);
		#ve($nodes);
		
		foreach($nodes as $nodeId => $node){
			if($node->getIp() && $node->getPort()){
				$this->getIpcKernelConnection()->execAsync('serverConnect', array($node->getIp(), $node->getPort(), false, true));
			}
		}
	}
	
	private function msgDbInit(){
		$this->log->debug(__FUNCTION__);
		
		$this->msgDb = $this->getIpcKernelConnection()->execSync('getMsgDb');
		$this->settings = $this->getIpcKernelConnection()->execSync('getSettings');
		$this->table = $this->getIpcKernelConnection()->execSync('getTable');
		$this->localNode = $this->table->getLocalNode();
		
		#ve($this->table);
		
		$this->msgDbInitNodes();
		$this->msgDbSendAll();
	}
	
	private function msgDbInitNodes(){
		$this->log->debug(__FUNCTION__);
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				$msg->getDstNodeId()
				&& $msg->getSrcNodeId() == $this->localNode->getIdHexStr()
				&& $msg->getEncryptionMode() == 'S'
			){
				#print __CLASS__.'->'.__FUNCTION__.': find node '.$msg->getId()."\n";
				
				$node = new Node();
				$node->setIdHexStr($msg->getDstNodeId());
				
				$onode = $this->table->nodeEnclose($node);
				
				if($onode->getSslKeyPub()){
					#print __CLASS__.'->'.__FUNCTION__.': found node'."\n";
					#print __CLASS__.'->'.__FUNCTION__.': pub: '.$this->settings->data['node']['sslKeyPubPath']."\n";
					
					
					$msg->setSrcSslKeyPub($this->localNode->getSslKeyPub());
					$msg->setDstSslPubKey($this->localNode->getSslKeyPub());
					$msg->setSslKeyPrvPath($this->settings->data['node']['sslKeyPrvPath'], $this->settings->data['node']['sslKeyPrvPass']);
					#$text = $msg->decrypt();
					
					$msg->setText($msg->decrypt());
					$msg->setEncryptionMode('D');
					$msg->setDstSslPubKey($onode->getSslKeyPub());
					$msg->encrypt();
					
					$this->getIpcKernelConnection()->execAsync('msgDbMsgUpdate', array($msg));
					
					#ve($text);
					
				}
				
			}
		}
		
		$this->msgDb = $this->getIpcKernelConnection()->execSync('getMsgDb');
		
		#print __CLASS__.'->'.__FUNCTION__.': done'."\n";
	}
	
	private function msgDbSendAll(){
		$this->log->debug(__FUNCTION__);
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$processedMsgIds = array();
		
		// Send own msgs.
		print __CLASS__.'->'.__FUNCTION__.': own'."\n"; # TODO
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getSrcNodeId() == $this->localNode->getIdHexStr()
				&& $msg->getEncryptionMode() == 'D'
			){
				print __CLASS__.'->'.__FUNCTION__.': own '.$msg->getId()."\n"; # TODO
				
				$processedMsgIds[] = $msg->getId();
				$this->msgDbSendMsg($msg);
			}
		}
		
		// Send foreign unsent msgs.
		print __CLASS__.'->'.__FUNCTION__.': unsent'."\n"; # TODO
		foreach($this->msgDb->getUnsentMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getSrcNodeId() != $this->localNode->getIdHexStr()
				&& $msg->getEncryptionMode() == 'D'
			){
				print __CLASS__.'->'.__FUNCTION__.': unsent '.$msg->getId()."\n"; # TODO
				
				$processedMsgIds[] = $msg->getId();
				$this->msgDbSendMsg($msg);
			}
		}
		
		// Relay all other msgs.
		print __CLASS__.'->'.__FUNCTION__.': other'."\n"; # TODO
		foreach($this->msgDb->getMsgs() as $msgId => $msg){
			if(
				!in_array($msg->getId(), $processedMsgIds)
				&& $msg->getDstNodeId()
				&& $msg->getEncryptionMode() == 'D'
			){
				print __CLASS__.'->'.__FUNCTION__.': other '.$msg->getId()."\n"; # TODO
				
				$processedMsgIds[] = $msg->getId();
				$this->msgDbSendMsg($msg);
			}
		}
		
		print __CLASS__.'->'.__FUNCTION__.': done'."\n"; # TODO
	}
	
	private function msgDbSendMsg(Msg $msg){
		$this->log->debug(__FUNCTION__);
		
		$node = new Node();
		$node->setIdHexStr($msg->getDstNodeId());
		$node = $this->table->nodeEnclose($node);
		
		$nodes = $this->table->nodeFindClosest($node);
		
		array_unshift($nodes, $node);
		
		if(count($msg->getSentNodes()) < static::MSG_FORWARD_TO_NODES && $msg->getForwardCycles() < static::MSG_FORWARD_CYCLES_MAX){
			foreach($nodes as $nodeId => $node){
				if($node->getIp() && $node->getPort() && !in_array($node->getIdHexStr(), $msg->getSentNodes())){
					$this->log->debug(__FUNCTION__.': '.$node->getIp().':'.$node->getPort().', '.$msg->getId());
					
					$msg->incForwardCycles();
					
					$this->getIpcKernelConnection()->execAsync('msgDbMsgUpdate', array($msg));
					
					$serverConnectArgs = array($node->getIp(), $node->getPort(), false, false, $msg->getId());
					$rv = $this->getIpcKernelConnection()->execSync('serverConnect', $serverConnectArgs);
					
					#print __CLASS__.'->'.__FUNCTION__.': node: '.$node->getIdHexStr().', '. (int)$rv .''."\n";
				}
				
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
