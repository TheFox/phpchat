<?php

namespace TheFox\PhpChat;

use TheFox\Ipc\Connection;
use TheFox\Ipc\StreamHandler;

class Thread{
	
	private $exit = 0;
	
	public function __construct(){
		
	}
	
	public function setExit($exit){
		$this->exit = $exit;
	}
	
	public function getExit(){
		return $this->exit;
	}
	
}
