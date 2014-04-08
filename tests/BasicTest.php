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
	
}
