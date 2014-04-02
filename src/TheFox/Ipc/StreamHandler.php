<?php

namespace TheFox\Ipc;

use RuntimeException;
use Closure;

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
		
		$handle = @stream_socket_client('tcp://'.$this->getIp().':'.$this->getPort(), $errno, $errstr, 2);
		if($handle !== false){
			$this->setHandle($handle);
			$this->isConnected(true);
			return true;
		}
		else{
			throw new RuntimeException($errstr, $errno);
		}
	}
	
	public function listen(){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		
		$handle = @stream_socket_server('tcp://'.$this->getIp().':'.$this->getPort(), $errno, $errstr);
		if($handle !== false){
			$this->setHandle($handle);
			$this->isListening(true);
			return true;
		}
		else{
			throw new RuntimeException($errstr, $errno);
		}
	}
	
	public function run(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$readHandles = array();
		$writeHandles = null; $exceptHandles = null;
		
		if($this->isListening()){
			$readHandles[] = $this->getHandle();
			foreach($this->getClients() as $client){
				$readHandles[] = $client['handle'];
			}
			
			#print __CLASS__.'->'.__FUNCTION__.': isListening ('.count($readHandles).')'."\n";
		}
		elseif($this->isConnected()){
			#print __CLASS__.'->'.__FUNCTION__.': isConnected'."\n";
			
			$readHandles[] = $this->getHandle();
		}
		
		if(count($readHandles)){
			$handlesChangedNum = stream_select($readHandles, $writeHandles, $exceptHandles, 0);
			#print __CLASS__.'->'.__FUNCTION__.': handlesChangedNum ('.$handlesChangedNum.')'."\n";
			
			if($handlesChangedNum){
				foreach($readHandles as $readableHandle){
					if($this->isListening() && $readableHandle == $this->getHandle()){
						// Server
						#print __CLASS__.'->'.__FUNCTION__.': accept'."\n";
						$handle = @stream_socket_accept($this->getHandle(), 2);
						$client = $this->clientAdd($handle);
						$this->execOnClientConnectFunction($client);
					}
					else{
						// Client
						if(feof($readableHandle)){
							#print __CLASS__.'->'.__FUNCTION__.': feof'."\n";
							if($this->isListening()){
								$client = $this->clientGetByHandle($readableHandle);
								if($client){
									stream_socket_shutdown($client['handle'], STREAM_SHUT_RDWR);
									$this->clientRemove($client);
								}
							}
							else{
								stream_socket_shutdown($this->getHandle(), STREAM_SHUT_RDWR);
								$this->isConnected(false);
							}
						}
						else{
							#print __CLASS__.'->'.__FUNCTION__.': recvfrom'."\n";
							$this->handleDataRecv($readableHandle);
						}
						
					}
				}
			}
		}
		
	}
	
	public function handleDataSend($handle, $data){
		#print __CLASS__.'->'.__FUNCTION__.': '.strlen($data).''."\n";
		$rv = @stream_socket_sendto($handle, $data);
		
		#print __CLASS__.'->'.__FUNCTION__.': '.(int)$rv.', "'.substr($data, 0, -1).'"'."\n";
	}
	
	public function handleDataRecv($handle){
		#print __CLASS__.'->'.__FUNCTION__.': '.(int)($handle === null)."\n";
		
		$data = stream_socket_recvfrom($handle, 4096);
		$this->recv($handle, $data);
		
		#print __CLASS__.'->'.__FUNCTION__.': '.strlen($data).''."\n";
	}
	
}
