<?php

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;
use Zend\Uri\UriFactory;
use Zend\Uri\Http;

use TheFox\Dht\Kademlia\Node;
use TheFox\PhpChat\TcpUri;
use TheFox\PhpChat\HttpUri;

class NodeTest extends PHPUnit_Framework_TestCase{
	
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
	
	public function testSerialize(){
		$node = new Node();
		$node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$node->setUri('tcp://192.168.241.21:25001');
		
		$node = unserialize(serialize($node));
		
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		$this->assertEquals('tcp://192.168.241.21:25001', (string)$node->getUri());
	}
	
	public function testId(){
		$id = (string)Uuid::uuid4();
		
		$node = new Node();
		$node->setIdHexStr($id);
		$this->assertEquals($id, $node->getIdHexStr());
	}
	
	public function testUri(){
		$node = new Node();
		$this->assertTrue($node->getUri() instanceof TcpUri);
		$this->assertEquals('tcp', $node->getUri()->getScheme());
		#ve($node->getUri());
		
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
	
	public function testSaveTcpnode(){
		$node = new Node('tests/test_node_tcp.yml');
		$node->setDatadirBasePath('tests');
		$node->setDataChanged(true);
		$node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		
		$node->setSslKeyPub(static::SSL_KEY_PUB1);
		$this->assertFalse( $node->setSslKeyPub(static::SSL_KEY_PUB1) );
		
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		$this->assertEquals('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwMZra', $node->getSslKeyPubFingerprint());
		
		$this->assertTrue( (bool)$node->save() );
	}
	
	public function testSaveHttpnode(){
		$node = new Node('tests/test_node_http.yml');
		$node->setDatadirBasePath('tests');
		$node->setDataChanged(true);
		$node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$node->setUri('http://phpchat.fox21.at:8080/web/phpchat.php');
		#ve($node->getUri());
		
		$node->setSslKeyPub(static::SSL_KEY_PUB1);
		$this->assertFalse( $node->setSslKeyPub(static::SSL_KEY_PUB1) );
		
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		$this->assertEquals('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwMZra', $node->getSslKeyPubFingerprint());
		
		$this->assertTrue( (bool)$node->save() );
	}
	
	/**
	* @depends testSaveTcpnode
	*/
	public function testLoadTcpnode(){
		$node = new Node('tests/test_node_tcp.yml');
		$node->setDatadirBasePath('tests');
		
		$this->assertTrue($node->load());
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		ve($node->getUri());
		$this->assertEquals('tcp', $node->getUri()->getScheme());
		$this->assertEquals('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwMZra', $node->getSslKeyPubFingerprint());
		$this->assertEquals(static::SSL_KEY_PUB1, $node->getSslKeyPub());
	}
	
	/**
	* @depends testSaveHttpnode
	*/
	public function testLoadHttpnode(){
		$node = new Node('tests/test_node_http.yml');
		$node->setDatadirBasePath('tests');
		
		$this->assertTrue($node->load());
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $node->getIdHexStr());
		$this->assertEquals('http', $node->getUri()->getScheme());
		$this->assertEquals('FC_BtK4HvbdX9wNQ6hGopSrFxs71SuuwMZra', $node->getSslKeyPubFingerprint());
		$this->assertEquals(static::SSL_KEY_PUB1, $node->getSslKeyPub());
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
		$this->assertTrue($node->setSslKeyPub(static::SSL_KEY_PUB1));
		$this->assertFalse($node->setSslKeyPub(static::SSL_KEY_PUB1));
	}
	
	/**
	 * @expectedException RuntimeException
	 */
	public function testSetSslKeyPubRuntimeException(){
		$node = new Node();
		$node->setSslKeyPub('invalid');
	}
	
	public function testSslKey1(){
		$this->assertFalse(static::SSL_KEY_PUB2_A == static::SSL_KEY_PUB2_B);
	}
	
	public function testSslKey2(){
		$node = new Node();
		$node->setSslKeyPub(static::SSL_KEY_PUB2_A);
		
		$this->assertEquals('FC_5zk4NskvcrQdJJLYQFb4V6fai8bzMV82G', $node->getSslKeyPubFingerprint());
		$this->assertEquals(static::SSL_KEY_PUB2_A, $node->getSslKeyPub());
	}
	
	public function testSslKey3(){
		$node = new Node();
		$node->setSslKeyPub(static::SSL_KEY_PUB2_B);
		
		$this->assertEquals('FC_5zk4NskvcrQdJJLYQFb4V6fai8bzMV82G', $node->getSslKeyPubFingerprint());
		$this->assertEquals(static::SSL_KEY_PUB2_A, $node->getSslKeyPub());
	}
	
}
