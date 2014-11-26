<?php

use Symfony\Component\Finder\Finder;
use Rhumsaa\Uuid\Uuid;

use TheFox\PhpChat\Msg;

class MsgTest extends PHPUnit_Framework_TestCase{
	
	const SRC1_SSL_KEY_PUB = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAxImO5o0WGuT2lqwArw4M
7MfTNeT242+ca94dFuM1JFHOvrFr5iJsauiK8HyUMzL+fFI54IDXjqpXAWK8dmoC
C6EF6iAq7X6Drq8WEosUOgWRnRh0UOHAXlFTRn+P1Epm1CItBOxliZOEHelhJCLt
IZx4f6N0EaaQDOGiSbOVQNL+c4GaXoF6yJJcE5WxXOuXp8NMk5V6ynNjql9cbYAR
d9jaBssHlRBkh+R2RzbX3AL7a1N5BmBQ6bf4LG4A5y+NMKKUjMkXgN7hPtehCvUf
XWObQzHP2wUiJOpv/2KJ83FntmXie61eZAjfrbt5emrA13i0DYDkvRCB835haFiw
JcnDvg1APkRHx5NbWY0LdJe0m/PEA2lf+deEjR07Pibhc83sg1vsxzMvkL3YJbCe
AcE1lIKSVVTl52RRHmsIRAmx1sEaKHA3asbJ9z7eIN/rNKCqd6220N4vxyf6SqTh
VF0P2lBRYJCiEIdGPF1JusbhJmUFjrKRGj9CZtMRw/dueSHl2ILTS7Mg7l3vZNkd
szn7uyYNc2pBpXgzZgB7KUN/d8GHejp9b+7rlnH7zWKCoEfjxEumnGkhOgNUjE2V
Qh67NDbSMJHUjW+jSgZWZQpWqyQgju5Z8Oav0RdbyDnWF4Erz22ieNEL5i12zHNw
/lGLPjHk/lF9CcbeIXl8/UsCAwEAAQ==
-----END PUBLIC KEY-----';
	const SRC1_SSL_KEY_PRV = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,D00A0FCDFC91ACF4

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
-----END RSA PRIVATE KEY-----';
	
	const DST1_SSL_KEY_PUB = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAtIIiZm70ZPEIOd+FIqr7
E7qav8+jNvI08LVhMSmOHE1s9WymLTChv2a10J8fhYY9ipIyc8WnCzN5Amtth9hK
LcZXi11Oi6n6+fBGyREoc9KQamu6ZQ9bVkJ1s4yVLzVF9k3JHyMO4GgdlJ7lZTlf
5GDOffj/KuLmhalO0XzOW49Eng9jV7RK/88iNbJwGm2Mn/66rn1fiEnMXz6kuvNX
785pOvPJH0wzXIBQsZ9m+zURJsbc979peMHrno85Y+ZmVQdwXXRaZcO0QozltULD
4r+1R8raYSH4Nwm5YyMuRONNuyCL9a6Q/AmVGqxcvz7IDKjizptEF1BE2ko9OAmr
NyPaxwK89JPRNXkxN/AnlojFzIg/8MES2O+rPMK5izLxqo8nNVmeCmhfwi0NwMkq
aJcenHXLPG+Hz5cov5cbpwzXIe5nxS4PSpkr+oqpTLWUqAz5hbjTZ96pMuo6huCF
StRMjmmfNXb47TeLhFS+OlCQlLBHwvXaHGl3wZ7f7eumUfhy3AOx5/8tsuUeItfp
CoEHq/SeWAUt4rxD/HW6gRf/cdY0GhbLgqwTIs8keft7BHQokwOvTI/o1sNFEGh1
cL7QNOI5Cv1SHZ0j85N1XmuHuIldXrjYkFRDJXifqgofMzM8M6J+0f74iupQLNwx
X7E/kioxMTLuoxs1R1+aatsCAwEAAQ==
-----END PUBLIC KEY-----';
	const DST1_SSL_KEY_PRV = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,4A9A531726EA796C

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
-----END RSA PRIVATE KEY-----';
	
