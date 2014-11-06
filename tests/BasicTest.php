<?php

class BasicTest extends PHPUnit_Framework_TestCase{
	
	public function testAlgos(){
		$this->assertTrue(in_array('sha512', hash_algos()), 'sha512 algorithm not found.');
		$this->assertTrue(in_array('ripemd160', hash_algos()), 'ripemd160 algorithm not found.');
	}
	
	public function testFunctions(){
		$this->assertTrue(function_exists('gzdecode'));
		$this->assertTrue(function_exists('gzcompress'));
		$this->assertTrue(function_exists('mt_rand'));
		$this->assertTrue(function_exists('strIsIp'));
		$this->assertTrue(function_exists('sslKeyPubClean'));
		$this->assertTrue(function_exists('intToBin'));
		$this->assertTrue(function_exists('timeStop'));
	}
	
	public function testExtensions(){
		$this->assertTrue(extension_loaded('openssl'));
		$this->assertTrue(extension_loaded('sockets'));
		$this->assertTrue(extension_loaded('curl'));
		$this->assertTrue(extension_loaded('bcmath'));
	}
	
}
