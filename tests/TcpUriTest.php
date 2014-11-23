<?php

use TheFox\PhpChat\TcpUri;

class TcpUriTest extends PHPUnit_Framework_TestCase{
	
	public function testSerialize(){
		$uri1 = new TcpUri('tcp://192.168.241.24:25000');
		
		$uri2 = unserialize(serialize($uri1));
		
		#\Doctrine\Common\Util\Debug::dump($uri2);
		
		$this->assertEquals('tcp://192.168.241.24:25000', (string)$uri2);
	}
	
}
