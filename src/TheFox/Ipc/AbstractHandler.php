<?php

namespace TheFox\Ipc;

use Closure;

abstract class AbstractHandler{
	
	private $ip;
	private $port;
	private $handle;
	
	private $isConnected = null;
	private $isListening = null;
	private $hasData = false;
	
	private $clientsId = 0;
	private $clients = array();
	private $onClientConnectFunction = null;
	
	private $recvBufferId = 0;
	private $recvBuffer = array();
	private $recvBufferTmp = '';
	
	
	abstract public function connect();
	abstract public function listen();
	abstract public function run();
	abstract public function handleDataSend($handle, $data);
	abstract public function handleDataRecv($handle);
	
	
	public function send($data, $clientId = null){
		#print __CLASS__.'->'.__FUNCTION__.': all='.(int)($clientId === null).', "'.$data.'"'."\n";
		
		if($this->isListening()){ // is Server
			if($clientId !== null && isset($this->clients[$clientId])){
				// Send to a certain client.
				$client = $this->clients[$clientId];
				$this->handleDataSend($client['handle'], base64_encode($data).$this->getSendSeparator());
			}
			else{
				// Send to all clients.
				#print __CLASS__.'->'.__FUNCTION__.': send to all, "'.$data.'"'."\n";
				foreach($this->clients as $clientId => $client){
					#print __CLASS__.'->'.__FUNCTION__.': send to '.$client['id'].', "'.$data.'"'."\n";
					$this->handleDataSend($client['handle'], base64_encode($data).$this->getSendSeparator());
				}
			}
		}
		elseif($this->isConnected()){ // is Client
			$this->handleDataSend($this->getHandle(), base64_encode($data).$this->getSendSeparator());
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
	
	public function recv($handle, $data){
		$dataLen = strlen($data);
		print __CLASS__.'->'.__FUNCTION__.': '.$dataLen.', '.(int)($handle === null)."\n";
		
		if($this->isListening()){ // is Server
			$client = $this->clientGetByHandle($handle);
			$this->clientHandleRevcData($client, $data);
		}
		elseif($this->isConnected()){ // is Client
			$this->hasData(true);
			
			do{
				$separatorPos = strpos($data, $this->getSendSeparator());
				if($separatorPos === false){
					#print "data1.1: '$data'\n";
					#$this->recvBuffer[$this->recvBufferId] .= $data;
					$this->recvBufferTmp .= $data;
					$data = '';
				}
				else{
					$msg = $this->recvBufferTmp.substr($data, 0, $separatorPos);
					$this->recvBufferTmp = '';
					#print "data1.2: '$msg'\n";
					
					$this->recvBufferId++;
					$this->recvBuffer[$this->recvBufferId] = base64_decode($msg);
					
					$data = substr($data, $separatorPos + 1);
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
	
	public function setHandle($handle){
		$this->handle = $handle;
	}
	
	public function getHandle(){
		return $this->handle;
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
	
	private function getSendSeparator(){
		return "\n";
	}
	
	public function getClients(){
		return $this->clients;
	}
	
	public function getClientsNum(){
		return count($this->clients);
	}
	
	public function clientAdd($handle){
		$this->clientsId++;
		$this->clients[$this->clientsId] = array(
			'id' => $this->clientsId,
			'handle' => $handle,
			'recvBufferId' => 0,
			'recvBuffer' => array(),
			'recvBufferTmp' => '',
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
				
				$separatorPos = strpos($data, $this->getSendSeparator());
				if($separatorPos === false){
					#print "data2.1: ".$clientId.", '$data'\n";
					
					$this->clients[$clientId]['recvBufferTmp'] .= $data;
					$data = '';
				}
				else{
					$msg = $this->clients[$clientId]['recvBufferTmp'].substr($data, 0, $separatorPos);
					$this->clients[$clientId]['recvBufferTmp'] = '';
					#print "data2.2: ".$clientId.", '$msg'\n";
					
					$this->clients[$clientId]['recvBufferId']++;
					$this->clients[$clientId]['recvBuffer'][$this->clients[$clientId]['recvBufferId']] = base64_decode($msg);
					
					$data = substr($data, $separatorPos + 1);
				}
				
			}while($data);
		}
	}
	
	public function clientGetByHandle($handle){
		foreach($this->clients as $clientId => $client){
			if($client['handle'] == $handle){
				return $client;
			}
		}
		
		return null;
	}
	
	public function clientRemove($client){
		unset($this->clients[$client['id']]);
	}
	
	public function setOnClientConnectFunction(Closure $onClientConnectFunction){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->onClientConnectFunction = $onClientConnectFunction;
	}
	
	public function execOnClientConnectFunction($client){
		if($this->onClientConnectFunction){
			$func = $this->onClientConnectFunction;
			$func($client);
		}
	}
	
}
