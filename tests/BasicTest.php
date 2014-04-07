<?php

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

class BasicTest extends PHPUnit_Framework_TestCase{
	
	public function testStrIsUuid(){
		
		$id = '00000000-0000-4000-8000-000000000000';
		$this->assertTrue(strIsUuid($id));
		
		$id = '00000000-0000-4000-8000-00000000000x';
		$this->assertFalse(strIsUuid($id));
		
		$id = '00000000-0000-4000-8000-0000000000';
		$this->assertFalse(strIsUuid($id));
		
		$id = '00000000-0000-0000-0000-000000000000';
		$this->assertFalse(strIsUuid($id));
		
		$id = 'badfood0-0000-4000-a000-000000000000';
		$this->assertFalse(strIsUuid($id));
		
		$id = 'cafed00d-2131-4159-8e11-0b4dbadb1738';
		$this->assertTrue(strIsUuid($id));
		
		$id = (string)Uuid::uuid4();
		$this->assertTrue(strIsUuid($id));
		
		return $id;
	}
	
	/*public function testPushAndPop(){
		$stack = array();
		#$this->assertEquals(0, count($stack));
		
		array_push($stack, 'foo');
		#$this->assertEquals('foo', $stack[count($stack)-1]);
		#$this->assertEquals(1, count($stack));
		
		#$this->assertEquals('foo', array_pop($stack));
		#$this->assertEquals(0, count($stack));
		
		$this->assertTrue(TRUE, 'This should already work.');
		
		#$this->markTestSkipped('The MySQLi extension is not available.');
	}*/
	
	/*public function testOne(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		#$this->assertTrue(false);
		$this->assertTrue(true);
	}*/

	/**
	* @depends testOne
	*/
	/*public function testTwo(){
		$this->markTestIncomplete('This test has not been implemented yet.');
	}*/

	/**
	* @dataProvider provider
	*/
	/*public function testAdd($a, $b, $c){
		#print __CLASS__.'->'.__FUNCTION__.': '.$a.', '.$b.', '.$c.''."\n";
		
		#$this->assertEquals($c, $a + $b);
	}

	public function provider(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		return array(
			array(0, 0, 0),
			array(0, 1, 1),
			array(1, 0, 1),
			array(1, 1, 3),
		);
	}*/
}