	const DST2_SSL_KEY_PUB = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAqlKjbOBP9v01t7MnXSwU
siJ1Lo0nw9O9pAEiz1/ZMXpMdaDBL98eWMQ0yf8sgcW+LGCfKM2D5E4ZrFsS06B2
hR7gr9jbeV1zlMPmy03dnwZfR6wRsgfgtrZEMunojui/4T/UkUlQGCHG6h2R4gs8
tc7iMv4GJV4Kk/gfguYQlHUaPg+f81jrv76/2n4WKd2Ha86xDpN0JSlkID9MAAQn
a1HW/tqMUduyLVXfFQXMtLIkG5hKkNXM7j3UXm6lqRUupY7Iputu8/D4IdBp5pPr
TzZZAu0Tj9iIp+pDPS5ucMIKyEsVqm4nf7oU1F6xWs7qGPmLQmY+ragXqrifRP0V
UVWivDA7y9VQaXOc+3jCtyaOoiPcX0Kw002PJ3of96x9yZbi4XvL6irkxJ3Z5Zi2
Ak94sPplRdEH7R3j6I1jpwvGQJLelfhITRzAb7DLA201bRB0kp434j28YqB0k9iL
jjhiBmBBc8WvoHP3dZ+iNlrjuQWWuhbkAzzypz1CtBRYgUNWbo0IhWA+W1oVkx1F
yQVCnIjpfitNKSwyKb9McxlrtMRr/QBjsG3HTnnZVfywoqgTv6eP1ZztDL6HatJ+
jm8XLC+SivcAVH0FtuJW17mWFhvp4I1NFUy0Xt+w1vZISEREgqKmKkagHrQkwYF/
JVDruFuvYBk+YUAlWkQ3+VcCAwEAAQ==
-----END PUBLIC KEY-----';
	const DST2_SSL_KEY_PRV = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,1601B57F6C552B56

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
-----END RSA PRIVATE KEY-----';
	
	const SSL_KEY_PRV_PASS = 'test';
	
	public function testSerialize(){
		$msg1 = new Msg();
		$msg1->setId('3d939e1c-9ac6-473c-a00d-4e96014821f9');
		
		$msg2 = unserialize(serialize($msg1));
		
		$this->assertEquals('3d939e1c-9ac6-473c-a00d-4e96014821f9', $msg2->getId());
	}
	
	public function testToString(){
		$msg = new Msg();
		$msg->setId('3d939e1c-9ac6-473c-a00d-4e96014821f9');
		$this->assertEquals('TheFox\PhpChat\Msg->{3d939e1c-9ac6-473c-a00d-4e96014821f9}', (string)$msg);
	}
	
