<?php

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;
use Zend\Uri\UriFactory;
use Zend\Uri\Http;

use TheFox\Dht\Kademlia\Node;
use TheFox\Dht\Kademlia\Bucket;
use TheFox\PhpChat\TcpUri;
use TheFox\PhpChat\HttpUri;

class KademliaNodeTest extends PHPUnit_Framework_TestCase{
	
	const SSL_KEY_PUB1 = '-----BEGIN PUBLIC KEY-----
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
	
	// A: with new line at the end.
	const SSL_KEY_PUB2_A = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAwrX73etzvLFRell4nfIT
3YFOuqAwCU6w1N1uipV+e96fx00ZsKQHvugyhwSP85a5TZ4qfQQie3kyRrwwL91s
dXECxskfOXtO94k9CENZGihkacnLUp8eAPJ3dJNHcM9AZm+gFVhVU7XmcQxXex6p
k3nWpCyrrK4ZUeg+D858Tadgd4w+uOgKozUyARrWU5AVVY27X/u97a3DkKbNZhuC
h3gSkBD/d8rjwe6d9siHb6aqiw6DBYXL3AlsDoN/lGvnV1U3wY9zRQ5BuebBzt7Q
ndAqKC9ZdCEK/JFBXkjJsiNOlKmc0AJpf41SyOqvkLvpBxwPvbTp4VLoGwMty+nT
8ke/REypXS9scFDlE6k71xAMi9OBthiVP5lszptPEn3cfhG0YRLuzkLOpcV5Gm0r
egQS+y0TFEu25vUbGcWKKjrWxG+TkWgyKkiylJoXdPRXxDrHA8KEY651x8vs/gsy
rdhoXy/EI+fxI9ytf7JLnc2OP2eh8qdUhcLMMs9mUOy9hojaxBYMAXRqJK53Lsgd
kWcl2BJ8IxSMYUeTbb8UmS2Qr8wWzEVqd/SQ4olC3gcPReEohMpJ+X0mp7CmjQUS
4GoZlLrIR/xaI76pSJLH2FBfHWiLS/Cbpgw8IEcKJFJVIqi7qjHw7MaoXMIqxZVT
2JGsj8q54He5gnVI01MEWr0CAwEAAQ==
-----END PUBLIC KEY-----
';
	
	// B: without a new line at the end.
	const SSL_KEY_PUB2_B = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAwrX73etzvLFRell4nfIT
3YFOuqAwCU6w1N1uipV+e96fx00ZsKQHvugyhwSP85a5TZ4qfQQie3kyRrwwL91s
dXECxskfOXtO94k9CENZGihkacnLUp8eAPJ3dJNHcM9AZm+gFVhVU7XmcQxXex6p
k3nWpCyrrK4ZUeg+D858Tadgd4w+uOgKozUyARrWU5AVVY27X/u97a3DkKbNZhuC
h3gSkBD/d8rjwe6d9siHb6aqiw6DBYXL3AlsDoN/lGvnV1U3wY9zRQ5BuebBzt7Q
ndAqKC9ZdCEK/JFBXkjJsiNOlKmc0AJpf41SyOqvkLvpBxwPvbTp4VLoGwMty+nT
8ke/REypXS9scFDlE6k71xAMi9OBthiVP5lszptPEn3cfhG0YRLuzkLOpcV5Gm0r
egQS+y0TFEu25vUbGcWKKjrWxG+TkWgyKkiylJoXdPRXxDrHA8KEY651x8vs/gsy
rdhoXy/EI+fxI9ytf7JLnc2OP2eh8qdUhcLMMs9mUOy9hojaxBYMAXRqJK53Lsgd
kWcl2BJ8IxSMYUeTbb8UmS2Qr8wWzEVqd/SQ4olC3gcPReEohMpJ+X0mp7CmjQUS
4GoZlLrIR/xaI76pSJLH2FBfHWiLS/Cbpgw8IEcKJFJVIqi7qjHw7MaoXMIqxZVT
2JGsj8q54He5gnVI01MEWr0CAwEAAQ==
-----END PUBLIC KEY-----';
	
