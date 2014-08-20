<?php

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Rhumsaa\Uuid\Uuid;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
use TheFox\PhpChat\Settings;
use TheFox\PhpChat\Kernel;
use TheFox\PhpChat\Client;
use TheFox\PhpChat\TcpClient;
use TheFox\PhpChat\ClientAction;
use TheFox\PhpChat\Msg;
use TheFox\Dht\Kademlia\Table;
use TheFox\Dht\Kademlia\Bucket;
use TheFox\Dht\Kademlia\Node;

class TcpClientTest extends PHPUnit_Framework_TestCase{
	
	const NODE_LOCAL_SSL_KEY_PRV1 = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,F1ED7B6E6F0D5E72

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
-----END RSA PRIVATE KEY-----
';
	const NODE_LOCAL_SSL_KEY_PUB1 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAxDKXwrrDMT5vTWwdwsjv
wZ45BxKs85QGeRL3ZtwnpqLH7iPNXmIe9YD7/NpancDnqKsxSE85RZWCdazWwWtK
Y4mXgCCPx4LUomixp47DO3FltU++L52kPUFB6B7vXOAG6OnCpNRUKk4ZHXkcb4B7
4Aews8t9dHplTLWuwLjNNQBe/OcZbpeVSd4lxqWNOIsErq3BA1tRU4to41TDZKev
6ll3SESRNKjXSrVCITIxxBEgXje0KjJ6chvQ3TY4Q7eS54ZScEYUTrNIdyFyLf1X
2pjln9+7BU0wiwji2fF5gF+vazTtqJx9fI2O68mC6pGc4MKd3W4oIyw0RtvaVYA/
16bzHVpDqdJJaBY+qP1sgKvdB8PCja8AvoHsWrbiwuUAeSzopZrXryZJg4jSnUoQ
Um6IIggkGmTw4bylSMe8qjxtm9Mt2V7bGo9rc2dO2N376oWHLI9fYSvs4Go1MLfn
rKTvtTQofjF+d8BBhba0Wdyxm2mgv6Bdctg+W/J9M+TOSdJTSWT+pls9uWzq3KNU
7l+LZ2vrUpbxlN95j47a3KoLsBnfOulKfxpoWq0Mf6xrNyKZki/qweoNstFGlQeV
UruU45tOsyDv/NyxlaaUu/OAu5bx6MxLzKB88CBCTMy2TAxz4AoFa73On5uUcIAN
/okytY8F9ZXwo6LuIet1xl8CAwEAAQ==
-----END PUBLIC KEY-----
';
	const NODE_LOCAL_SSL_KEY_PRV2 = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,15B5AB15B19103A4

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
-----END RSA PRIVATE KEY-----
';
	const NODE_LOCAL_SSL_KEY_PUB2 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAvAyZWe+VL1AfzK8ciwBQ
Pq2JCzDxFWz7DhicP3ukaY3q5R3fxS46pfZRNTgCRUuQJ0UHsExo35cLK3EhcgCb
2apoz+ZmMYbIABJymKhaKNMWSPNkpcCiEYizf9ee5CxKW+Cls/53jGMwOxLRxahs
Z33yFBgEV9qJKvRKxs8YeDAR0o482/qaunBql06oZ6Wqg/iKuGz7vi9p+lt/msGt
ijEFOE/h+VblGntpAzJkVK+SIwm7dpaFWwBJ/CpzW8H6kYTfm5vPg1cdp3fnHUAY
x0l7p3n6srn7J2aq64l0b/YjlWbcwC0Wu0952egAagbIYLu3JJFmTCJdI8E7ckjg
Rsg8ldZ55KTrx8HXHIzJY9ab+bqnemX+ZzZUnsRXGoA8ujdQA+rGRG+TyF5+d6IH
V7PU+rFE8krm9bmwyWmLMZYd3PpxdswgmKrgWmTsRc/pnPMqv5lj4xKfsgA7RjnX
rX9VgXeqXyrMrcAO1x3w5dDyZci0pQF/J0vZ7ThLUWPLPTw5QRsKBzlIp14dZGbN
c2CifgR03fYzMXSuUDKyJwF/aVehTq4kkww9vsRjdbudg27RF/FxKA3QC8RZhVr+
mS9G8S/HSmd2F2SMyI8tdijophvw0g0pZcWj4KSwRmH9bQ9oYMRViO6dhn0LaFgz
eruZB1Vdgq1HiHqmuF/cP0ECAwEAAQ==
-----END PUBLIC KEY-----
';
	
