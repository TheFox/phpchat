<?php

namespace TheFox\Ipc;

use Exception;

class StreamHandler extends AbstractHandler{
	
	public function __construct($ip = '', $port = 0){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		
		if($ip && $port){
			$this->setIp($ip);
			$this->setPort($port);
		}
	}
	
	public function connect(){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		
		$socket = @stream_socket_client('tcp://'.$this->getIp().':'.$this->getPort(), $errno, $errstr, 2);
		$this->setSocket($socket);
		
		if($socket !== false){
			#print __CLASS__.'->'.__FUNCTION__.': ok'."\n";
			
			$this->isConnected(true);
			return true;
		}
		else{
			#print __CLASS__.'->'.__FUNCTION__.': '.$errno.', '.$errstr."\n";
			return false;
		}
		
	}
	
	public function listen(){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		
		$socket = @stream_socket_server('tcp://'.$this->getIp().':'.$this->getPort(), $errno, $errstr);
		$this->setSocket($socket);
		
		if($socket){
			#print __CLASS__.'->'.__FUNCTION__.': ok '.$this->getSocket()."\n";
			
			$this->isListening(true);
			return true;
		}
		else{
			#print __CLASS__.'->'.__FUNCTION__.': '.$errno.', '.$errstr."\n";
			#return false;
			throw new Exception($errstr, $errno);
		}
	}
	
	public function run(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$readSockets = array();
		$writeSockets = null;
		$exceptSockets = null;
		
		if($this->isListening()){
			$readSockets[] = $this->getSocket();
			foreach($this->getClients() as $client){
				$readSockets[] = $client['socket'];
			}
		}
		elseif($this->isConnected()){
			#print __CLASS__.'->'.__FUNCTION__.': isConnected'."\n";
			
			$readSockets[] = $this->getSocket();
		}
		
		if(count($readSockets)){
			$socketsChangedNum = stream_select($readSockets, $writeSockets, $exceptSockets, 0);
			if($socketsChangedNum){
				foreach($readSockets as $socket){
					if($this->isListening() && $socket == $this->getSocket()){
						// Server
						#print __CLASS__.'->'.__FUNCTION__.': accept'."\n";
						$socket = @stream_socket_accept($this->getSocket(), 2);
						$client = $this->clientAdd($socket);
						
						$this->sendId($client['id']);
					}
					else{
						// Client
						if(feof($socket)){
							#print __CLASS__.'->'.__FUNCTION__.': feof'."\n";
							if($this->isListening()){
								$client = $this->clientFindBySocket($socket);
								stream_socket_shutdown($client['socket'], STREAM_SHUT_RDWR);
								$this->clientRemove($client);
							}
							else{
								stream_socket_shutdown($this->getSocket(), STREAM_SHUT_RDWR);
								$this->isConnected(false);
							}
						}
						else{
							#print __CLASS__.'->'.__FUNCTION__.': recvfrom'."\n";
							$this->socketDataRecv($socket);
						}
						
					}
				}
			}
		}
		
	}
	
	public function socketDataSend($socket, $data){
		$rv = stream_socket_sendto($socket, $data);
		
		#print __CLASS__.'->'.__FUNCTION__.': '.$rv.', "'.substr($data, 0, -1).'"'."\n";
	}
	
	public function socketDataRecv($socket){
		$data = stream_socket_recvfrom($socket, 1500);
		$this->recv($socket, $data);
		
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
	}
	
}
