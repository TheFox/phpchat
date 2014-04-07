<?php

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Dht\Kademlia\Node;

class NodeTest extends PHPUnit_Framework_TestCase{
	
	private $node = null;
	
	public function setUp(){
		$this->node = new Node();
	}
	
	public function testStrIsUuid(){
		$id = (string)Uuid::uuid4();
		$this->assertTrue(strIsUuid($id));
		return $id;
	}
	
	/**
	* @depends testStrIsUuid
	*/
	public function testId($id){
		$this->node->setIdHexStr($id);
		$this->assertEquals($id, $this->node->getIdHexStr());
	}
	
}