	const NODE_LOCAL_SSL_KEY_PRV3 = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,218E8574D5B151B3

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
-----END RSA PRIVATE KEY-----
';
	const NODE_LOCAL_SSL_KEY_PUB3 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEArNdfCOGsNSwO5+1gCYtQ
K8QpVVPSCwodhz5Wl8JY/ojuJmLPDjcR1b5WxTrXZ30UouMIxJeiYN3/NgqAYO+K
nYqhBH9bZrHWRgwtlcJBHjX0+xwP6vqkQLVzKVoTX0JXGj1rI3pBdO/QpAbgQFw0
adjPSJf3pA9F/c4UQon2UMk2IhH49hrbWBcXJEJ+PrVpW1M8iOVM+5+6LzMCy6Bf
0q2BcmbyvB8sqYP4sj6XjRnY0yUsBFB4DZrIIaVE6fUkcTvA/2UChJxNpbenha8r
OYyhoYD+fsGRErgkSP1fc1InrX9ns3Q09/ats3oyehGdmOuY9SypZoq2t7ZsQqWC
tBjyyWckwlhEQW1GiEsfsRAHWFQaepVsq7HPuz4dLQY7QvwyFkby6zVmvR2x/TGw
VUAjk42rseVckoWCEOsPHiIRLP/BsG3RutKMvkGbdM8Vhse+IFtXTBC0gOEr8RGG
B4O4LKWRKC5ciynFJjUWvULspbSxxXIHxk6Y/DTONCvwZUkB69UphhhA852CFfVy
cNciHJLzM7Wns1iFPRQHAl1JTIeUcbsGfm/IrqRm87vUMyLbV00PeZEeKF2JI/Nb
by2YU2Mm9gTatj5TJEj8Dj5QtY9p7NgbQG70BHfTxl39GF4nqC+bDMn8CF9sW/5O
rP8NyKCpywlwexQ48Jtz3F8CAwEAAQ==
-----END PUBLIC KEY-----
';
	
	const NODE_LOCAL_SSL_KEY_PRV4 = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,3EC14CEE16D77897

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
-----END RSA PRIVATE KEY-----
';
	const NODE_LOCAL_SSL_KEY_PUB4 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAo27jeopOvaC9mOhAm+ab
oKOhtG/8DVCDe4yRShiz+jx/VwAC15Nh1zHZWI2B4Hc6i4GGtRTGqiXfhG9H0/ks
PMAf2Doi3RtvqEcJv98Lir7/fViWXl4VttATTwqo6ZgUS44+5IRomKP3juXy93cl
VsqPp0OTa0Z57vnn3NDncrtbRdmzCWtPM9cyx6ChH63FkPcJDlnD8Vkm4JxAExSJ
vBUeZl+X2rFJDEZVJEm+otKWDUXGHjqYmS1ZQfZQGnpmVrLLOeHQvhnxeKyxtV8P
58tT5U1D+kekk/slxRF+tHaWN1xxsC9QG0WDj98ZtqX0jniyg3mHT+++OKIDiG8J
fChCehI6iEdgQ5eejZjCDN98M/uuwv7EHlpvL5l7fWOYUeM9l/tN1m8oF/StuqXs
FDF0zKiJrNfaok48lXyiYcedYqhXGWTvrflZFLh7c2VG9H+6l7zm5zUxxS99ZZIW
zYKc/NRKL37hBpb1PqGuzfVjiJZuJSY7zW+IXu7gVBYYg05l8kFyDRd5Z6JXkhO7
SSduOxjNYuAZFbf+rJZi4nvdpaeaJs0GUlgWde6JBrkP5CYD9km9tlZJknlQr3Mv
3+x0fhWGdVJK8kVIRvjFSyWbafONUQmABFYxSVhw/UlSLwRWnQhl1ZVsOdldxYkq
zskDiXdx3y7W/4Htp9j6SD0CAwEAAQ==
-----END PUBLIC KEY-----
';
	
