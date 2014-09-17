<?php

use Colors\Color;

class ColorTest extends PHPUnit_Framework_TestCase{
	
	public function testColor(){
		$color = new Color();
		
		$msg = 'Hello World';
		
		$color($msg)->bg('green')->fg('black');
		$color($msg)->bg('white')->fg('black');
		
		$this->assertTrue(true);
	}
	
}
