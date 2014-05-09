<?php

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Dht\Kademlia\Node;

class NodeTest extends PHPUnit_Framework_TestCase{
	
	const SSL_KEY_PUB = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA2+wZQQSxQXaxUmL/bg7O
gA7fOuw4Kk6/UtEntvM4O1Ll75l0ptgalwkO8DFhwRmWxDd0BYd/RxsbWrii3/1R
6+HSQdjyeeY3gQFdL7r65RRvXkYTtNSsFDeqcVQC+c6lFqRozQDNnAtxmy1Fhc0z
IUeC0iWNXIJciDYLTJV6VB0WNNl+5mCV2KaH2H3opw2A0c/+FTPWbvgf28WAd4FQ
koWiNjnDEDl5Ti39HeJN7q9LjpiafRTSrwE/kNcFNEtcdcxArxITuR92H+VjgXqs
dre0pqN7q1cJCZ/XP8Z0ZWA8rpLym+3S+FJaTJXhHBAv05hOu2zfzKUqaxmatAWz
NgxY7wvarGol/kqBYqyfVO/c1AOdr2Uw9rO0vJ9nPADih+OMYltaX521i6gvngdc
P7JJIZyNcZgN1l6HbO0KxugD2nJfkgGmU/ihIEpHjmrMXYMSzJy1KVOmLFpd8tiu
WXQCmarTOlzkcH7jmVqDRAjMUvDoAve4LYl0jua1W2wtCm1DisgIK6MCt38W8Zn3
o1pxgj1LiQmhAx4D9nL4MH14Zi++mK0iu8tJeXJdcql1l+bOJfkRjkNh3QjmLX3b
zoDXmjCC/vFQgspeMCSnIeml5Ymlk1tgxgiRNAPRpttbyr0jzlnUGEYZ/fGzNsY7
O5mYMzSLyuOXR5xhBhG7fjsCAwEAAQ==
-----END PUBLIC KEY-----
';
	
	public function testId(){
		$id = (string)Uuid::uuid4();
		
		$node = new Node();
		$node->setIdHexStr($id);
		$this->assertEquals($id, $node->getIdHexStr());
	}
	
	public function testSave(){
		$node = new Node('data/test_node.yml');
		$node->setDatadirBasePath('data');
		$node->setDataChanged(true);
		$node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		
		$node->setSslKeyPub(static::SSL_KEY_PUB);
		$this->assertFalse( $node->setSslKeyPub(static::SSL_KEY_PUB) );
		
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		$this->assertEquals('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwMZra', $node->getSslKeyPubFingerprint());
		
		$this->assertTrue( (bool)$node->save() );
	}
	
	/**
	* @depends testSave
	*/
	public function testLoad(){
		$node = new Node('data/test_node.yml');
		$node->setDatadirBasePath('data');
		
		$this->assertTrue($node->load());
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		$this->assertEquals('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwMZra', $node->getSslKeyPubFingerprint());
		
		#unlink('data/test_node.yml');
	}
	
	public function testSslKeyPubFingerprintVerify(){
		$this->assertTrue (Node::sslKeyPubFingerprintVerify('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwMZra'));
		$this->assertFalse(Node::sslKeyPubFingerprintVerify('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwM'));
		$this->assertFalse(Node::sslKeyPubFingerprintVerify('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwMXYZ'));
		$this->assertFalse(Node::sslKeyPubFingerprintVerify('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwM_XY'));
		$this->assertFalse(Node::sslKeyPubFingerprintVerify('BtK4HvbdX9wNQ6hGopSrFxs71SuuwMZra'));
		$this->assertFalse(Node::sslKeyPubFingerprintVerify('FC_XYZ4HvbdX9wNQ6hGopSrFxs71SuuwMZra'));
		$this->assertFalse(Node::sslKeyPubFingerprintVerify(''));
	}
	
	public function testDistanceHexStr(){
		$node_a = new Node();
		$node_a->setIdHexStr('11111111-1111-4111-8111-111111111100');
		
		$node_b = new Node();
		$node_b->setIdHexStr('11111111-1111-4111-8111-111111111100');
		
		$node_c = new Node();
		$node_c->setIdHexStr('11111111-1111-4111-8111-111111111102');
		
		$node_d = new Node();
		$node_d->setIdHexStr('11111111-1111-4111-8111-111111111104');
		
		$this->assertEquals('00000000-0000-0000-0000-000000000000', $node_a->distanceHexStr($node_b));
		$this->assertEquals('00000000-0000-0000-0000-000000000002', $node_a->distanceHexStr($node_c));
		$this->assertEquals('00000000-0000-0000-0000-000000000006', $node_c->distanceHexStr($node_d));
		
		$zeros = str_repeat('0', 120);
		$this->assertEquals($zeros.'00000000', $node_a->distanceBitStr($node_b));
		$this->assertEquals($zeros.'00000010', $node_a->distanceBitStr($node_c));
		$this->assertEquals($zeros.'00000110', $node_c->distanceBitStr($node_d));
	}
	
	public function testSetSslKeyPub(){
		$node = new Node();
		$this->assertTrue($node->setSslKeyPub(static::SSL_KEY_PUB));
		$this->assertFalse($node->setSslKeyPub(static::SSL_KEY_PUB));
	}
	
	/**
	 * @expectedException RuntimeException
	 */
	public function testSetSslKeyPubRuntimeException(){
		$node = new Node();
		$node->setSslKeyPub('invalid');
		#$node->setSslKeyPub(static::SSL_KEY_PUB);
		#$node->setSslKeyPub('abc', true);
	}
	
}
