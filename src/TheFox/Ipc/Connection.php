<?php

namespace TheFox\Ipc;

use Exception;
use OutOfBoundsException;

class Connection{
	
	private $isServer = false;
	private $handler = null;
	private $functions = array();
	private $execId = 0;
	
	public function __construct(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
	}
	
	public function isServer($isServer = null){
		if($isServer !== null){
			$this->isServer = $isServer;
		}
		
		return $this->isServer;
	}
	
	public function setHandler(AbstractHandler $handler){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->handler = $handler;
	}
	
	public function functionAdd($name, $objc = null, $func = null){
		if($objc !== null && $func === null){
			$func = $objc;
			$objc = null;
		}
		
		$this->functions[$name] = array(
			'name' => $name,
			'objc' => $objc,
			'func' => $func,
		);
	}
	
	public function functionExec($name, $args = array()){
		if(!isset($this->functions[$name])){
			throw new OutOfBoundsException('Function "'.$name.'" not defined.');
		}
		
		$function = $this->functions[$name];
		#ve($function);
		array_unshift($args, $this);
		#ve($args);
		
		if(is_object($function['objc'])){
			if(is_string($function['func'])){
				print __CLASS__.'->'.__FUNCTION__.': exec '. get_class($function['objc']) .'->'.$function['func'].'()'."\n";
				return call_user_func_array(array($function['objc'], $function['func']), $args);
			}
		}
		elseif(is_string($function['func'])){
			print __CLASS__.'->'.__FUNCTION__.': exec '.$function['func'].'()'."\n";
			return call_user_func_array($function['func'], $args);
		}
		elseif($function['func'] === null){
			print __CLASS__.'->'.__FUNCTION__.': exec '.$name.''."\n";
			return call_user_func_array($name, $args);
		}
		else{
			print __CLASS__.'->'.__FUNCTION__.': exec anon '.$name.''."\n";
			return call_user_func_array($function['func'], $args);
		}
	}
	
	public function exec($name, $args = array()){
		$this->execId++;
		$this->handler->sendFunctionExec($name, $args, $this->execId);
	}
	
	public function connect(){
		if($this->handler === null){
			throw new Exception('Handler not set. Use setHandler().');
		}
		
		if($this->isServer()){
			try{
				$this->handler->listen();
				
				return true;
			}
			catch(Exception $e){
				print __CLASS__.'->'.__FUNCTION__.': '.$e->getMessage()."\n";
				
				return false;
			}
		}
		else{
			if($rv = $this->handler->connect()){
				$this->handler->send('ID');
			}
			return $rv;
		}
	}
	
	private function msgHandle($msg, $clientId = null){
		print __CLASS__.'->'.__FUNCTION__.': "'.$msg.'"'."\n";
		
		if($msg == 'ID'){
			$this->handler->sendIdOk($clientId);
		}
		elseif(substr($msg, 0, 14) == 'FUNCTION_EXEC '){
			$data = substr($msg, 14);
			$json = json_decode($data, true);
			
			$args = array();
			$argsIn = $json['args'];
			foreach($argsIn as $arg){
				$args[] = unserialize($arg);
			}
			
			try{
				$rv = $this->functionExec($json['name'], $args);
				ve($rv);
			}
			catch(Exception $e){
				print __CLASS__.'->'.__FUNCTION__.': '.$e->getMessage().''."\n";
			}
		}
		elseif(substr($msg, 0, 14) == 'FUNCTION_RETN '){
			$data = substr($msg, 14);
			$json = json_decode($data, true);
			
			ve($json);
		}
	}
	
	public function run(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if($this->handler === null){
			throw new Exception('Handler not set. Use setHandler().');
		}
		
		$rv = false;
		$rv = true;
		
		$this->handler->run();
		
		if($this->isServer()){
			#print __CLASS__.'->'.__FUNCTION__.': is server'."\n";
			
			foreach($this->handler->recvBuffer() as $client){
				print __CLASS__.'->'.__FUNCTION__.': client '.$client['id']."\n";
				
				foreach($client['recvBuffer'] as $msg){
					#print "data: ".$client['id'].", '".$msg."'\n";
					$this->msgHandle($msg, $client['id']);
				}
			}
		}
		else{
			$rv = $this->handler->isConnected();
			foreach($this->handler->recvBuffer() as $msg){
				#print "data: '".$msg."'\n";
				$this->msgHandle($msg);
			}
		}
		
		
		return $rv;
	}
	
	public function loop(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		while($this->run()){
			usleep(100000);
		}
	}
	
}
