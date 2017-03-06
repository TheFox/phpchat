<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;

class TestTest extends PHPUnit_Framework_TestCase{
	
	public $x = 21;
	
	public static function setUpBeforeClass(){
		fwrite(STDOUT, __METHOD__.''."\n");
	}
	
	protected function setUp(){
		fwrite(STDOUT, __METHOD__.''."\n");
	}
	
	protected function assertPreConditions(){
		fwrite(STDOUT, __METHOD__.''."\n");
	}
	
	public function testOne(){
		fwrite(STDOUT, __METHOD__.''."\n");
		$this->assertTrue(true);
	}
	
	public function testTwo(){
		fwrite(STDOUT, __METHOD__.''."\n");
		$this->assertTrue(false);
	}
	
	protected function assertPostConditions(){
		fwrite(STDOUT, __METHOD__.''."\n");
	}
	
	protected function tearDown(){
		fwrite(STDOUT, __METHOD__.''."\n");
	}
	
	public static function tearDownAfterClass(){
		fwrite(STDOUT, __METHOD__.''."\n");
	}
	
	protected function onNotSuccessfulTest(Exception $e){
		fwrite(STDOUT, __METHOD__.''."\n");
		throw $e;
	}
	
	public function testArray(){
		$stack = array();
		$this->assertEquals(0, count($stack));
	}
	
	public function testTrue(){
		$this->assertTrue(true);
	}
	
	/*public function testPushAndPop(){
		$stack = array();
		#$this->assertEquals(0, count($stack));
		
		array_push($stack, 'foo');
		#$this->assertEquals('foo', $stack[count($stack)-1]);
		#$this->assertEquals(1, count($stack));
		
		#$this->assertEquals('foo', array_pop($stack));
		#$this->assertEquals(0, count($stack));
		
		$this->assertTrue(true, 'This should already work.');
		
		#$this->markTestSkipped('The MySQLi extension is not available.');
	}*/
	
	/*public function testOne(){
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
		#$this->assertEquals($c, $a + $b);
	}

	public function provider(){
		return array(
			array(0, 0, 0),
			array(0, 1, 1),
			array(1, 0, 1),
			array(1, 1, 3),
		);
	}*/
}
