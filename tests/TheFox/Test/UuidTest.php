<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;
use Zend\Uri\UriFactory;
use Zend\Uri\Http;
use TheFox\Dht\Kademlia\Node;
use TheFox\PhpChat\TcpUri;
use TheFox\PhpChat\HttpUri;

class UuidTest extends PHPUnit_Framework_TestCase{
	
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
	
	public function testId(){
		#$id = (string)Uuid::uuid4();
		#$id = (string)Uuid::uuid5(Uuid::NAMESPACE_DNS, 'php.net');
		
		$key = sslKeyPubClean(static::SSL_KEY_PUB1);
		$keyBin = base64_decode($key);
		
		$id = (string)Uuid::uuid5(Uuid::NAMESPACE_X500, $keyBin);
		$this->assertEquals('d4773c00-6a11-540a-b72c-ed106ef8309b', $id);
		
		$id = (string)Uuid::uuid5(Uuid::NAMESPACE_X500, static::SSL_KEY_PUB1);
		$this->assertEquals('91a3d7b5-28fe-52d1-a56d-b09093c63c84', $id);
		
		$id = (string)Uuid::uuid5(Uuid::NAMESPACE_X500, 'hello world');
		$this->assertEquals('dbd9b896-6d7c-5852-895c-ecc5735cf874', $id);
		
		$id = (string)Uuid::uuid5(Uuid::NAMESPACE_DNS, 'hello world');
		$this->assertEquals('823a2f73-a936-56c3-b8b4-03641bd74f35', $id);
		
		$id = (string)Uuid::uuid5(Uuid::NAMESPACE_X500, 'my_name');
		$this->assertEquals('045fe53e-72be-5a76-8f58-783aed5c99d5', $id);
		
		
		$this->assertTrue(Uuid::isValid('91a3d7b5-28fe-52d1-a56d-b09093c63c84'));
		$this->assertFalse(Uuid::isValid('x1a3d7b5-28fe-52d1-a56d-b09093c63c84'));
		
		$id = '00000000-0000-4000-8000-000000000000';
		$this->assertTrue(Uuid::isValid($id));
		
		$id = '00000000-0000-4000-8000-00000000000x';
		$this->assertFalse(Uuid::isValid($id));
		
		$id = '00000000-0000-4000-8000-0000000000';
		$this->assertFalse(Uuid::isValid($id));
		
		$id = '00000000-0000-0000-0000-000000000000';
		$this->assertTrue(Uuid::isValid($id));
		
		$id = 'badfood0-0000-4000-a000-000000000000';
		$this->assertFalse(Uuid::isValid($id));
		
		$id = 'cafed00d-2131-4159-8e11-0b4dbadb1738';
		$this->assertTrue(Uuid::isValid($id));
	}
	
}
