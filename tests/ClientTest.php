<?php

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;
use TheFox\PhpChat\Settings;
use TheFox\PhpChat\Kernel;
use TheFox\PhpChat\Client;
use TheFox\PhpChat\ClientAction;
use TheFox\PhpChat\Msg;
use TheFox\Dht\Kademlia\Table;
use TheFox\Dht\Kademlia\Bucket;
use TheFox\Dht\Kademlia\Node;

class ClientTest extends PHPUnit_Framework_TestCase{
	
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
	
	public function testSerialize(){
		$node = new Node();
		$node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		
		$client = new Client();
		$client->setId(21);
		$client->setUri('tcp://127.0.0.1:25000');
		$client->setNode($node);
		
		$client = unserialize(serialize($client));
		#ve($client);
		
		$this->assertEquals(21, $client->getId());
		$this->assertEquals('tcp://127.0.0.1:25000', (string)$client->getUri());
		$this->assertEquals($node, $client->getNode());
	}
	
	public function testBasic(){
		file_put_contents('tests/id_rsa_client1.prv', static::NODE_LOCAL_SSL_KEY_PRV1);
		file_put_contents('tests/id_rsa_client1.pub', static::NODE_LOCAL_SSL_KEY_PUB1);
		
		$settings = new Settings();
		$settings->data['datadir'] = 'tests';
		$settings->data['firstRun'] = false;
		$settings->data['timeCreated'] = time();
		$settings->data['node']['ip'] = '';
		$settings->data['node']['port'] = 0;
		$settings->data['node']['id'] = 'cafed00d-2131-4159-8e11-0b4dbadb1738';
		$settings->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings->data['node']['sslKeyPrvPath'] = 'tests/id_rsa_client1.prv';
		$settings->data['node']['sslKeyPubPath'] = 'tests/id_rsa_client1.pub';
		$settings->data['user']['nickname'] = 'user_'.substr(md5(time()), 0, 4);
		
		$log = new Logger('test_application');
		$log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$kernel = new Kernel();
		$kernel->setLog($log);
		$kernel->setSettings($settings);
		$kernel->init();
		
		$server = $kernel->getServer();
		$server->setLog($log);
		
		$client = new Client();
		$client->setSslPrv($settings->data['node']['sslKeyPrvPath'], $settings->data['node']['sslKeyPrvPass']);
		$client->setId(1);
		$client->setServer($server);
		
		$this->assertEquals($server->getLocalNode(), $client->getLocalNode());
		$this->assertEquals($server->getSettings(), $client->getSettings());
		$this->assertEquals($server->getLog(), $client->getLog());
		$this->assertEquals($server->getTable(), $client->getTable());
		$this->assertEquals($server->getMsgDb(), $client->getMsgDb());
		$this->assertEquals($server->getHashcashDb(), $client->getHashcashDb());
		
		$this->assertEquals($kernel->getLocalNode(), $server->getLocalNode());
		$this->assertEquals($kernel->getSettings(), $server->getSettings());
		#$this->assertEquals($kernel->getLog(), $server->getLog());
		$this->assertEquals($kernel->getTable(), $server->getTable());
		$this->assertEquals($kernel->getMsgDb(), $server->getMsgDb());
		$this->assertEquals($kernel->getHashcashDb(), $server->getHashcashDb());
	}
	
	public function testGetIpPort(){
		$client = new Client();
		
		$this->assertEquals('', (string)$client->getUri());
		
		$client->setUri('tcp://192.168.241.21:1987');
		$this->assertEquals('tcp://192.168.241.21:1987', (string)$client->getUri());
	}
	
	public function testHashcashMint(){
		@unlink('tests/hashcashdb.yml');
		
		file_put_contents('tests/id_rsa_client1.prv', static::NODE_LOCAL_SSL_KEY_PRV1);
		file_put_contents('tests/id_rsa_client1.pub', static::NODE_LOCAL_SSL_KEY_PUB1);
		
		$settings = new Settings();
		$settings->data['datadir'] = 'tests';
		$settings->data['firstRun'] = false;
		$settings->data['timeCreated'] = time();
		$settings->data['node']['ip'] = '';
		$settings->data['node']['port'] = 0;
		$settings->data['node']['id'] = 'cafed00d-2131-4159-8e11-0b4dbadb1738';
		$settings->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings->data['node']['sslKeyPrvPath'] = 'tests/id_rsa_client1.prv';
		$settings->data['node']['sslKeyPubPath'] = 'tests/id_rsa_client1.pub';
		$settings->data['user']['nickname'] = 'user_'.substr(md5(time()), 0, 4);
		
		$log = new Logger('test_application');
		$log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$kernel = new Kernel();
		$kernel->setLog($log);
		$kernel->setSettings($settings);
		$kernel->init();
		
		$server = $kernel->getServer();
		$server->setLog($log);
		
		$client = new Client();
		$client->setSslPrv($settings->data['node']['sslKeyPrvPath'], $settings->data['node']['sslKeyPrvPass']);
		$client->setId(1);
		$client->setServer($server);
		
		$hashcash = $client->hashcashMint(5);
		#fwrite(STDOUT, 'hashcash: /'.$hashcash.'/'."\n");
		
		$this->assertTrue($client->hashcashVerify($hashcash, 'cafed00d-2131-4159-8e11-0b4dbadb1738', 5));
		
		$kernel->shutdown();
		
		$finder = new Finder();
		$files = $finder->in('tests')->depth(0)->name('hashcashdb.yml')->files();
		foreach($files as $file){
			ve($file->getRealpath());
		}
		$this->assertEquals(1, count($files));
	}
	
