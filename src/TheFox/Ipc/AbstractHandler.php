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
		if($this->isListening()){
			$client = $this->client[$clientId];
			$this->socketDataSend($client['socket'], $data.$this->getSendDelimiter());
		}
		elseif($this->isConnected()){
			if($clientId === null){
				$this->socketDataSend($this->getSocket(), $data);
			}
		}
	}
	
	public function recv($socket, $data){
		$dataLen = strlen($data);
		print __CLASS__.'->'.__FUNCTION__.': data: '.(int)($data === false).', '.(int)feof($socket).', '.$dataLen.' "'.$data.'"'."\n";
		
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
					print "data1: '$data'\n";
					$this->recvBuffer[$this->recvBufferId] .= $data;
					$data = '';
				}
				else{
					$msg = substr($data, 0, $delimiterPos);
					print "data1: '$msg'\n";
					
					$this->recvBuffer[$this->recvBufferId] = $data;
					$this->recvBufferId++;
					
					$data = substr($data, $delimiterPos + 1);
				}
				
			}while($data);
		}
	}
	
	public function recvBuffer(){
		$recvBuffer = array();
		
		if($this->isListening()){
			#print __CLASS__.'->'.__FUNCTION__.': isListening'."\n";
			
			foreach($this->clients as $clientId => $client){
				$recvBuffer[] = array(
					'clientId' => $clientId,
					'recvBuffer' => $client['recvBuffer'],
				);
				
				$client['recvBufferId'] = 0;
				$client['recvBuffer'] = array();
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
	}
	
	public function clientHandleRevcData($client, $data){
		$dataLen = strlen($data);
		if($dataLen){
			$this->hasData(true);
			
			do{
				if(!isset($client['recvBuffer'][$client['recvBufferId']])){
					$client['recvBuffer'][$client['recvBufferId']] = '';
				}
				
				$delimiterPos = strpos($data, $this->getSendDelimiter());
				if($delimiterPos === false){
					print "data2.1: '$data'\n";
					
					$client['recvBuffer'][$client['recvBufferId']] .= $data;
					$data = '';
					
					ve($client['recvBuffer']);
				}
				else{
					$msg = substr($data, 0, $delimiterPos);
					print "data2.2: '$msg'\n";
					
					$client['recvBuffer'][$client['recvBufferId']] = $data;
					$client['recvBufferId']++;
					
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
