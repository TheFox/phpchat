<?php

class BasicTest extends PHPUnit_Framework_TestCase{
	
	public function testFunctions(){
		$this->assertTrue(in_array('sha512', hash_algos()), 'sha512 algorithm not found.');
		$this->assertTrue(in_array('ripemd160', hash_algos()), 'ripemd160 algorithm not found.');
	}
	
}
