<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;
use Zend\Uri\UriFactory;
use TheFox\PhpChat\TcpUri;

class UriTest extends PHPUnit_Framework_TestCase{
	
	public function testUri(){
		$uri1 = UriFactory::factory('tcp://192.168.241.21:25000');
		$uri2 = UriFactory::factory('tcp://192.168.241.21:25000');
		$uri3 = UriFactory::factory('tcp://192.168.241.22:25000');
		$uri4 = UriFactory::factory('');
		$uri5 = UriFactory::factory('192.168.241.22');
		$uri6 = UriFactory::factory('//192.168.241.22');
		$uri7 = UriFactory::factory('192.168.241.22:25000');
		
		$this->assertEquals($uri1, $uri2);
		$this->assertEquals('tcp://192.168.241.21:25000', $uri1);
		$this->assertEquals('tcp://192.168.241.21:25000', (string)$uri1);
		$this->assertEquals('tcp://192.168.241.22:25000', $uri3);
		$this->assertEquals('tcp://192.168.241.22:25000', (string)$uri3);
		$this->assertEquals('', (string)$uri4);
		$this->assertEquals('192.168.241.22', (string)$uri5);
		$this->assertEquals('', $uri5->getHost());
		$this->assertEquals('//192.168.241.22', (string)$uri6);
		$this->assertEquals('192.168.241.22', $uri6->getHost());
		
		$this->assertTrue($uri1 == $uri2);
		$this->assertFalse($uri1 == $uri3);
		$this->assertTrue($uri1 ? true : false);
		$this->assertTrue((string)$uri1 ? true : false);
		$this->assertTrue($uri4 ? true : false);
		$this->assertFalse((string)$uri4 ? true : false);
	}
	
}