	const NODE_LOCAL_SSL_KEY_PRV5 = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,648012F6C8A84BC1

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
-----END RSA PRIVATE KEY-----
';
	const NODE_LOCAL_SSL_KEY_PUB5 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA1TaWyMlW6/CwjJser4Wi
/mxnHmnSX+ZD+ixiNW8JNOATcBP2jGW/91tB88l7eglDSpCk+F26C015PQs6Ckve
Yb0fTW3B8XZ1RMfaVzRsT7gGacOu64EHpL4OB2J+jD90y3+7KN8jCBNvixXE+EsN
YZiVMY2MJaAgE58hAW609emTN2WTms4vnLBTGsx3IZ4T24TVbSpgXNBDIV7wNQDh
2gCng+CHAWkyotpCwarh+z3sMk2ogBxN3yR1PaSTg0BmZyZMeMS+jdCvhpC4J+xT
JnpdAWopD98m+J+n4HnIFfeC/AOGtLzxa8H8Xb8c000pww1dQRV/HZENJFrFQ/2h
8no+7p4d4zGMklrPe+lMc5O+QvUgx6F9vLxKZEwXp/93Mqy2gO4mb3aBzOJe8wLH
Dl+XArTtkQWdI4UA8qhZm2q3aS63lYvu21Bxb6xrdhiKGxRYDgG8oMY7sOC4bfNT
HSO+CGkNuTKXVv1D1CqV62f+VFW88WVb5n/0I2+X7rou8NgBYEGj7+e2vx8nET3I
aCym1PZLFQhkI2wyZIt46Vgf2yVCdUSmNdX7IMFQUK22AFy82+Lq7jBynw8eNpyK
SFBNDssxI9wK76cJd8Vk1FNMhpGCuGb4/wlHWndz55LqmtQMRXijk5KhdLanROX4
QfZblZnpC5QbcMmBenRqcuECAwEAAQ==
-----END PUBLIC KEY-----
';
	
	const NODE_LOCAL_SSL_KEY_PRV6 = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,F3DD41126EB0B369

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
-----END RSA PRIVATE KEY-----
';
	const NODE_LOCAL_SSL_KEY_PUB6 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAt6AGAhYwZPRmEkGsIoFl
bbUnzg2hTeH8UjVuw90TdxdHwBWP8Z5OpbkPy5mk0tQplcjRI9f2EyKjEfRwaZv5
iljq8fR+sE9b0nFRXjTA49AmXlJVOuYc/TuWhWEN8aTFonU2+PVIUUyGyk6wdf+P
A7FHxbFf5rElD7e2HMilPfPRJEAEittLXAA4XiVTXt0nP8ua2JECSPoXjRIKgbC0
6f63oF/pXl3NZ7tH4/hCZp0ymY1jdOqz8cqYhX8zwBz5voj6i53C6W8OmLypuzfZ
bwyJm76NxnPCUDvLjLdCcIyA6BuLEiLJ5WFjw5fGc9ymwURVJxFAzt9nFvlAxKuJ
tnW1CABL/pYpTVBcUPsEzdeyBIctJFMKLKDwHoAsq0YAAfnvv7Gjn+FyN7W+clPW
nGMQFFFqb9IoLOLBb0GkNeQFQmG6Nf4vp42VcNquYWhmXPe7kZg7pMRqtdVxJT++
uptUMKcmy+/rgWp73NcwJEJQWPyZF/n/E/wsZFfMFqpv9wIBNK+fH+Hax/cuKnmP
LAmMeEsNcgIA5gmiAY18MMYMJC7lwHIe8kJ/GJZ4J7wShD+BIpq+KGCw9TTZa1lq
OnU819AJVH6EacoY2kyl6BxrbObilkFwn6okCM9S1OxzeMgSH5ehTW2pG/LLJxNM
qFdLCnlGsFNPrOqpUoKmudkCAwEAAQ==
-----END PUBLIC KEY-----
';
	