	public function testId(){
		$this->assertEquals(Node::ID_LEN_BIT, Node::ID_LEN_BYTE * 8);
		$this->assertEquals(Node::ID_LEN_BYTE, Node::ID_LEN_BIT / 8);
		
		// Array index.
		$this->assertEquals(0, floor(0 / 8));
		$this->assertEquals(0, floor(1 / 8));
		$this->assertEquals(0, floor(7 / 8));
		$this->assertEquals(1, floor(8 / 8));
		$this->assertEquals(1, floor(9 / 8));
		$this->assertEquals(1, floor(15 / 8));
		$this->assertEquals(2, floor(16 / 8));
		$this->assertEquals(15, floor(126 / 8));
		$this->assertEquals(15, floor(127 / 8));
	}
	
	public function testCompareSslKey(){
		$this->assertFalse(static::SSL_KEY_PUB2_A == static::SSL_KEY_PUB2_B);
	}
	
	public function testSerialize(){
		$node = new Node();
		$node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$node->setUri('tcp://192.168.241.21:25001');
		
		$node = unserialize(serialize($node));
		
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		$this->assertEquals('tcp://192.168.241.21:25001', (string)$node->getUri());
	}
	
	public function testToString1(){
		$node = new Node();
		$node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$this->assertEquals('TheFox\Dht\Kademlia\Node->{ID:cafed00d-2131-4159-8e11-0b4dbadb1738}', (string)$node);
	}
	
	public function testToString2(){
		$node = new Node();
		$node->setUri('tcp://192.168.241.21:25001');
		$this->assertEquals('TheFox\Dht\Kademlia\Node->{URI:tcp://192.168.241.21:25001}', (string)$node);
	}
	
	public function testToString3(){
		$node = new Node();
		$this->assertEquals('TheFox\Dht\Kademlia\Node', (string)$node);
	}
	
	public function testSaveNode(){
		$node = new Node('test_data/testfile_node_tcp.yml');
		$node->setDatadirBasePath('test_data');
		$node->setDataChanged(true);
		$node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$node->setUri('tcp://192.168.241.21:25001');
		
		$node->setSslKeyPub(static::SSL_KEY_PUB1);
		$this->assertFalse( $node->setSslKeyPub(static::SSL_KEY_PUB1) );
		
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		#$this->assertEquals('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwMZra', $node->getSslKeyPubFingerprint());
		$this->assertEquals('FC_TVqkkaeVwy5HMADDy1ErtdSsBUQ8Ch5zVPNYegNnHBVgejj8Mu8UYW78v5TyUC7aCB2Wo11hrMsfrVk', $node->getSslKeyPubFingerprint());
		
		$this->assertTrue( (bool)$node->save() );
	}
	
	/**
	* @depends testSaveNode
	*/
	public function testLoadNode1(){
		$node = new Node('test_data/testfile_node_tcp.yml');
		$node->setDatadirBasePath('test_data');
		
		$this->assertTrue($node->load());
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		#ve($node->getUri());
		$this->assertEquals('tcp', $node->getUri()->getScheme());
		$this->assertEquals('FC_TVqkkaeVwy5HMADDy1ErtdSsBUQ8Ch5zVPNYegNnHBVgejj8Mu8UYW78v5TyUC7aCB2Wo11hrMsfrVk', $node->getSslKeyPubFingerprint());
		$this->assertEquals(static::SSL_KEY_PUB1, $node->getSslKeyPub());
	}
	
	public function testLoadNode2(){
		$node = new Node('test_data/testfile_node_tcp2.yml');
		$node->setDatadirBasePath('test_data');
		
		$this->assertFalse($node->load());
	}
	