	public function testSaveLoad(){
		$runName = uniqid('', true);
		$fileName = 'testfile_msg_'.date('Ymd_His').'_'.$runName.'.yml';
		
		$msg = new Msg('test_data/'.$fileName);
		$msg->setDatadirBasePath('test_data');
		$msg->setDataChanged(true);
		
		$msg->setVersion(21);
		$this->assertEquals(21, $msg->getVersion());
		
		$msg->setId('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $msg->getId());
		
		$msg->setRelayNodeId('cafed00d-2231-4159-8e11-0b4dbadb1738');
		$this->assertEquals('cafed00d-2231-4159-8e11-0b4dbadb1738', $msg->getRelayNodeId());
		
		$msg->setSrcNodeId('cafed00d-2331-4159-8e11-0b4dbadb1738');
		$this->assertEquals('cafed00d-2331-4159-8e11-0b4dbadb1738', $msg->getSrcNodeId());
		
		$msg->setSrcSslKeyPub(static::SRC1_SSL_KEY_PUB);
		$this->assertEquals(static::SRC1_SSL_KEY_PUB, $msg->getSrcSslKeyPub());
		
		$msg->setSrcUserNickname('thefox');
		$this->assertEquals('thefox', $msg->getSrcUserNickname());
		
		$msg->setDstNodeId('cafed00d-2431-4159-8e11-0b4dbadb1738');
		$this->assertEquals('cafed00d-2431-4159-8e11-0b4dbadb1738', $msg->getDstNodeId());
		
		$msg->setDstSslPubKey(static::DST1_SSL_KEY_PUB);
		$this->assertEquals(static::DST1_SSL_KEY_PUB, $msg->getDstSslPubKey());
		
		$msg->setSubject('my first subject');
		$this->assertEquals('my first subject', $msg->getSubject());
		
		$msg->setText('hello world! this is a test');
		$this->assertEquals('hello world! this is a test', $msg->getText());
		
		$msg->setPassword('my_password01');
		$this->assertEquals('my_password01', $msg->getPassword());
		
		$msg->setChecksum('checksuuuum_sum');
		$this->assertEquals('checksuuuum_sum', $msg->getChecksum());
		
		$msg->setSentNodes(array(21, 2, 1987));
		$msg->addSentNode(42);
		$this->assertEquals(array(21, 2, 1987, 42), $msg->getSentNodes());
		
		$msg->setRelayCount(22);
		$this->assertEquals(22, $msg->getRelayCount());
		
		$msg->setForwardCycles(23);
		$msg->incForwardCycles();
		$this->assertEquals(24, $msg->getForwardCycles());
		
		$msg->setEncryptionMode('D');
		$this->assertEquals('D', $msg->getEncryptionMode());
		
		$msg->setStatus('O');
		$this->assertEquals('O', $msg->getStatus());
		
		$msg->setTimeCreated(679874400);
		$this->assertEquals(679874400, $msg->getTimeCreated());
		
		
		$msg->setSslKeyPrv(static::SRC1_SSL_KEY_PRV, static::SSL_KEY_PRV_PASS);
		$this->assertTrue( $msg->encrypt() );
		
		$this->assertTrue( (bool)$msg->save() );
		
		$finder = new Finder();
		$files = $finder->in('test_data')->depth(0)->name($fileName)->files();
		$this->assertEquals(1, count($files));
		
		
		$msg = new Msg('test_data/'.$fileName);
		$msg->setDatadirBasePath('test_data');
		
		$this->assertTrue($msg->load());
		
		$this->assertEquals(21, $msg->getVersion());
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $msg->getId());
		$this->assertEquals('cafed00d-2231-4159-8e11-0b4dbadb1738', $msg->getRelayNodeId());
		$this->assertEquals('cafed00d-2331-4159-8e11-0b4dbadb1738', $msg->getSrcNodeId());
		$this->assertEquals(static::SRC1_SSL_KEY_PUB, $msg->getSrcSslKeyPub());
		$this->assertEquals('cafed00d-2431-4159-8e11-0b4dbadb1738', $msg->getDstNodeId());
		$this->assertEquals(array(21, 2, 1987, 42), $msg->getSentNodes());
		$this->assertEquals(22, $msg->getRelayCount());
		$this->assertEquals(24, $msg->getForwardCycles());
		$this->assertEquals('D', $msg->getEncryptionMode());
		$this->assertEquals('O', $msg->getStatus());
		$this->assertEquals(679874400, $msg->getTimeCreated());
		
		
		$msg = new Msg('test_data/'.$fileName);
		$msg->setDatadirBasePath('test_data');
		
		$this->assertTrue($msg->load());
		
		$msg->setDstSslPubKey(static::DST1_SSL_KEY_PUB);
		$msg->setSslKeyPrv(static::DST1_SSL_KEY_PRV, static::SSL_KEY_PRV_PASS);
		
		$subject = 'N/A';
		$text = 'N/A';
		try{
			$text = $msg->decrypt();
			$subject = $msg->getSubject();
		}
		catch(Exception $e){
			$text = $e->getMessage();
		}
		
		$this->assertEquals('my first subject', $subject);
		$this->assertEquals('hello world! this is a test', $text);
		$this->assertEquals('thefox', $msg->getSrcUserNickname());
		
		
		$msg = new Msg('test_data/'.$fileName);
		$msg->setDatadirBasePath('test_data');
		
		$this->assertTrue($msg->load());
		
		$msg->setDstSslPubKey(static::DST2_SSL_KEY_PUB);
		$msg->setSslKeyPrv(static::DST2_SSL_KEY_PRV, static::SSL_KEY_PRV_PASS);
		
		$subject = 'N/A';
		$text = 'N/A';
		try{
			$text = $msg->decrypt();
			$subject = $msg->getSubject();
		}
		catch(Exception $e){
			$text = 'FAILED OK';
		}
		
		$this->assertEquals('N/A', $subject);
		$this->assertEquals('FAILED OK', $text);
		$this->assertEquals('', $msg->getSrcUserNickname());
		
		
		$msg = new Msg('test_data/not_existing.yml');
		$this->assertFalse($msg->load());
	}
	
	public function testId(){
		$msg = new Msg();
		
		$this->assertTrue(Uuid::isValid($msg->getId()));
	}
	
	public function testStatus(){
		$msg = new Msg();
		
		$msg->setStatus('R');
		$this->assertEquals('R', $msg->getStatus());
		
		$msg->setStatus('D');
		$msg->setStatus('U');
		$this->assertEquals('D', $msg->getStatus());
	}
	
