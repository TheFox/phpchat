<?php

namespace TheFox\PhpChat;

use Exception;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Utilities\Hex;
use TheFox\Network\AbstractSocket;

class Client{
	
	const MSG_SEPARATOR = "\n";
	
	private $id = 0;
	private $status = array();
	
	private $socket = null;
	private $ip = '';
	private $port = 0;
	
	private $recvBufferTmp = '';
	
	public function __construct(){}
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function getId(){
		return $this->id;
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
		$msg = json_decode($msg, true);
		
		print __CLASS__.'->'.__FUNCTION__.': '.$msgRaw."\n";
		ve($msg);
		
		if($msg['name'] == 'hello'){
			
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
		$data = array(
			'id' => 'x',
			'port' => 25000,
		);
		$this->dataSend('id', $data);
	}
	
}