	public function testGetIdHexStr(){
		$id = (string)Uuid::uuid4();
		
		$node = new Node();
		$node->setIdHexStr($id);
		$this->assertEquals($id, $node->getIdHexStr());
		
		$node = new Node();
		$node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		
		$node = new Node();
		$node->setIdHexStr('CAFED00d-2131-4159-8e11-0b4dbadb1738');
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		
		$node = new Node();
		$node->setIdHexStr('CAFED00d-2131-4159-8e11-0');
		$this->assertEquals('00000000-0000-4000-8000-000000000000', $node->getIdHexStr());
		
		$node = new Node();
		$node->setIdHexStr('xafed00d-2131-4159-8e11-0b4dbadb1738');
		$this->assertEquals('00000000-0000-4000-8000-000000000000', $node->getIdHexStr());
		
		$node = new Node();
		$node->setIdHexStr('cafed00d-2131-0159-0e11-0b4dbadb1738');
		$this->assertEquals('cafed00d-2131-0159-0e11-0b4dbadb1738', $node->getIdHexStr());
	}
	
	public function testGenIdHexStr(){
		$this->assertEquals('d4773c00-6a11-540a-b72c-ed106ef8309b', Node::genIdHexStr(static::SSL_KEY_PUB1));
		$this->assertEquals('4a5b4d4e-0025-5d8d-ae5c-ca1c5548dfba', Node::genIdHexStr(static::SSL_KEY_PUB2_A));
		$this->assertEquals('4a5b4d4e-0025-5d8d-ae5c-ca1c5548dfba', Node::genIdHexStr(static::SSL_KEY_PUB2_B));
	}
	
	public function testGetIdBitStr(){
		$expected = '';
		$expected .= '11001010111111101101000000001101001000010011000101000001010110011000';
		$expected .= '111000010001000010110100110110111010110110110001011100111000';
		$node = new Node();
		$node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$this->assertEquals($expected, $node->getIdBitStr());
	}
	
