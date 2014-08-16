<?php

use TheFox\Dht\Kademlia\Table;
use TheFox\Dht\Kademlia\Node;

class TableTest extends PHPUnit_Framework_TestCase{
	
	public function testNodeFindClosest(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-010000000002');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-010000000004');
		$table->nodeEnclose($node_b);
		
		$node_c = new Node();
		$node_c->setIdHexStr('10000001-2002-4004-8008-010000000008');
		$table->nodeEnclose($node_c);
		
		$node_d = new Node();
		$node_d->setIdHexStr('10000001-2002-4004-8008-010000000010');
		$table->nodeEnclose($node_d);
		
		
		$node_e = new Node();
		$node_e->setIdHexStr('10000001-2002-4004-8008-020000000008');
		
		$nodes = $table->nodeFindClosest($node_e);
		
		$this->assertEquals(array($node_c, $node_a, $node_b, $node_d), $nodes);
	}
	
	public function testNodeFindInBuckets1(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_a->setHost('192.168.241.21');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-100000000002');
		
		$onode = $table->nodeFindInBuckets($node_b);
		
		$this->assertEquals('192.168.241.21', $onode->getHost());
	}
	
	public function testNodeFindInBuckets2(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_a->setHost('192.168.241.21');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-100000000003');
		$node_b->setHost('10.0.0.1');
		
		$onode = $table->nodeFindInBuckets($node_b);
		$this->assertEquals(null, $onode);
	}
	
	public function testNodeEnclose1(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node = new Node();
		$node->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$onode = $table->nodeEnclose($node);
		
		$this->assertEquals($node, $onode);
	}
	
	public function testNodeEnclose2(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_a->setHost('192.168.241.21');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_b->setHost('10.0.0.1');
		
		$onode = $table->nodeEnclose($node_b);
		
		$this->assertFalse($node_a === $node_b);
		$this->assertTrue($node_a === $onode);
		
		$this->assertEquals('192.168.241.21', $onode->getHost());
	}
	
}