	public function testSerialize(){
		$node = new Node();
		$node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		
		$client = new TcpClient();
		$client->setId(21);
		$client->setUri('tcp://127.0.0.1:25000');
		$client->setNode($node);
		
		$client = unserialize(serialize($client));
		#ve($client);
		
		$this->assertEquals(21, $client->getId());
		$this->assertEquals('tcp://127.0.0.1:25000', (string)$client->getUri());
		$this->assertEquals($node, $client->getNode());
	}
	
	public function rawMsgToArray($raw){
		$ar = array();
		
		if(substr($raw, -Client::MSG_SEPARATOR_LEN) == Client::MSG_SEPARATOR){
			$raw = substr($raw, 0, -Client::MSG_SEPARATOR_LEN);
		}
		
		$ar = array_map(function($item){
			return base64_decode($item);
		}, preg_split('/'.Client::MSG_SEPARATOR.'/', $raw));
		
		/*
		foreach($raw as $msg){
			$msg = substr($msg, 0, -Client::MSG_SEPARATOR_LEN);
			$msg = base64_decode($msg);
			#fwrite(STDOUT, 'rawMsgToArray msg: '.$msg."\n");
			$ar[] = $msg;
		}
		*/
		
		#fwrite(STDOUT, 'rawMsgToArray'."\n"); ve($ar);
		return $ar;
	}
	
	public function rawMsgToJson($raw){
		#ve($raw);
		$ar = $this->rawMsgToArray($raw);
		
		#fwrite(STDOUT, 'rawMsgToJson'."\n"); ve($ar);
		$rv = array_map(function($item){
			#fwrite(STDOUT, 'rawMsgToJson item: /'.$item.'/'."\n");
			return json_decode($item, true);
		}, $ar);
		
		#fwrite(STDOUT, 'rawMsgToJson'."\n"); ve($rv);
		return $rv;
	}
	
	private function sendGenTestData(){
		$filesystem = new Filesystem();
		$filesystem->mkdir('tests/client1_tcp', $mode = 0777);
		$filesystem->mkdir('tests/client2_tcp', $mode = 0777);
		
		file_put_contents('tests/client1_tcp/id_rsa.prv', static::NODE_LOCAL_SSL_KEY_PRV1);
		file_put_contents('tests/client1_tcp/id_rsa.pub', static::NODE_LOCAL_SSL_KEY_PUB1);
		
		file_put_contents('tests/client2_tcp/id_rsa.prv', static::NODE_LOCAL_SSL_KEY_PRV2);
		file_put_contents('tests/client2_tcp/id_rsa.pub', static::NODE_LOCAL_SSL_KEY_PUB2);
		
		
		$localNode1 = new Node();
		$localNode1->setUri('tcp://127.0.0.1:25000');
		$localNode1->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		
		$table1 = new Table('tests/client1_tcp/table.yml');
		$table1->setDatadirBasePath('tests/client1_tcp');
		$table1->setLocalNode($localNode1);
		for($n = 0; $n < 5; $n++){
			$node = new Node();
			$node->setUri('tcp://192.168.241.'.$n);
			$node->setIdHexStr('10000000-1000-4000-8000-1'.sprintf('%011d', $n));
			$table1->nodeEnclose($node);
			#fwrite(STDOUT, 'node: /'.$node->getIdHexStr().'/ '.$node->getIpPort()."\n");
		}
		$table1->setDataChanged(true);
		$table1->save();
		
		
		$localNode2 = new Node();
		$localNode1->setUri('tcp://127.0.0.2:25000');
		$localNode2->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1739');
		
		$table2 = new Table('tests/client2_tcp/table.yml');
		$table2->setDatadirBasePath('tests/client2_tcp');
		$table2->setLocalNode($localNode2);
		for($n = 5; $n < 10; $n++){
			$node = new Node();
			$node->setUri('tcp://192.168.241.'.$n);
			$node->setIdHexStr('10000000-1000-4000-8000-1'.sprintf('%011d', $n));
			$table2->nodeEnclose($node);
			#fwrite(STDOUT, 'node: /'.$node->getIdHexStr().'/ '.$node->getIpPort()."\n");
		}
		$table2->setDataChanged(true);
		$table2->save();
		
		
		$settings1 = new Settings();
		$settings1->data['datadir'] = 'tests/client1_tcp';
		$settings1->data['firstRun'] = false;
		$settings1->data['node']['timeCreated'] = time();
		$settings1->data['node']['ip'] = '127.0.0.1';
		$settings1->data['node']['port'] = 0;
		$settings1->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB1);
		$settings1->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings1->data['node']['sslKeyPrvPath'] = 'tests/client1_tcp/id_rsa.prv';
		$settings1->data['node']['sslKeyPubPath'] = 'tests/client1_tcp/id_rsa.pub';
		$settings1->data['user']['nickname'] = 'user1';
		