	public function testIdMinHexStr(){
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738',
			Node::idMinHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738', 'cafed00d-2131-4159-8e11-0b4dbadb1738'));
		
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738',
			Node::idMinHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738', 'cafed00d-2131-4159-8e11-0b4dbadb1739'));
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738',
			Node::idMinHexStr('cafed00d-2131-4159-8e11-0b4dbadb1739', 'cafed00d-2131-4159-8e11-0b4dbadb1738'));
		
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1740',
			Node::idMinHexStr('cafed00d-2131-4159-8e11-0b4dbadb174a', 'cafed00d-2131-4159-8e11-0b4dbadb1740'));
	}
	
	public function testGetUri(){
		$node = new Node();
		$this->assertTrue($node->getUri() instanceof TcpUri);
		$this->assertEquals('tcp', $node->getUri()->getScheme());
		
		$node = new Node();
		$node->setUri('');
		$this->assertEquals('tcp', $node->getUri()->getScheme());
		
		$node = new Node();
		$node->setUri('tcp://192.168.241.21:25001');
		$this->assertEquals('tcp', $node->getUri()->getScheme());
		$this->assertEquals('192.168.241.21', $node->getUri()->getHost());
		$this->assertEquals('25001', $node->getUri()->getPort());
		$this->assertEquals('tcp://192.168.241.21:25001', (string)$node->getUri());
		
		$uri = UriFactory::factory('tcp://192.168.241.21:25001');
		$this->assertTrue($uri instanceof TcpUri);
		$node = new Node();
		$node->setUri($uri);
		$this->assertEquals($uri, $node->getUri());
		$this->assertEquals('tcp', $node->getUri()->getScheme());
		$this->assertEquals('192.168.241.21', $node->getUri()->getHost());
		$this->assertEquals('25001', $node->getUri()->getPort());
		$this->assertEquals('tcp://192.168.241.21:25001', (string)$node->getUri());
		
		$uri = UriFactory::factory('http://phpchat.fox21.at/web/phpchat.php');
		$this->assertTrue($uri instanceof HttpUri);
		$node = new Node();
		$node->setUri($uri);
		$this->assertEquals($uri, $node->getUri());
		$this->assertEquals('http', $node->getUri()->getScheme());
		$this->assertEquals('phpchat.fox21.at', $node->getUri()->getHost());
		$this->assertEquals('/web/phpchat.php', $node->getUri()->getPath());
		$this->assertEquals('http://phpchat.fox21.at/web/phpchat.php', (string)$node->getUri());
	}
	
	public function testSetSslKeyPub1(){
		$node = new Node();
		$this->assertTrue($node->setSslKeyPub(static::SSL_KEY_PUB1));
		$this->assertFalse($node->setSslKeyPub(static::SSL_KEY_PUB1));
	}
	
	public function testSetSslKeyPub2(){
		$node = new Node();
		$node->setSslKeyPub(static::SSL_KEY_PUB2_A);
		
		#$this->assertEquals('FC_5zk4NskvcrQdJJLYQFb4V6fai8bzMV82G', $node->getSslKeyPubFingerprint());
		$this->assertEquals('FC_SxXQaupNHdvtYVknJyqasrqsabsdZCwGMFrh34GiggcuF9Ry1LrWgdm9RjJeG8sd4rhgpjAvfnPaK9t', $node->getSslKeyPubFingerprint());
		$this->assertEquals(static::SSL_KEY_PUB2_A, $node->getSslKeyPub());
	}
	
	public function testSetSslKeyPub3(){
		$node = new Node();
		$node->setSslKeyPub(static::SSL_KEY_PUB2_B);
		
		$this->assertEquals('FC_SxXQaupNHdvtYVknJyqasrqsabsdZCwGMFrh34GiggcuF9Ry1LrWgdm9RjJeG8sd4rhgpjAvfnPaK9t', $node->getSslKeyPubFingerprint());
		$this->assertEquals(static::SSL_KEY_PUB2_A, $node->getSslKeyPub());
	}
	
	/**
	 * @expectedException RuntimeException
	 */
	public function testSetSslKeyPubRuntimeException(){
		$node = new Node();
		$node->setSslKeyPub('invalid');
	}
	
	public function testSetSslKeyPubStatus(){
		$node = new Node();
		
		$node->setSslKeyPubStatus('U');
		$this->assertEquals('U', $node->getSslKeyPubStatus());
		
		$node->setSslKeyPubStatus('C');
		$this->assertEquals('C', $node->getSslKeyPubStatus());
		
		$node->setSslKeyPubStatus('U');
		$this->assertEquals('C', $node->getSslKeyPubStatus());
	}
	
	public function testSslKeyPubFingerprintVerify(){
		$this->assertTrue (Node::sslKeyPubFingerprintVerify('FC_TVqkkaeVwy5HMADDy1ErtdSsBUQ8Ch5zVPNYegNnHBVgejj8Mu8UYW78v5TyUC7aCB2Wo11hrMsfrVk'));
		$this->assertFalse(Node::sslKeyPubFingerprintVerify('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwM'));
		$this->assertFalse(Node::sslKeyPubFingerprintVerify('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwMXYZ'));
		#$this->assertFalse(Node::sslKeyPubFingerprintVerify('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwM_XY'));
		$this->assertFalse(Node::sslKeyPubFingerprintVerify('BtK4HvbdX9wNQ6hGopSrFxs71SuuwMZra'));
		$this->assertFalse(Node::sslKeyPubFingerprintVerify('FC_XYZ4HvbdX9wNQ6hGopSrFxs71SuuwMZra'));
		$this->assertFalse(Node::sslKeyPubFingerprintVerify(''));
	}
	
	public function testSetDistance(){
		$distance = array_fill(0, Node::ID_LEN_BYTE, 0);
		$distance[0] = 1;
		$distance[1] = 2;
		$distance[2] = 3;
		$distance[3] = 4;
		
		$node = new Node();
		$node->setDistance($distance);
		$this->assertEquals($distance, $node->getDistance());
	}
	
	public function testDistance(){
		$node_a = new Node();
		$node_a->setIdHexStr('11111111-1111-4111-8111-111111111100');
		
		$node_b = new Node();
		$node_b->setIdHexStr('11111111-1111-4111-8111-111111111100');
		
		$node_c = new Node();
		$node_c->setIdHexStr('11111111-1111-4111-8111-111111111102');
		
		$node_d = new Node();
		$node_d->setIdHexStr('11111111-1111-4111-8111-111111111104');
		
		
		$distance = array_fill(0, Node::ID_LEN_BYTE, 0);
		$this->assertEquals($distance, $node_a->distance($node_b));
		
		$distance = array_fill(0, Node::ID_LEN_BYTE, 0);
		$distance[15] = 2;
		$this->assertEquals($distance, $node_a->distance($node_c));
		
		$distance = array_fill(0, Node::ID_LEN_BYTE, 0);
		$distance[15] = 4;
		$this->assertEquals($distance, $node_a->distance($node_d));
	}
	
	public function testDistanceBitStr(){
		$node_a = new Node();
		$node_a->setIdHexStr('11111111-1111-4111-8111-111111111100');
		
		$node_b = new Node();
		$node_b->setIdHexStr('11111111-1111-4111-8111-111111111100');
		
		$node_c = new Node();
		$node_c->setIdHexStr('11111111-1111-4111-8111-111111111102');
		
		$node_d = new Node();
		$node_d->setIdHexStr('11111111-1111-4111-8111-111111111104');
		
		$zeros = str_repeat('0', 120);
		$this->assertEquals($zeros.'00000000', $node_a->distanceBitStr($node_b));
		$this->assertEquals($zeros.'00000010', $node_a->distanceBitStr($node_c));
		$this->assertEquals($zeros.'00000110', $node_c->distanceBitStr($node_d));
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
	}
	
	public function testSetConnectionsOutboundSucceed(){
		$node = new Node();
		$this->assertEquals(0, $node->getConnectionsOutboundSucceed());
		
		$node->setConnectionsOutboundSucceed(24);
		$this->assertEquals(24, $node->getConnectionsOutboundSucceed());
	}
	
	public function testIncConnectionsOutboundSucceed(){
		$node = new Node();
		$node->incConnectionsOutboundSucceed();
		$this->assertEquals(1, $node->getConnectionsOutboundSucceed());
		
		$node->incConnectionsOutboundSucceed();
		$this->assertEquals(2, $node->getConnectionsOutboundSucceed());
		
		$node->incConnectionsOutboundSucceed(2);
		$this->assertEquals(4, $node->getConnectionsOutboundSucceed());
	}
	
	public function testSetConnectionsOutboundAttempts(){
		$node = new Node();
		$this->assertEquals(0, $node->getConnectionsOutboundAttempts());
		
		$node->setConnectionsOutboundAttempts(24);
		$this->assertEquals(24, $node->getConnectionsOutboundAttempts());
	}
	
	public function testIncConnectionsOutboundAttempts(){
		$node = new Node();
		
		$node->incConnectionsOutboundAttempts();
		$this->assertEquals(1, $node->getConnectionsOutboundAttempts());
		
		$node->incConnectionsOutboundAttempts();
		$this->assertEquals(2, $node->getConnectionsOutboundAttempts());
		
		$node->incConnectionsOutboundAttempts(2);
		$this->assertEquals(4, $node->getConnectionsOutboundAttempts());
	}
	
	public function testSetConnectionsInboundSucceed(){
		$node = new Node();
		$this->assertEquals(0, $node->getConnectionsInboundSucceed());
		
		$node->setConnectionsInboundSucceed(24);
		$this->assertEquals(24, $node->getConnectionsInboundSucceed());
	}
	
	public function testIncConnectionsInboundSucceed(){
		$node = new Node();
		
		$node->incConnectionsInboundSucceed();
		$this->assertEquals(1, $node->getConnectionsInboundSucceed());
		
		$node->incConnectionsInboundSucceed();
		$this->assertEquals(2, $node->getConnectionsInboundSucceed());
		
		$node->incConnectionsInboundSucceed(2);
		$this->assertEquals(4, $node->getConnectionsInboundSucceed());
	}
	
	public function testSetBridgeServer(){
		$node = new Node();
		$this->assertFalse($node->getBridgeServer());
		
		$node->setBridgeServer(true);
		$this->assertTrue($node->getBridgeServer());
	}
	
	public function testSetBridgeClient(){
		$node = new Node();
		$this->assertFalse($node->getBridgeClient());
		
		$node->setBridgeClient(true);
		$this->assertTrue($node->getBridgeClient());
	}
	
	public function testAddBridgeDst(){
		$node = new Node();
		$this->assertEquals(array(), $node->getBridgeDst());
		
		$node->addBridgeDst(array());
		$this->assertEquals(array(), $node->getBridgeDst());
		
		$node->addBridgeDst(array('192.168.241.24:25000'));
		$this->assertEquals(array('192.168.241.24:25000'), $node->getBridgeDst());
		
		$node->addBridgeDst(array('192.168.241.25:25000'));
		$this->assertEquals(array('192.168.241.24:25000', '192.168.241.25:25000'), $node->getBridgeDst());
		
		$node->addBridgeDst('192.168.241.26:25000');
		$this->assertEquals(array('192.168.241.24:25000', '192.168.241.25:25000',
			'192.168.241.26:25000'), $node->getBridgeDst());
	}
	
	public function testSetTimeCreated(){
		$node = new Node();
		$this->assertEquals(time(), $node->getTimeCreated());
		
		$node->setTimeCreated(24);
		$this->assertEquals(24, $node->getTimeCreated());
	}
	
	public function testSetTimeLastSeen(){
		$node = new Node();
		$this->assertEquals(0, $node->getTimeLastSeen());
		
		$node->setTimeLastSeen(24);
		$this->assertEquals(24, $node->getTimeLastSeen());
	}
	
	public function testSetBucket(){
		$bucket = new Bucket();
		
		$node = new Node();
		$this->assertEquals(null, $node->getBucket());
		
		$node->setBucket($bucket);
		$this->assertEquals($bucket, $node->getBucket());
	}
	
	public function testIsEqual(){
		$node_a = new Node();
		$node_a->setIdHexStr('11111111-1111-4111-8111-111111111100');
		
		$node_b = new Node();
		$node_b->setIdHexStr('11111111-1111-4111-8111-111111111100');
		
		$node_c = new Node();
		$node_c->setIdHexStr('11111111-1111-4111-8111-111111111101');
		
		$this->assertTrue($node_a->isEqual($node_b));
		$this->assertFalse($node_a->isEqual($node_c));
	}
	
	public function testUpdate(){
		$node_a = new Node();
		$node_a->setIdHexStr('11111111-1111-4111-8111-111111111100');
		$node_a->setUri('tcp://192.168.241.24:25000');
		
		$node_b = new Node();
		$node_b->setIdHexStr('11111111-1111-4111-8111-111111111101');
		$node_b->setUri('tcp://192.168.241.25:25000');
		$node_b->setBridgeServer(true);
		$node_b->setBridgeClient(true);
		
		$node_a->update($node_b);
		$this->assertEquals('11111111-1111-4111-8111-111111111100', $node_a->getIdHexStr());
		$this->assertEquals('tcp://192.168.241.24:25000', (string)$node_a->getUri());
		$this->assertFalse($node_a->getBridgeServer());
		$this->assertFalse($node_a->getBridgeClient());
		$this->assertFalse($node_a->getDataChanged());
		
		$node_b->setTimeLastSeen(time());
		
		$node_a->update($node_b);
		$this->assertEquals('11111111-1111-4111-8111-111111111100', $node_a->getIdHexStr());
		$this->assertEquals('tcp://192.168.241.25:25000', (string)$node_a->getUri());
		$this->assertTrue($node_a->getBridgeServer());
		$this->assertTrue($node_a->getBridgeClient());
		$this->assertTrue($node_a->getDataChanged());
	}
	
}
