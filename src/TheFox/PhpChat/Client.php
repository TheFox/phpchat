<?php

namespace TheFox\PhpChat;

use Exception;
use RuntimeException;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Utilities\Hex;
use TheFox\Network\AbstractSocket;
use TheFox\Dht\Kademlia\Node;

class Client{
	
	const MSG_SEPARATOR = "\n";
	
	private $id = 0;
	private $status = array();
	
	private $server = null;
	private $socket = null;
	private $ip = '';
	private $port = 0;
	
	private $recvBufferTmp = '';
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->status['hasShutdown'] = false;
	}
	
	public function __destruct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function getStatus($name){
		if(array_key_exists($name, $this->status)){
			return $this->status[$name];
		}
		return null;
	}
	
	public function setStatus($name, $value){
		$this->status[$name] = $value;
	}
	
	public function setServer(Server $server){
		$this->server = $server;
	}
	
	public function getServer(){
		return $this->server;
	}
	
	public function setSocket(AbstractSocket $socket){
		$this->socket = $socket;
	}
	
	public function getSocket(){
		return $this->socket;
	}
	
	public function setIp($ip){
		$this->ip = $ip;
	}
	
	public function getIp(){
		if(!$this->ip){
			$this->setIpPort();
		}
		return $this->ip;
	}
	
	public function setPort($port){
		$this->port = $port;
	}
	
	public function getPort(){
		if(!$this->port){
			$this->setIpPort();
		}
		return $this->port;
	}
	
	public function setIpPort($ip = '', $port = 0){
		$this->getSocket()->getPeerName($ip, $port);
		$this->setIp($ip);
		$this->setPort($port);
	}
	
	public function getIpPort(){
		return $this->getIp().':'.$this->getPort();
	}
	
	public function setSslPrv($sslKeyPrvPath, $sslKeyPrvPass){
		$this->ssl = openssl_pkey_get_private(file_get_contents($sslKeyPrvPath), $sslKeyPrvPass);
	}
	
	public function getLocalNode(){
		if($this->getServer()){
			return $this->getServer()->getLocalNode();
		}
		return null;
	}
	
	public function getSettings(){
		if($this->getServer()){
			return $this->getServer()->getSettings();
		}
		
		return null;
	}
	
	private function getLog(){
		if($this->getServer()){
			return $this->getServer()->getLog();
		}
		
		return null;
	}
	
	private function log($level, $msg){
		if($this->getLog()){
			if(method_exists($this->getLog(), $level)){
				$this->getLog()->$level($msg);
			}
		}
	}
	
	public function dataRecv(){
		$data = $this->getSocket()->read();
		
		$separatorPos = strpos($data, static::MSG_SEPARATOR);
		if($separatorPos === false){
			$this->recvBufferTmp .= $data;
			$data = '';
		}
		else{
			$msg = $this->recvBufferTmp.substr($data, 0, $separatorPos);
			
			$this->msgHandle($msg);
		}
	}
	
	private function msgHandle($msgRaw){
		$msgRaw = base64_decode($msgRaw);
		$msg = json_decode($msgRaw, true);
		
		print __CLASS__.'->'.__FUNCTION__.': '.$msgRaw."\n";
		#ve($msg);
		
		$msgName = $msg['name'];
		$msgData = array();
		if(array_key_exists('data', $msg)){
			$msgData = $msg['data'];
		}
		
		if($msgName == 'hello'){
			if(array_key_exists('ip', $msgData)){
				if($msgData['ip'] != '127.0.0.1' && $this->getSettings()){
					$this->getSettings()->data['node']['ipPub'] = $msgData['ip'];
					$this->getSettings()->setDataChanged(true);
				}
			}
		}
		elseif($msgName == 'quit'){
			$this->shutdown();
		}
	}
	
	private function msgCreate($name, $data){
		$json = array(
			'name' => $name,
			'data' => $data,
		);
		return json_encode($json);
	}
	
	private function dataSend($msg){
		$this->getSocket()->write($msg.static::MSG_SEPARATOR);
	}
	
	private function sendId(){
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$data = array(
			'id'   => $this->getLocalNode()->getIdHexStr(),
			'port' => $this->getLocalNode()->getPort(),
		);
		$this->dataSend($this->msgCreate('id', $data));
	}
	
	public function shutdown(){
		if(!$this->getStatus('hasShutdown')){
			$this->setStatus('hasShutdown', true);
			
			$this->getSocket()->shutdown();
			$this->getSocket()->close();
			
			if($this->ssl){
				openssl_free_key($this->ssl);
			}
		}
	}
	
}
