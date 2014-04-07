<?php

class BasicTest extends PHPUnit_Framework_TestCase{
	
	public function testPushAndPop(){
		$stack = array();
		#$this->assertEquals(0, count($stack));
		
		array_push($stack, 'foo');
		#$this->assertEquals('foo', $stack[count($stack)-1]);
		#$this->assertEquals(1, count($stack));
		
		#$this->assertEquals('foo', array_pop($stack));
		#$this->assertEquals(0, count($stack));
		
		$this->assertTrue(TRUE, 'This should already work.');
		
		#$this->markTestSkipped('The MySQLi extension is not available.');
	}
	
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
