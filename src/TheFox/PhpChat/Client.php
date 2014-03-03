<?php

namespace TheFox\PhpChat;

use Exception;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Utilities\Hex;

class Client{
	
	private $id = 0;
	
	public function __construct(){
		
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
}
