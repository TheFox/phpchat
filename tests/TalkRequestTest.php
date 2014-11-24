<?php

use TheFox\PhpChat\TalkRequest;
use TheFox\PhpChat\Client;

class TalkRequestTest extends PHPUnit_Framework_TestCase{
	
	public function testSetId(){
		$request = new TalkRequest();
		
		$request->setId(1);
		$this->assertEquals(1, $request->getId());
		
		$request->setId(24);
		$this->assertEquals(24, $request->getId());
	}
	
	public function testSetRid(){
		$request = new TalkRequest();
		
		$request->setRid(1);
		$this->assertEquals(1, $request->getRid());
		
		$request->setRid(24);
		$this->assertEquals(24, $request->getRid());
	}
	
	public function testSetClient(){
		$client = new Client();
		$request = new TalkRequest();
		
		$request->setClient($client);
		$this->assertEquals($client, $request->getClient());
		
		#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.': end'."\n");
	}
	
	public function testSetUserNickname(){
		$request = new TalkRequest();
		
		$request->setUserNickname('xyz');
		$this->assertEquals('xyz', $request->getUserNickname());
		
		$request->setUserNickname('123');
		$this->assertEquals('123', $request->getUserNickname());
	}
	
	public function testSetStatus(){
		$request = new TalkRequest();
		
		$request->setStatus(1);
		$this->assertEquals(1, $request->getStatus());
		
		$request->setStatus(24);
		$this->assertEquals(24, $request->getStatus());
	}
	
}