		$settings2 = new Settings();
		$settings2->data['datadir'] = 'tests/client2_tcp';
		$settings2->data['firstRun'] = false;
		$settings2->data['node']['timeCreated'] = time();
		$settings2->data['node']['ip'] = '127.0.0.2';
		$settings2->data['node']['port'] = 0;
		$settings2->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB2);
		$settings2->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings2->data['node']['sslKeyPrvPath'] = 'tests/client2_tcp/id_rsa.prv';
		$settings2->data['node']['sslKeyPubPath'] = 'tests/client2_tcp/id_rsa.pub';
		$settings2->data['user']['nickname'] = 'user2';
		
		
		$log1 = new Logger('client_1');
		$log1->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$log2 = new Logger('client_2');
		$log2->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$kernel1 = new Kernel();
		$kernel1->setLog($log1);
		$kernel1->setSettings($settings1);
		$kernel1->init();
		
		$kernel2 = new Kernel();
		$kernel2->setLog($log2);
		$kernel2->setSettings($settings2);
		$kernel2->init();
		
		$server1 = $kernel1->getServer();
		$server1->setLog($log1);
		$server2 = $kernel2->getServer();
		$server2->setLog($log2);
		
		$client1 = new TcpClient();
		$client1->setSslPrv($settings1->data['node']['sslKeyPrvPath'], $settings1->data['node']['sslKeyPrvPass']);
		$client1->setId(1);
		$client1->setUri('tcp://'.$settings1->data['node']['ip']);
		$client1->setServer($server1);
		$this->assertEquals($settings1->data['node']['ip'], $client1->getUri()->getHost());
		#fwrite(STDOUT, 'ip1: /'.$client1->getUri()->getHost().'/'."\n");
		
		$client2 = new TcpClient();
		$client2->setSslPrv($settings2->data['node']['sslKeyPrvPath'], $settings2->data['node']['sslKeyPrvPass']);
		$client2->setId(2);
		$client2->setUri('tcp://'.$settings2->data['node']['ip']);
		$client2->setServer($server2);
		$this->assertEquals($settings2->data['node']['ip'], $client2->getUri()->getHost());
		#fwrite(STDOUT, 'ip2: /'.$client2->getUri()->getHost().'/'."\n");
		
		return array($client1, $client2);
	}
	
	private function sendClientsId($client1, $client2){
		// Hello Client1
		$raw = $client1->sendHello();
		
		// ID Client1
		$raw = $client2->dataRecv($raw);
		$raw = $client1->dataRecv($raw);
		$raw = $client2->dataRecv($raw);
		
		// Hello Client2
		$raw = $client2->sendHello();
		
		// ID Client2
		$raw = $client1->dataRecv($raw);
		$raw = $client2->dataRecv($raw);
		$raw = $client1->dataRecv($raw);
	}
	
	public function testSendBasic(){
		list($client1, $client2) = $this->sendGenTestData();
		
		// Hello Client1
		$raw = $client1->sendHello();
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('hello', $json[0]['name']);
		$this->assertEquals('127.0.0.1', $json[0]['data']['ip']);
		
		// ID Client1
		$raw = $client2->dataRecv($raw);
		#ve($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('id', $json[0]['name']);
		$this->assertEquals('07fb5f61-5565-58f2-891e-1337e8b747ac', $json[0]['data']['id']);
		$this->assertTrue(array_key_exists('sslKeyPub', $json[0]['data']));
		$this->assertTrue(array_key_exists('sslKeyPubSign', $json[0]['data']));
		$this->assertFalse($json[0]['data']['isChannel']);
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('id_ok', $json[0]['name']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		$this->assertEquals(static::NODE_LOCAL_SSL_KEY_PUB2, $client1->getNode()->getSslKeyPub());
		
		
		// Hello Client2
		$raw = $client2->sendHello();
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('hello', $json[0]['name']);
		$this->assertEquals('127.0.0.2', $json[0]['data']['ip']);
		
		// ID Client2
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('id', $json[0]['name']);
		$this->assertEquals('264bfdaf-e558-5547-b4b2-a7c1ce75478c', $json[0]['data']['id']);
		$this->assertTrue(array_key_exists('sslKeyPub', $json[0]['data']));
		$this->assertTrue(array_key_exists('sslKeyPubSign', $json[0]['data']));
		$this->assertFalse($json[0]['data']['isChannel']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('id_ok', $json[0]['name']);
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		$this->assertEquals(static::NODE_LOCAL_SSL_KEY_PUB1, $client2->getNode()->getSslKeyPub());
		
		
		
		// re-ID should cause an error.
		$raw = $client1->sendId();
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(1010, $json[0]['data']['code']);
		
		
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendNodeFind(){
		list($client1, $client2) = $this->sendGenTestData();
		
		// Node Find before ID should cause an error.
		$raw = $client1->sendNodeFind($client1->getSettings()->data['node']['id']);
		#ve('testSend raw A'); ve($raw);
		$raw = $client2->dataRecv($raw);
		#ve('testSend raw B'); ve($raw);
		$json = $this->rawMsgToJson($raw);
		#ve('testSend json'); ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(1000, $json[0]['data']['code']);
		
		// Node Find before ID should cause an error.
		$raw = $client1->sendNodeFind($client1->getSettings()->data['node']['id']);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(1000, $json[0]['data']['code']);
		
		
		$this->sendClientsId($client1, $client2);
		
		
		// Node Find
		$raw = $client1->sendNodeFind($client1->getSettings()->data['node']['id']);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('node_find', $json[0]['name']);
		$this->assertEquals(8, $json[0]['data']['num']);
		$this->assertEquals('264bfdaf-e558-5547-b4b2-a7c1ce75478c', $json[0]['data']['nodeId']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('node_found', $json[0]['name']);
		$this->assertTrue(Uuid::isValid($json[0]['data']['rid']));
		$this->assertEquals('10000000-1000-4000-8000-100000000008', $json[0]['data']['nodes'][0]['id']);
		$this->assertEquals('10000000-1000-4000-8000-100000000009', $json[0]['data']['nodes'][1]['id']);
		$this->assertEquals('10000000-1000-4000-8000-100000000005', $json[0]['data']['nodes'][2]['id']);
		$this->assertEquals('10000000-1000-4000-8000-100000000006', $json[0]['data']['nodes'][3]['id']);
		$this->assertEquals('10000000-1000-4000-8000-100000000007', $json[0]['data']['nodes'][4]['id']);
		$this->assertEquals('tcp://192.168.241.8', $json[0]['data']['nodes'][0]['uri']);
		$this->assertEquals('tcp://192.168.241.9', $json[0]['data']['nodes'][1]['uri']);
		$this->assertEquals('tcp://192.168.241.5', $json[0]['data']['nodes'][2]['uri']);
		$this->assertEquals('tcp://192.168.241.6', $json[0]['data']['nodes'][3]['uri']);
		$this->assertEquals('tcp://192.168.241.7', $json[0]['data']['nodes'][4]['uri']);
		$this->assertTrue(array_key_exists('sslKeyPub', $json[0]['data']['nodes'][0]));
		$this->assertTrue(array_key_exists('sslKeyPub', $json[0]['data']['nodes'][1]));
		$this->assertTrue(array_key_exists('sslKeyPub', $json[0]['data']['nodes'][2]));
		$this->assertTrue(array_key_exists('sslKeyPub', $json[0]['data']['nodes'][3]));
		$this->assertTrue(array_key_exists('sslKeyPub', $json[0]['data']['nodes'][4]));
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		
		// Node Find without Hashcash should cause an error.
		$raw = $client1->sendNodeFind($client1->getSettings()->data['node']['id'], null, null, false);
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(4000, $json[0]['data']['code']);
		
		// Found Node with wrong RID should cause an error.
		$raw = $client1->sendNodeFound('wrong_rid', array(), false);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('node_found', $json[0]['name']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(9000, $json[0]['data']['code']);
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendMsg(){
		list($client1, $client2) = $this->sendGenTestData();
		
		// Send Msg before ID should cause an error.
		$msg = new Msg();
		$msg->setVersion(1);
		$msg->setSrcNodeId($client1->getSettings()->data['node']['id']);
		$msg->setSrcSslKeyPub(static::NODE_LOCAL_SSL_KEY_PUB1);
		$msg->setSrcUserNickname('thefox');
		$msg->setDstNodeId($client2->getSettings()->data['node']['id']);
		$msg->setDstSslPubKey(static::NODE_LOCAL_SSL_KEY_PUB2);
		$msg->setSubject('my first subject');
		$msg->setText('hello world! this is a test');
		$msg->setSslKeyPrv(static::NODE_LOCAL_SSL_KEY_PRV1, 'my_password');
		$msg->encrypt();
		
		$raw = $client1->sendMsg($msg);
		#ve($raw);
		$raw = $client2->dataRecv($raw);
		#ve($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(1000, $json[0]['data']['code']);
		
		
		$this->sendClientsId($client1, $client2);
		
		
		// Send Msg
		$msg = new Msg();
		$msg->setVersion(1);
		#$msg->setId('200b9758-2d34-4152-8ada-fc09fc9c9da0');
		$msg->setSrcNodeId('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$msg->setSrcSslKeyPub(static::NODE_LOCAL_SSL_KEY_PUB1);
		$msg->setSrcUserNickname('thefox');
		$msg->setDstNodeId('cafed00d-2131-4159-8e11-0b4dbadb1739');
		$msg->setDstSslPubKey(static::NODE_LOCAL_SSL_KEY_PUB2);
		$msg->setSubject('my first subject');
		$msg->setText('hello world! this is a test');
		$msg->setSslKeyPrv(static::NODE_LOCAL_SSL_KEY_PRV1, 'my_password');
		$msg->encrypt();
		
		$raw = $client1->sendMsg($msg);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$rid = $json[0]['data']['rid'];
		$this->assertEquals('msg', $json[0]['name']);
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $json[0]['data']['srcNodeId']);
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1739', $json[0]['data']['dstNodeId']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('msg_response', $json[0]['name']);
		$this->assertEquals($rid, $json[0]['data']['rid']);
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendSsl(){
		list($client1, $client2) = $this->sendGenTestData();
		
		// SSL before ID should cause an error.
		$raw = $client1->sendSslInit();
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_init_response', $json[0]['name']);
		$this->assertEquals(1000, $json[0]['data']['code']);
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(3100, $json[0]['data']['code']);
		
		
		$this->sendClientsId($client1, $client2);
		
		
		// SSL without Hashcash should cause an error.
		$raw = $client1->sendSslInit(false);
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_init_response', $json[0]['name']);
		$this->assertEquals(4000, $json[0]['data']['code']);
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(3100, $json[0]['data']['code']);
		
		
		
		
		// SSL
		$raw = $client1->sendSslInit();
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_init', $json[0]['name']);
		#return;
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_init', $json[0]['name']);
		$this->assertEquals('ssl_init_response', $json[1]['name']);
		#return;
		
		// SSL response
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_init_response', $json[0]['name']);
		$this->assertEquals('ssl_test', $json[1]['name']);
		#return;
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_test', $json[0]['name']);
		$this->assertEquals('ssl_verify', $json[1]['name']);
		#return;
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_verify', $json[0]['name']);
		$this->assertEquals('ssl_password_put', $json[1]['name']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_password_put', $json[0]['name']);
		$this->assertEquals('ssl_password_test', $json[1]['name']);
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_password_test', $json[0]['name']);
		$this->assertEquals('ssl_password_verify', $json[1]['name']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_password_verify', $json[0]['name']);
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendTalk(){
		list($client1, $client2) = $this->sendGenTestData();
		
		/*
		// Talk Request before ID should cause an error.
		$raw = $client1->sendTalkRequest('user1');
		#ve($raw);
		$raw = $client2->dataRecv($raw);
		#ve($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		
		// Talk Response before ID should cause an error.
		$raw = $client1->sendTalkResponse('rid1', 1, 'user1');
		#ve($raw);
		$raw = $client2->dataRecv($raw);
		#ve($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		
		// Talk Msg before ID should cause an error.
		$raw = $client1->sendTalkMsg('rid1', 'user1', 'hello world', false);
		#ve($raw);
		$raw = $client2->dataRecv($raw);
		#ve($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		
		// Talk User Nickname Change before ID should cause an error.
		$raw = $client1->sendTalkUserNicknameChange('user1', 'user1b');
		#ve($raw);
		$raw = $client2->dataRecv($raw);
		#ve($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		
		// Talk Close before ID should cause an error.
		$raw = $client1->sendTalkClose('rid1', 'user1b');
		#ve($raw);
		$raw = $client2->dataRecv($raw);
		#ve($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		*/
		
		# TODO
		#$this->sendClientsId($client1, $client2);
		#$this->sendClientsSsl($client1, $client2);
		
		// Talk Request
		$raw = $client1->sendTalkRequest('user1');
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('talk_request', $json[0]['name']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('talk_response', $json[0]['name']);
		$this->assertEquals('quit', $json[1]['name']);
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('quit', $json[0]['name']);
		
		// Talk Msg
		$raw = $client1->sendTalkMsg('de0bb575-cead-4ffe-adcb-311388511ed5', 'user1', 'hello world', false);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('talk_msg', $json[0]['name']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		
		// Talk User Nickname change
		$raw = $client1->sendTalkUserNicknameChange('user1', 'user1b');
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('talk_user_nickname_change', $json[0]['name']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		
		// Talk Close
		$raw = $client1->sendTalkClose('de0bb575-cead-4ffe-adcb-311388511ed6', 'user1b');
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('talk_close', $json[0]['name']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('quit', $json[0]['name']);
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendPingPong(){
		list($client1, $client2) = $this->sendGenTestData();
		
		// Ping - Pong
		$raw = $client1->sendPing();
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ping', $json[0]['name']);
		$this->assertEquals('', $json[0]['data']['id']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('pong', $json[0]['name']);
		$this->assertEquals('', $json[0]['data']['id']);
		
		$raw = $client1->sendPing('de0bb575-cead-4ffe-adcb-311388511ed7');
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ping', $json[0]['name']);
		$this->assertEquals('de0bb575-cead-4ffe-adcb-311388511ed7', $json[0]['data']['id']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		ve($json);
		$this->assertEquals('pong', $json[0]['name']);
		$this->assertEquals('de0bb575-cead-4ffe-adcb-311388511ed7', $json[0]['data']['id']);
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendNoop(){
		list($client1, $client2) = $this->sendGenTestData();
		
		// NoOp
		$raw = $client1->sendNoop();
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('noop', $json[0]['name']);
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendError(){
		list($client1, $client2) = $this->sendGenTestData();
		
		// Error
		$raw = $client1->sendError();
		$json = $this->rawMsgToJson($raw);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(9999, $json[0]['data']['code']);
		
		$errors = Client::getError();
		foreach($errors as $errorCode => $error){
			$raw = $client1->sendError($errorCode);
			$json = $this->rawMsgToJson($raw);
			#ve($json);
			$this->assertEquals('error', $json[0]['name']);
			$this->assertEquals($errorCode, $json[0]['data']['code']);
		}
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendUnknownCommand(){
		list($client1, $client2) = $this->sendGenTestData();
		
		// Unknown Command
		$raw = $client2->msgHandle('{"name":"blaaaaa"}');
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(9020, $json[0]['data']['code']);
		$this->assertEquals('blaaaaa', $json[0]['data']['name']);
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
}
