<?php

namespace TheFox\Ipc;

class Connection{
	
	private $ip = '127.0.0.1';
	private $port = 0;
	
	public function __construct(){
		print __CLASS__.'->'.__FUNCTION__."\n";
		print __CLASS__.'->'.__FUNCTION__.': '.$this->ip."\n";
		
	}
	
	public function setPort($port){
		$this->port = $port;
	}
	
}