	public function testRequest(){
		file_put_contents('tests/id_rsa_client1.prv', static::NODE_LOCAL_SSL_KEY_PRV1);
		file_put_contents('tests/id_rsa_client1.pub', static::NODE_LOCAL_SSL_KEY_PUB1);
		
		$settings = new Settings();
		$settings->data['datadir'] = 'tests';
		$settings->data['firstRun'] = false;
		$settings->data['timeCreated'] = time();
		$settings->data['node']['ip'] = '';
		$settings->data['node']['port'] = 0;
		$settings->data['node']['id'] = 'cafed00d-2131-4159-8e11-0b4dbadb1738';
		$settings->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings->data['node']['sslKeyPrvPath'] = 'tests/id_rsa_client1.prv';
		$settings->data['node']['sslKeyPubPath'] = 'tests/id_rsa_client1.pub';
		$settings->data['user']['nickname'] = 'user_'.substr(md5(time()), 0, 4);
		
		$log = new Logger('test_application');
		$log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$kernel = new Kernel();
		$kernel->setLog($log);
		$kernel->setSettings($settings);
		$kernel->init();
		
		$server = $kernel->getServer();
		$server->setLog($log);
		
		$client = new Client();
		$client->setSslPrv($settings->data['node']['sslKeyPrvPath'], $settings->data['node']['sslKeyPrvPass']);
		$client->setId(1);
		$client->setServer($server);
		
		$client->requestAdd('name1', 'rid1', array('data1' => 1234));
		$expect = array('id' => 1, 'name' => 'name1', 'rid' => 'rid1', 'data' => array('data1' => 1234));
		$request = $client->requestGetByRid('rid1');
		$this->assertEquals($expect, $request);
		
		$client->requestRemove($request);
		$request = $client->requestGetByRid('rid1');
		$this->assertEquals(null, $request);
		
		$kernel->shutdown();
	}
	
	public function testAction(){
		file_put_contents('tests/id_rsa_client1.prv', static::NODE_LOCAL_SSL_KEY_PRV1);
		file_put_contents('tests/id_rsa_client1.pub', static::NODE_LOCAL_SSL_KEY_PUB1);
		
		$settings = new Settings();
		$settings->data['datadir'] = 'tests';
		$settings->data['firstRun'] = false;
		$settings->data['timeCreated'] = time();
		$settings->data['node']['ip'] = '';
		$settings->data['node']['port'] = 0;
		$settings->data['node']['id'] = 'cafed00d-2131-4159-8e11-0b4dbadb1738';
		$settings->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings->data['node']['sslKeyPrvPath'] = 'tests/id_rsa_client1.prv';
		$settings->data['node']['sslKeyPubPath'] = 'tests/id_rsa_client1.pub';
		$settings->data['user']['nickname'] = 'user_'.substr(md5(time()), 0, 4);
		
		$log = new Logger('test_application');
		$log->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$kernel = new Kernel();
		$kernel->setLog($log);
		$kernel->setSettings($settings);
		$kernel->init();
		
		$server = $kernel->getServer();
		$server->setLog($log);
		
		$client = new Client();
		$client->setSslPrv($settings->data['node']['sslKeyPrvPath'], $settings->data['node']['sslKeyPrvPass']);
		$client->setId(1);
		$client->setServer($server);
		
		$action = new ClientAction(ClientAction::CRITERION_NONE);
		$client->actionAdd($action);
		
		$oldActions = $client->actionsGetByCriterion(ClientAction::CRITERION_NONE);
		$this->assertEquals(array($action), $oldActions);
		
		$oldAction = $client->actionGetByCriterion(ClientAction::CRITERION_NONE);
		$this->assertEquals($action, $oldAction);
		
		
		$client->actionRemove($action);
		
		$oldActions = $client->actionsGetByCriterion(ClientAction::CRITERION_NONE);
		$this->assertEquals(array(), $oldActions);
		
		$oldAction = $client->actionGetByCriterion(ClientAction::CRITERION_NONE);
		$this->assertEquals(null, $oldAction);
		
		$kernel->shutdown();
	}
	
}
