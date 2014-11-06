<?php

class BasicTest extends PHPUnit_Framework_TestCase{
	
	public function testAlgos(){
		$this->assertTrue(in_array('sha512', hash_algos()), 'sha512 algorithm not found.');
		$this->assertTrue(in_array('ripemd160', hash_algos()), 'ripemd160 algorithm not found.');
	}
	
	public function testFunctions(){
		$this->assertTrue(function_exists('gzdecode'), 'gzdecode function not found.');
		$this->assertTrue(function_exists('gzcompress'), 'gzcompress function not found.');
		$this->assertTrue(function_exists('mt_rand'), 'mt_rand function not found.');
		$this->assertTrue(function_exists('strIsIp'), 'strIsIp function not found.');
		$this->assertTrue(function_exists('sslKeyPubClean'), 'sslKeyPubClean function not found.');
		$this->assertTrue(function_exists('intToBin'), 'intToBin function not found.');
		$this->assertTrue(function_exists('timeStop'), 'timeStop function not found.');
	}
	
	public function testExtensions(){
		$this->assertTrue(extension_loaded('openssl'), 'openssl extension not found.');
		$this->assertTrue(extension_loaded('sockets'), 'sockets extension not found.');
		$this->assertTrue(extension_loaded('curl'), 'curl extension not found.');
		$this->assertTrue(extension_loaded('bcmath'), 'bcmath extension not found.');
	}
	
}
