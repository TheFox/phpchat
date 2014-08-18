<?php

use TheFox\Dht\Kademlia\Table;
use TheFox\Dht\Kademlia\Node;

class TableTest extends PHPUnit_Framework_TestCase{
	
	public function testSerialize(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$localNode->setTimeCreated(1408371221);
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-010000000002');
		$node_a->setTimeCreated(1408371221);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-010000000004');
		$node_b->setTimeCreated(1408371221);
		
		$node_c = new Node();
		$node_c->setIdHexStr('10000001-2002-4004-8008-010000000008');
		$node_c->setTimeCreated(1408371221);
		
		$node_d = new Node();
		$node_d->setIdHexStr('10000001-2002-4004-8008-010000000010');
		$node_d->setTimeCreated(1408371221);
		
		$node_e = new Node();
		$node_e->setIdHexStr('10000001-2002-4004-8008-020000000008');
		$node_e->setTimeCreated(1408371221);
		
		$table->nodeEnclose($node_a);
		$table->nodeEnclose($node_b);
		$table->nodeEnclose($node_c);
		$table->nodeEnclose($node_d);
		$table->nodeEnclose($node_e);
		
		$table = unserialize(serialize($table));
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-010000000002');
		$node_a->setTimeCreated(1408371221);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-010000000004');
		$node_b->setTimeCreated(1408371221);
		
		$node_c = new Node();
		$node_c->setIdHexStr('10000001-2002-4004-8008-010000000008');
		$node_c->setTimeCreated(1408371221);
		
		$node_d = new Node();
		$node_d->setIdHexStr('10000001-2002-4004-8008-010000000010');
		$node_d->setTimeCreated(1408371221);
		
		$node_e = new Node();
		$node_e->setIdHexStr('10000001-2002-4004-8008-020000000008');
		$node_e->setTimeCreated(1408371221);
		
		$this->assertEquals($localNode, $table->getLocalNode());
		$this->assertEquals(array($node_a, $node_b, $node_c, $node_d, $node_e), $table->getNodes());
	}
	
	public function testNodeFindInBuckets1(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_a->setUri('tcp://192.168.241.1');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-100000000002');
		
		$onode = $table->nodeFindInBuckets($node_b);
		
		$this->assertEquals('192.168.241.1', $onode->getUri()->getHost());
	}
	
	public function testNodeFindInBuckets2(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_a->setUri('tcp://192.168.241.1');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-100000000003');
		$node_b->setUri('tcp://192.168.241.2');
		
		$onode = $table->nodeFindInBuckets($node_b);
		$this->assertEquals(null, $onode);
	}
	
	public function testNodeFindInBucketsByUri(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_a->setUri('tcp://192.168.241.1');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-100000000003');
		$node_b->setUri('tcp://192.168.241.2');
		$table->nodeEnclose($node_b);
		
		$onode = $table->nodeFindInBucketsByUri('tcp://192.168.241.3');
		$this->assertEquals(null, $onode);
		
		$onode = $table->nodeFindInBucketsByUri('tcp://192.168.241.2');
		$this->assertEquals($node_b, $onode);
	}
	
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
		$node_a->setUri('tcp://192.168.241.1');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_b->setUri('tcp://192.168.241.2');
		
		$onode = $table->nodeEnclose($node_b);
		
		$this->assertFalse($node_a === $node_b);
		$this->assertTrue($node_a === $onode);
		
		$this->assertEquals('192.168.241.1', $onode->getUri()->getHost());
	}
	
}