	public function testChecksum1(){
		$version = 1;
		$id = 'cafed00d-2131-4159-8e11-0b4dbadb1738';
		$srcNodeId = 'cafed00d-2331-4159-8e11-0b4dbadb1738';
		$dstNodeId = 'cafed00d-2431-4159-8e11-0b4dbadb1738';
		$dstSslPubKey = static::DST1_SSL_KEY_PUB;
		$text = 'hello world! this is a test';
		$timeCreated = '1407137420';
		$password = 'tt9M/WdvXyChAWthKDFaP/tUAG6bZsdalTOxrFNsYX+4NgTNQ7iNCUng0jDPNzoMOYVu';
		$password .= 'DdV/ZVnja5pamipawuw71wyIa6vDGoJKJ1yOUbVkH9YO34gZTRVz6MfZu2BQ680YIJo';
		$password .= 'u5J3aPMTcet5jYU2b2ffJSPkYqaEmV2DzLQr/M0bGn3rHml4OovKgX9m1vN7XlTQL+E';
		$password .= 'wW5MCLqPYsethgoKahKh2O17oZ6VDGVa/b2P4KzM3d41NzUXz/s31Bce+blR2o6oM+n';
		$password .= 'KIbXNoxs9dZbbCSqDzLk8AZ1+dGI2ZX7hovL+XSv0Ta7S0lgEf44zwDttGvdWpIaFvW+uL70w==';
		$checksum = Msg::createCheckSum($version, $id, $srcNodeId, $dstNodeId, $dstSslPubKey, $text, $timeCreated, $password);
		
		$this->assertEquals('7c4459a9bc0ec4b19ebae6d9ded536aa6ee55ba13552dc81', $checksum);
	}
	
	public function testChecksum2(){
		$version = 1;
		$id = 'cafed00d-2131-4159-8e11-0b4dbadb1738';
		$srcNodeId = 'cafed00d-2331-4159-8e11-0b4dbadb1738';
		$dstNodeId = 'cafed00d-2531-4159-8e11-0b4dbadb1738';
		$dstSslPubKey = static::DST1_SSL_KEY_PUB;
		$text = 'hello world!';
		$timeCreated = '540892800';
		$password = 'password1';
		$checksum = Msg::createCheckSum($version, $id, $srcNodeId, $dstNodeId, $dstSslPubKey, $text, $timeCreated, $password);
		
		$this->assertEquals('1c870e54257e6eb594724508a0a9c616b1905c2aed25de8a', $checksum);
	}
	
	public function providerEncryption(){
		$rv = array();
		
		$rv[] = array('thefox', 'another subject', 'hello world! this is a test', false);
		$rv[] = array('thefox21', 'hello again', 'hello world! this is a test2', true);
		
		return $rv;
	}
	
	/**
     * @dataProvider providerEncryption
     */
	public function testEncryption($srcUserNickname, $subject, $text, $ignore){
		$msg = new Msg();
		
		$msg->setVersion(1);
		$msg->setId('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$msg->setSrcNodeId('cafed00d-2331-4159-8e11-0b4dbadb1738');
		$msg->setSrcSslKeyPub(static::SRC1_SSL_KEY_PUB);
		$msg->setSrcUserNickname($srcUserNickname);
		$msg->setDstNodeId('cafed00d-2431-4159-8e11-0b4dbadb1738');
		$msg->setDstSslPubKey(static::DST1_SSL_KEY_PUB);
		$msg->setSubject($subject);
		$msg->setText($text);
		$msg->setSslKeyPrv(static::SRC1_SSL_KEY_PRV, static::SSL_KEY_PRV_PASS);
		$msg->setIgnore($ignore);
		
		$this->assertTrue( $msg->encrypt() );
		$body = $msg->getBody();
		$timeCreated = $msg->getTimeCreated();
		$password = $msg->getPassword();
		$checksum = $msg->getChecksum();
		
		
		$msg = new Msg();
		$msg->setVersion(1);
		$msg->setId('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$msg->setSrcNodeId('cafed00d-2331-4159-8e11-0b4dbadb1738');
		$msg->setBody($body);
		$msg->setSrcSslKeyPub(static::SRC1_SSL_KEY_PUB);
		$msg->setDstSslPubKey(static::DST1_SSL_KEY_PUB);
		$msg->setSslKeyPrv(static::DST1_SSL_KEY_PRV, static::SSL_KEY_PRV_PASS);
		$msg->setDstNodeId('cafed00d-2431-4159-8e11-0b4dbadb1738');
		$msg->setTimeCreated($timeCreated);
		$msg->setPassword($password);
		$msg->setChecksum($checksum);
		
		$textDecrypted = $msg->decrypt();
		$this->assertEquals($subject, $msg->getSubject());
		$this->assertEquals($text, $textDecrypted);
		$this->assertEquals($text, $msg->getText());
		$this->assertEquals($srcUserNickname, $msg->getSrcUserNickname());
		$this->assertEquals($ignore, $msg->getIgnore());
	}
	
}
