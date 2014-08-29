<?php

use TheFox\Dht\Kademlia\Bucket;

class KademliaBucketTest extends PHPUnit_Framework_TestCase{
	
	public function testSave(){
		$bucket = new Bucket('tests/testfile_bucket_bucket1.yml');
		$bucket->setDatadirBasePath('tests');
		$bucket->setDataChanged(true);
		
		$bucket->setDistance(21);
		$this->assertEquals(21, $bucket->getDistance());
		
		$bucket->setMaskBit(24);
		$this->assertEquals(24, $bucket->getMaskBit());
		
		$bucket->setIsFull(true);
		$this->assertEquals(true, $bucket->getIsFull());
		
		$bucket->setIsUpper(true);
		$this->assertEquals(true, $bucket->getIsUpper());
		
		$bucket->setIsLower(true);
		$this->assertEquals(true, $bucket->getIsLower());
		
		$this->assertTrue((bool)$bucket->save());
		
		
		$bucket = new Bucket('tests/testfile_bucket_bucket1.yml');
		
		$this->assertTrue($bucket->load());
		
		$this->assertEquals(21, $bucket->getDistance());
		$this->assertEquals(24, $bucket->getMaskBit());
		$this->assertEquals(true, $bucket->getIsFull());
		$this->assertEquals(true, $bucket->getIsUpper());
		$this->assertEquals(true, $bucket->getIsLower());
	}
	
}
