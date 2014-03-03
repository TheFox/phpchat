<?php

namespace TheFox\Ipc;

abstract class AbstractHandler{
	
	private $ip;
	private $port;
	private $socket;
	
	private $isConnected = null;
	private $isListening = null;
	private $hasData = false;
	
	private $clientsId = 0;
	private $clients = array();
	private $clientsBySockets = array();
	
	private $recvBufferId = 0;
	private $recvBuffer = array();
	
	
	abstract public function connect();
	abstract public function listen();
	abstract public function run();
	abstract public function socketDataSend($socket, $data);
	abstract public function socketDataRecv($socket);
	
	
	public function send($data, $clientId = null){
		print __CLASS__.'->'.__FUNCTION__.': "'.$data.'"'."\n";
		
		if($this->isListening()){ // Server
			if($clientId !== null && isset($this->clients[$clientId])){
				$client = $this->clients[$clientId];
				$this->socketDataSend($client['socket'], $data.$this->getSendDelimiter());
			}
		}
		elseif($this->isConnected()){ // Client
			$this->socketDataSend($this->getSocket(), $data.$this->getSendDelimiter());
		}
	}
	
	public function sendId($clientId = null){
		$this->send('ID', $clientId);
	}
	
	public function sendIdOk($clientId = null){
		$this->send('ID_OK', $clientId);
	}
	
	public function sendFunctionExec($name, $args = array(), $rid = 0, $clientId = null){
		$argsOut = array();
		foreach($args as $arg){
			$argsOut[] = serialize($arg);
		}
		
		$json = array(
			'name' => $name,
			'rid' => $rid,
			'args' => $argsOut,
		);
		$jsonStr = json_encode($json);
		
		$this->send('FUNCTION_EXEC '.$jsonStr, $clientId);
	}
	
	public function sendFunctionRetn($value, $rid = 0, $clientId = null){
		$json = array(
			'value' => serialize($value),
			'rid' => $rid,
		);
		$jsonStr = json_encode($json);
		
		$this->send('FUNCTION_RETN '.$jsonStr, $clientId);
	}
	
	public function recv($socket, $data){
		$dataLen = strlen($data);
		print __CLASS__.'->'.__FUNCTION__.': '.$dataLen.''."\n";
		
		if($this->isListening()){
			$client = $this->clientFindBySocket($socket);
			$this->clientHandleRevcData($client, $data);
		}
		elseif($this->isConnected()){
			$this->hasData(true);
			
			do{
				if(!isset($this->recvBuffer[$this->recvBufferId])){
					$this->recvBuffer[$this->recvBufferId] = '';
				}
				
				$delimiterPos = strpos($data, $this->getSendDelimiter());
				if($delimiterPos === false){
					#print "data1.1: '$data'\n";
					$this->recvBuffer[$this->recvBufferId] .= $data;
					$data = '';
				}
				else{
					$msg = substr($data, 0, $delimiterPos);
					#print "data1.2: '$msg'\n";
					
					$this->recvBuffer[$this->recvBufferId] = $msg;
					$this->recvBufferId++;
					
					$data = substr($data, $delimiterPos + 1);
				}
				
			}while($data);
		}
	}
	
	public function recvBuffer(){
		$recvBuffer = array();
		
		if($this->isListening()){
			foreach($this->clients as $clientId => $client){
				if($client['recvBuffer']){
					$recvBuffer[] = array(
						'id' => $client['id'],
						'recvBuffer' => $client['recvBuffer'],
					);
					
					$this->clients[$client['id']]['recvBufferId'] = 0;
					$this->clients[$client['id']]['recvBuffer'] = array();
				}
			}
		}
		elseif($this->isConnected()){
			$recvBuffer = $this->recvBuffer;
			
			$this->recvBufferId = 0;
			$this->recvBuffer = array();
		}
		
		$this->hasData(false);
		
		return $recvBuffer;
	}
	
	public function setIp($ip){
		$this->ip = $ip;
	}
	
	public function getIp(){
		return $this->ip;
	}
	
	public function setPort($port){
		$this->port = (int)$port;
	}
	
	public function getPort(){
		return $this->port;
	}
	
	public function setSocket($socket){
		$this->socket = $socket;
	}
	
	public function getSocket(){
		return $this->socket;
	}
	
	public function isConnected($isConnected = null){
		if($isConnected !== null){
			$this->isConnected = $isConnected;
		}
		return $this->isConnected;
	}
	
	public function isListening($isListening = null){
		if($isListening !== null){
			$this->isListening = $isListening;
		}
		return $this->isListening;
	}
	
	public function hasData($hasData = null){
		if($hasData !== null){
			$this->hasData = $hasData;
		}
		#print __CLASS__.'->'.__FUNCTION__.': '.(int)$this->hasData."\n";
		return $this->hasData;
	}
	
	public function getSendDelimiter(){
		return "\n";
	}
	
	public function getClients(){
		return $this->clients;
	}
	
	public function clientAdd($socket){
		$this->clientsId++;
		$this->clients[$this->clientsId] = array(
			'id' => $this->clientsId,
			'socket' => $socket,
			'recvBufferId' => 0,
			'recvBuffer' => array(),
			#'sendBufferId' => 0,
			#'sendBuffer' => array(),
		);
		
		return $this->clients[$this->clientsId];
	}
	
	public function clientHandleRevcData($client, $data){
		$dataLen = strlen($data);
		if($dataLen){
			$this->hasData(true);
			
			do{
				$clientId = $client['id'];
				$recvBufferId = $this->clients[$clientId]['recvBufferId'];
				
				if(!isset($client['recvBuffer'][$client['recvBufferId']])){
					$this->clients[$clientId]['recvBuffer'][$recvBufferId] = '';
				}
				
				$delimiterPos = strpos($data, $this->getSendDelimiter());
				if($delimiterPos === false){
					#print "data2.1: ".$clientId.", '$data'\n";
					
					$this->clients[$clientId]['recvBuffer'][$recvBufferId] .= $data;
					$data = '';
				}
				else{
					$msg = substr($data, 0, $delimiterPos);
					#print "data2.2: ".$clientId.", '$msg'\n";
					
					$this->clients[$clientId]['recvBuffer'][$recvBufferId] = $msg;
					$this->clients[$clientId]['recvBufferId']++;
					
					$data = substr($data, $delimiterPos + 1);
				}
				
			}while($data);
		}
	}
	
	public function clientFindBySocket($socket){
		foreach($this->clients as $clientId => $client){
			if($client['socket'] == $socket){
				return $client;
			}
		}
		
		return null;
	}
	
	public function clientRemove($client){
		unset($this->clients[$client['id']]);
	}
	
}
