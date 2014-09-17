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
use TheFox\Dht\Simple\Table;
#use TheFox\Dht\Kademlia\Bucket;
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

fzuWxnL4rI1Qb15BeskZUvmW2xsVQTN0b57Y6IcTnAIAcLPmremgClzp34J/F0h+
CjR6M7D9TyHxPlukGLR5rVhskbfM5yRZd0BLW4wuJokeYU5NjWhVijthG1/7EH5I
bXX7+XSpD0dLKWfYgHx1QLADdupdvSMmMa/nKkzT5Av64LfN6t5r7tEoW1eRov7O
NBfC9oB5SK0SQtM0Ba5jfpHzsTa6eLHjrj5kYqB4x07nf4eLIdx63Qv0osQr7SKz
4cqaOye36D3sxD6CC9W33HQX0yXiOyvJEXw5csXb2vdNNc1tErGc2SuvKbxHT8R1
zro4HY1KIy+oQrW4FwTCAW5M37Qf2CrD86LBlkTINLpcDXaY6rbODcNMCIqNV/Uv
gU9j73CAyZYkkIWRc//KGxso2N5r6bu1OxLJFk9W8RdnN5wrjvUxOJc3b/I4eRoG
h5039p+aUUrAdw8u/IodGepxJ0R1R302NP8kS1/5tNuYMLnZfuImXPiDYlqwbQB5
Z76CsoPp9V51g3OWNmrjCknRzOp5G+POhOcHxwrUNajPgFpIN+GCn9HlOguOS1Oz
5qpGQ7RV4Jp9eQN944iPa7ERSx0ladTOnmoKUeTO7U4ye9h2iwrDQUdcHstgooFY
11V/g/fMGNmVtLTYy3yUqG+SFbLlW+IKO4+hzuEsU31Xa2dWrpGlzBgmYZ21RB/6
F+VcHJ6rRem0s7tZrrThJ4b6e/OuBoGitAAB5mX5I44gvkFSJGPdP/cfotpJKjMn
MFytKLqZ33+rJbNq5UKtyCppdUNt8EzDryWDmQTB+iW7hpQ36g8Wlxh2/N+e3dpl
WZcHyLqBuetb0bx8YlgcuA+PhzqIsFPwODB7Xr/rxHv58/5yYtRgJ0G42+xWpqG4
OJJ1WxCQwk3wl+XGGY3Y1W/+iz+E82Dwtwpbl/bVfNr6BCo8TlNSj7ZZ4ttMKnY/
+RVBzarnvAl2KyGyOCh5QTWUr9OOFj8ujD9Xqyj6XHatlWrsVW2eWEFetmEKNg+o
LTvoWow1HwXcAmeUha+jBuKsbFtiCGlPGTHAJX+GCR5if8TsGKCekyRkyL23pXuv
cBjJ0txsO7smLyPAim7XyYrAE8an4uCRgzgOdNxIXDiYbXBLMqNHPnVnUFWqFQWP
raM0uC9yIXlqUX+5H6A5uZVnhdOQN0j1JMDh3WQ2LDh6oeO2TpLCVZT6COk9+Ral
odVUKZzqasjsIKtHXybHDQ50cwi4kaF98+YeB4vUJakcInu4vqtkgoIp4c+Dvt6M
x2e/RZJKxk0pNq8f79o+k6yPwwZJv4Ehy9elC6jqniUJKR+ThlKROXmLxUFkSjjD
SVSVt72bjwa1Xyatu7sSM4zuK1yYUuAIS1l9Nx9T0eYry32Wj3tlxbbVgfUP/mgb
dZLctehiMqlhrJM3n/An4AiI0dVR9xSMtAv/SfBijpf6fGK1MbD8jBqsa1AGhIPE
pDn7GRdQ3rb0Lv+hcTIawHPmaJbwhFCZgVPo8JZCDAtkfXcNeOR5bJ5lyzTbOtbQ
94/C69chAN0t+d5K1YOPhJ4g8hty4TLa3JZUXL9Y2jGax8F+yWIpQ3GSjum2r9hi
MKe56jWUSgzHYJC8ww1+GIiculVOI8dYpSzi5LVwq9zIEjQrHar7MSgdQ7ojO17I
Ept3MHoGBL7mPI9+VTUv3CenkQNL/mgMVm24ILfX8DUYjwrQxv2SxNdVF330UclQ
KGiUmoAG/0ASsbtYWopZNIengNpuCzIO97J5tZAfeFkegct9c+jn8XOt2bkdEKlE
vtJQOgP6KwKGaLvMc+nG4QiwT8yXb7UlIMRrGQ6nxTHLvXtfOLNbqTKfHfBWI8aa
IubjX/WvWW6+YAWaiX2WZI1LCRUr0v8JLu8ErXIl1JIFaX3Ho+OTKQEtSX0NQ8M/
pUSnC4f9XFpR85sp0+GygcqpwwrSAhWli4k9KWjgkncJafrxCOwOIGfSgtzFoHIj
MZRSVXYv5WBopSo7VR7d9R/3SmATZtoS1PDp/MgX6DaNzMQzaECztcLapEs0/KWH
wdAdpV+gX5upmjuPzIAx6dOuDpmhb8uwa899CMfBnnGO5pixzqYKMYRjMGtfdZrd
hd8zwfcksGURel54PnN4ivHCZyPu7hvoFVogF0ngDbkF5+ZHCxcGZ15BEra6kgwN
v2u4uw9x954eVa/hcooGJhVm/Qt5CM0inkd14+tr+VOC0kRlw8uqaS4tHwwxzawh
AB/isFPm7Q5RzLlaE0MtTyKCBHVWSuwp8mE7vhhEpc1kbVCrphOW/GsH2PCP+xFh
JkKvp9uhoVYwN2dW2szoLupDi5+D0hfsgaTRYPCc1lx+nPS/rF1y3IZsOCH2iNQc
e76ugHstx3daR81zY06dCFLFw8XDFb0en8MVj4WU7ToGn7UocixFWYvMz0SEmU0G
EGAdSHFRvUtzJaWyYzkomOiodF+9H3TGr7ABhupzt6KNFyyMvmjBNL4NLWtvfYxh
nFYN6D7KYeK0dOgskMblG1S1YwHYoQOuUGyUskWQOHsQ4QKUMhlyQFTExCKsirSD
Xoq/QaIg9oglkthTeEo9iPsgy0JGsv0yjr7xG4DGYe21piPBV8/vF0Exr3DlglIU
006IGgOcIcAG9BMOMF3CbjhR7udXH7REo62DsrKO/gYJHhNVJpVVWjB95I8pXUn3
ir91tzdJjqs9EcSTgU4agq15dAdbLnSnAFArlbeL+aZ7rqXj+P2F6T96p5EraIes
oGCnr8wrpfSkPpkn5zrWg+QvWLpyaABiuKFqshgFkyil3Ol3NjwoY7NaJ8Ejkvk1
vvn7jmdrxfOEytbuLWQQt703PqrixPmL0J9Gt/MRg+3ffSEqrmioJQcWhGmr8YAu
UPgLOaoPASyHbQlf+Pd44dgkY9kyr20vvlKwU0/HzpyfSJ81isxjRc6lBjD24czu
qEeULsDRqMUayec4TE9jZhdmrHRBMlwVe/D00dUoypc4qLBlFjrgRW9GP4N230Ud
6MMy1WzS7yVD7+K0KANgMpwO15KUuid/sx6W1TlNZ6/2NOKL92CewJAndm0jsDW5
GheNpfKLUSpaJnLoAYIHqijdsVyrLMuAm1QYWPnZf1XYZNy4RjciW8DzVSg5bXbF
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
DEK-Info: DES-EDE3-CBC,2938B7BC0E596B9A

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
-----END RSA PRIVATE KEY-----
';
	
	const NODE_LOCAL_SSL_KEY_PUB3 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA1nXWhVcpOOWX9zKUaU2v
eI7DdepYLlrhed+lN+p6z+xvahcp2d4Ogqr3dUzzx7EDV8xTo7kSoZlozlcOasq2
c3cyu8n5HicIEo9xVkADQqz9lR1fghYNzbrEmWC7GQryjjYpb+q/maWGB4gm9QSq
jw2dCvzNrEQMAALvxjvnD9DtmPluLbbaBo+CFZuf6haFAMjzh16f6Pb+AG3yiUe2
o+2Y0xeMIJbzEugmhumjaL0wZkK17pq3VEhuz1izgHAB3THDHzxUtW8TlIths/5a
4upHzWKLohYMHB/o1d+V54DJLE5meazfpCKI37AEKhGhWdxX/QZ6HiDohwJ8nUdG
hbQ4IxMC/MDR5Z2KxVo1GwkqLW8CPC0LBsmBrqMy5ewxRTEpMh4m5MmeKViCDlPN
Fr2MrZlAIOLdA5VTD7ybjOm/vQ1nftU5zlzg8mRo99KK57nOLqOsRESPuU6j4CK8
n55Tp8+WHAYFTAG8/P0tGMvEVuhw/3IMtYFZQUJsjfZg617nCSFpTzJ0hZLRbraK
s5WruJuTHNcsB4mDnTOjL5L4DsQNq9ktVyr7m4Qf62XEZtkLlDl4/zRMyqWt2Zg8
VhHlVMjkH+5bNofoP1CFBttRHbBd1Nt79Os9hzxGdyXedQ0yXunknTboZKhNcOU9
VNHajAjDUbj1uoKYsuAFQO0CAwEAAQ==
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
	
	private function sendGenTestDataDefault(){
		@unlink('tests/testdir_tcpclient1/bucket_root.yml');
		@unlink('tests/testdir_tcpclient2/bucket_root.yml');
		
		$filesystem = new Filesystem();
		$filesystem->remove('tests/testdir_tcpclient1');
		$filesystem->remove('tests/testdir_tcpclient2');
		$filesystem->mkdir('tests/testdir_tcpclient1', $mode = 0777);
		$filesystem->mkdir('tests/testdir_tcpclient2', $mode = 0777);
		
		file_put_contents('tests/testdir_tcpclient1/id_rsa.prv', static::NODE_LOCAL_SSL_KEY_PRV1);
		file_put_contents('tests/testdir_tcpclient1/id_rsa.pub', static::NODE_LOCAL_SSL_KEY_PUB1);
		
		file_put_contents('tests/testdir_tcpclient2/id_rsa.prv', static::NODE_LOCAL_SSL_KEY_PRV2);
		file_put_contents('tests/testdir_tcpclient2/id_rsa.pub', static::NODE_LOCAL_SSL_KEY_PUB2);
		
		
		$localNode1 = new Node();
		$localNode1->setUri('tcp://127.0.0.1:25000');
		$localNode1->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		
		$table1 = new Table('tests/testdir_tcpclient1/table.yml');
		$table1->setDatadirBasePath('tests/testdir_tcpclient1');
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
		$localNode2->setUri('tcp://127.0.0.2:25000');
		$localNode2->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1739');
		
		$table2 = new Table('tests/testdir_tcpclient2/table.yml');
		$table2->setDatadirBasePath('tests/testdir_tcpclient2');
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
		$settings1->data['datadir'] = 'tests/testdir_tcpclient1';
		$settings1->data['firstRun'] = false;
		$settings1->data['timeCreated'] = time();
		$settings1->data['node']['uriLocal'] = 'tcp://127.0.0.1';
		$settings1->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB1);
		$settings1->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings1->data['node']['sslKeyPrvPath'] = 'tests/testdir_tcpclient1/id_rsa.prv';
		$settings1->data['node']['sslKeyPubPath'] = 'tests/testdir_tcpclient1/id_rsa.pub';
		$settings1->data['user']['nickname'] = 'user1';
		
		$settings2 = new Settings();
		$settings2->data['datadir'] = 'tests/testdir_tcpclient2';
		$settings2->data['firstRun'] = false;
		$settings2->data['timeCreated'] = time();
		$settings2->data['node']['uriLocal'] = 'tcp://127.0.0.2';
		$settings2->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB2);
		$settings2->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings2->data['node']['sslKeyPrvPath'] = 'tests/testdir_tcpclient2/id_rsa.prv';
		$settings2->data['node']['sslKeyPubPath'] = 'tests/testdir_tcpclient2/id_rsa.pub';
		$settings2->data['user']['nickname'] = 'user2';
		
		
		$log1 = new Logger('client_1');
		#$log1->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$log2 = new Logger('client_2');
		#$log2->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
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
		$client1->debug = true;
		$client1->setSslPrv($settings1->data['node']['sslKeyPrvPath'], $settings1->data['node']['sslKeyPrvPass']);
		$client1->setId(1);
		$client1->setUri($settings1->data['node']['uriLocal']);
		$client1->setServer($server1);
		$this->assertEquals($settings1->data['node']['uriLocal'], 'tcp://'.$client1->getUri()->getHost());
		#fwrite(STDOUT, 'ip1: /'.$client1->getUri()->getHost().'/'."\n");
		
		$client2 = new TcpClient();
		$client2->debug = true;
		$client2->setSslPrv($settings2->data['node']['sslKeyPrvPath'], $settings2->data['node']['sslKeyPrvPass']);
		$client2->setId(2);
		$client2->setUri($settings2->data['node']['uriLocal']);
		$client2->setServer($server2);
		$this->assertEquals($settings2->data['node']['uriLocal'], 'tcp://'.$client2->getUri()->getHost());
		#fwrite(STDOUT, 'ip2: /'.$client2->getUri()->getHost().'/'."\n");
		
		return array($client1, $client2);
	}
	
	private function sendGenTestDataBridge(){
		@unlink('tests/testdir_tcpclient1/bucket_root.yml');
		@unlink('tests/testdir_tcpclient2/bucket_root.yml');
		@unlink('tests/testdir_tcpclient3/bucket_root.yml');
		
		$filesystem = new Filesystem();
		$filesystem->remove('tests/testdir_tcpclient1');
		$filesystem->remove('tests/testdir_tcpclient2');
		$filesystem->remove('tests/testdir_tcpclient3');
		$filesystem->mkdir('tests/testdir_tcpclient1', $mode = 0777);
		$filesystem->mkdir('tests/testdir_tcpclient2', $mode = 0777);
		$filesystem->mkdir('tests/testdir_tcpclient3', $mode = 0777);
		
		file_put_contents('tests/testdir_tcpclient1/id_rsa.prv', static::NODE_LOCAL_SSL_KEY_PRV1);
		file_put_contents('tests/testdir_tcpclient1/id_rsa.pub', static::NODE_LOCAL_SSL_KEY_PUB1);
		
		file_put_contents('tests/testdir_tcpclient2/id_rsa.prv', static::NODE_LOCAL_SSL_KEY_PRV2);
		file_put_contents('tests/testdir_tcpclient2/id_rsa.pub', static::NODE_LOCAL_SSL_KEY_PUB2);
		
		file_put_contents('tests/testdir_tcpclient3/id_rsa.prv', static::NODE_LOCAL_SSL_KEY_PRV3);
		file_put_contents('tests/testdir_tcpclient3/id_rsa.pub', static::NODE_LOCAL_SSL_KEY_PUB3);
		
		
		$localNode1 = new Node();
		$localNode1->setUri('tcp://127.0.0.1:25000');
		$localNode1->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');
		
		$table1 = new Table('tests/testdir_tcpclient1/table.yml');
		$table1->setDatadirBasePath('tests/testdir_tcpclient1');
		$table1->setLocalNode($localNode1);
		$table1->setDataChanged(true);
		$table1->save();
		
		
		$localNode2 = new Node();
		$localNode2->setUri('tcp://127.0.0.2:25000');
		$localNode2->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1739');
		
		$table2 = new Table('tests/testdir_tcpclient2/table.yml');
		$table2->setDatadirBasePath('tests/testdir_tcpclient2');
		$table2->setLocalNode($localNode2);
		$table2->setDataChanged(true);
		$table2->save();
		
		
		$localNode3 = new Node();
		$localNode3->setUri('tcp://127.0.0.3:25000');
		$localNode3->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1740');
		
		$table3 = new Table('tests/testdir_tcpclient3/table.yml');
		$table3->setDatadirBasePath('tests/testdir_tcpclient3');
		$table3->setLocalNode($localNode3);
		$table3->setDataChanged(true);
		$table3->save();
		
		
		$settings1 = new Settings();
		$settings1->data['datadir'] = 'tests/testdir_tcpclient1';
		$settings1->data['firstRun'] = false;
		$settings1->data['timeCreated'] = time();
		$settings1->data['node']['uriLocal'] = 'tcp://127.0.0.1';
		$settings1->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB1);
		$settings1->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings1->data['node']['sslKeyPrvPath'] = 'tests/testdir_tcpclient1/id_rsa.prv';
		$settings1->data['node']['sslKeyPubPath'] = 'tests/testdir_tcpclient1/id_rsa.pub';
		$settings1->data['user']['nickname'] = 'user1';
		$settings1->data['node']['bridge']['client']['enabled'] = true;
		
		$settings2 = new Settings();
		$settings2->data['datadir'] = 'tests/testdir_tcpclient2';
		$settings2->data['firstRun'] = false;
		$settings2->data['timeCreated'] = time();
		$settings2->data['node']['uriLocal'] = 'tcp://127.0.0.2';
		$settings2->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB2);
		$settings2->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings2->data['node']['sslKeyPrvPath'] = 'tests/testdir_tcpclient2/id_rsa.prv';
		$settings2->data['node']['sslKeyPubPath'] = 'tests/testdir_tcpclient2/id_rsa.pub';
		$settings2->data['user']['nickname'] = 'user2';
		$settings2->data['node']['bridge']['server']['enabled'] = true;
		
		$settings3 = new Settings();
		$settings3->data['datadir'] = 'tests/testdir_tcpclient3';
		$settings3->data['firstRun'] = false;
		$settings3->data['timeCreated'] = time();
		$settings3->data['node']['uriLocal'] = 'tcp://127.0.0.3';
		$settings3->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB2);
		$settings3->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings3->data['node']['sslKeyPrvPath'] = 'tests/testdir_tcpclient3/id_rsa.prv';
		$settings3->data['node']['sslKeyPubPath'] = 'tests/testdir_tcpclient3/id_rsa.pub';
		$settings3->data['user']['nickname'] = 'user3';
		$settings3->data['node']['bridge']['client']['enabled'] = true;
		
		
		$log1 = new Logger('client_1');
		$log1->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$log2 = new Logger('client_2');
		$log2->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$log3 = new Logger('client_2');
		$log3->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$kernel1 = new Kernel();
		$kernel1->setLog($log1);
		$kernel1->setSettings($settings1);
		$kernel1->init();
		
		$kernel2 = new Kernel();
		$kernel2->setLog($log2);
		$kernel2->setSettings($settings2);
		$kernel2->init();
		
		$kernel3 = new Kernel();
		$kernel3->setLog($log3);
		$kernel3->setSettings($settings3);
		$kernel3->init();
		
		$server1 = $kernel1->getServer();
		$server1->setLog($log1);
		$server2 = $kernel2->getServer();
		$server2->setLog($log2);
		$server3 = $kernel3->getServer();
		$server3->setLog($log3);
		
		$client1 = new TcpClient();
		$client1->debug = true;
		$client1->setSslPrv($settings1->data['node']['sslKeyPrvPath'], $settings1->data['node']['sslKeyPrvPass']);
		$client1->setId(1);
		$client1->setUri($settings1->data['node']['uriLocal']);
		$client1->setServer($server1);
		$this->assertEquals($settings1->data['node']['uriLocal'], 'tcp://'.$client1->getUri()->getHost());
		#fwrite(STDOUT, 'ip1: /'.$client1->getUri()->getHost().'/'."\n");
		
		$client2 = new TcpClient();
		$client2->debug = true;
		$client2->setSslPrv($settings2->data['node']['sslKeyPrvPath'], $settings2->data['node']['sslKeyPrvPass']);
		$client2->setId(2);
		$client2->setUri($settings2->data['node']['uriLocal']);
		$client2->setServer($server2);
		$this->assertEquals($settings2->data['node']['uriLocal'], 'tcp://'.$client2->getUri()->getHost());
		#fwrite(STDOUT, 'ip2: /'.$client2->getUri()->getHost().'/'."\n");
		
		$client3 = new TcpClient();
		$client3->debug = true;
		$client3->setSslPrv($settings3->data['node']['sslKeyPrvPath'], $settings3->data['node']['sslKeyPrvPass']);
		$client3->setId(3);
		$client3->setUri($settings3->data['node']['uriLocal']);
		$client3->setServer($server3);
		$this->assertEquals($settings3->data['node']['uriLocal'], 'tcp://'.$client3->getUri()->getHost());
		#fwrite(STDOUT, 'ip3: /'.$client3->getUri()->getHost().'/'."\n");
		
		return array($client1, $client2, $client3);
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
		
		$this->assertTrue($client1->getStatus('hasId'));
		$this->assertTrue($client2->getStatus('hasId'));
	}
	
	public function testSendBasic(){
		list($client1, $client2) = $this->sendGenTestDataDefault();
		
		// Hello Client1
		$raw = $client1->sendHello();
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('hello', $json[0]['name']);
		$this->assertEquals('127.0.0.1', $json[0]['data']['ip']);
		
		// ID Client1
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('id', $json[0]['name']);
		$this->assertEquals('07fb5f61-5565-58f2-891e-1337e8b747ac', $json[0]['data']['id']);
		$this->assertTrue(array_key_exists('sslKeyPub', $json[0]['data']));
		$this->assertTrue(array_key_exists('sslKeyPubSign', $json[0]['data']));
		$this->assertFalse($json[0]['data']['isChannel']);
		$this->assertFalse($client1->getStatus('hasId'));
		$this->assertFalse($client2->getStatus('hasId'));
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('id_ok', $json[0]['name']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		$this->assertEquals(static::NODE_LOCAL_SSL_KEY_PUB2, $client1->getNode()->getSslKeyPub());
		$this->assertTrue($client1->getStatus('hasId'));
		
		
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
		$this->assertTrue($client2->getStatus('hasId'));
		
		
		
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
		list($client1, $client2) = $this->sendGenTestDataDefault();
		
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
		list($client1, $client2) = $this->sendGenTestDataDefault();
		
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
	
	private function sendClientsSsl($client1, $client2){
		$raw = $client1->sendSslInit();
		$raw = $client2->dataRecv($raw);
		$raw = $client1->dataRecv($raw);
		$raw = $client2->dataRecv($raw);
		$raw = $client1->dataRecv($raw);
		$raw = $client2->dataRecv($raw);
		$raw = $client1->dataRecv($raw);
		$raw = $client2->dataRecv($raw);
		$raw = $client1->dataRecv($raw);
	}
	
	public function testSendSslDefault(){
		list($client1, $client2) = $this->sendGenTestDataDefault();
		
		// SSL before ID should cause an error.
		$raw = $client1->sendSslInit();
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_init_response', $json[0]['name']);
		$this->assertEquals(1000, $json[0]['data']['status']);
		
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
		$this->assertEquals(4000, $json[0]['data']['status']);
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(3100, $json[0]['data']['code']);
		$this->assertFalse($client1->getStatus('hasSslInit'));
		$this->assertFalse($client2->getStatus('hasSslInit'));
		
		
		
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
		
		$this->assertFalse($client1->getStatus('hasSslInit'));
		$this->assertTrue($client1->getStatus('hasSendSslInit'));
		$this->assertFalse($client1->getStatus('hasSslInitOk'));
		$this->assertFalse($client1->getStatus('hasSslTest'));
		$this->assertFalse($client1->getStatus('hasSslVerify'));
		$this->assertFalse($client1->getStatus('hasSslPasswortPut'));
		$this->assertFalse($client1->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client1->getStatus('hasSsl'));
		
		$this->assertTrue($client2->getStatus('hasSslInit'));
		$this->assertTrue($client2->getStatus('hasSendSslInit'));
		$this->assertFalse($client2->getStatus('hasSslInitOk'));
		$this->assertFalse($client2->getStatus('hasSslTest'));
		$this->assertFalse($client2->getStatus('hasSslVerify'));
		$this->assertFalse($client2->getStatus('hasSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client2->getStatus('hasSsl'));
		#return;
		
		// SSL response
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_init_response', $json[0]['name']);
		$this->assertEquals('ssl_test', $json[1]['name']);
		
		$this->assertTrue($client1->getStatus('hasSslInit'));
		$this->assertTrue($client1->getStatus('hasSendSslInit'));
		$this->assertTrue($client1->getStatus('hasSslInitOk'));
		$this->assertFalse($client1->getStatus('hasSslTest'));
		$this->assertFalse($client1->getStatus('hasSslVerify'));
		$this->assertFalse($client1->getStatus('hasSslPasswortPut'));
		$this->assertFalse($client1->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client1->getStatus('hasSsl'));
		
		$this->assertTrue($client2->getStatus('hasSslInit'));
		$this->assertTrue($client2->getStatus('hasSendSslInit'));
		$this->assertFalse($client2->getStatus('hasSslInitOk'));
		$this->assertFalse($client2->getStatus('hasSslTest'));
		$this->assertFalse($client2->getStatus('hasSslVerify'));
		$this->assertFalse($client2->getStatus('hasSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client2->getStatus('hasSsl'));
		#return;
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_test', $json[0]['name']);
		$this->assertEquals('ssl_verify', $json[1]['name']);
		
		$this->assertTrue($client1->getStatus('hasSslInit'));
		$this->assertTrue($client1->getStatus('hasSendSslInit'));
		$this->assertTrue($client1->getStatus('hasSslInitOk'));
		$this->assertFalse($client1->getStatus('hasSslTest'));
		$this->assertFalse($client1->getStatus('hasSslVerify'));
		$this->assertFalse($client1->getStatus('hasSslPasswortPut'));
		$this->assertFalse($client1->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client1->getStatus('hasSsl'));
		
		$this->assertTrue($client2->getStatus('hasSslInit'));
		$this->assertTrue($client2->getStatus('hasSendSslInit'));
		$this->assertTrue($client2->getStatus('hasSslInitOk'));
		$this->assertTrue($client2->getStatus('hasSslTest'));
		$this->assertFalse($client2->getStatus('hasSslVerify'));
		$this->assertFalse($client2->getStatus('hasSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client2->getStatus('hasSsl'));
		#return;
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_verify', $json[0]['name']);
		$this->assertEquals('ssl_password_put', $json[1]['name']);
		
		$this->assertTrue($client1->getStatus('hasSslInit'));
		$this->assertTrue($client1->getStatus('hasSendSslInit'));
		$this->assertTrue($client1->getStatus('hasSslInitOk'));
		$this->assertTrue($client1->getStatus('hasSslTest'));
		$this->assertTrue($client1->getStatus('hasSslVerify'));
		$this->assertFalse($client1->getStatus('hasSslPasswortPut'));
		$this->assertFalse($client1->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client1->getStatus('hasSsl'));
		
		$this->assertTrue($client2->getStatus('hasSslInit'));
		$this->assertTrue($client2->getStatus('hasSendSslInit'));
		$this->assertTrue($client2->getStatus('hasSslInitOk'));
		$this->assertTrue($client2->getStatus('hasSslTest'));
		$this->assertFalse($client2->getStatus('hasSslVerify'));
		$this->assertFalse($client2->getStatus('hasSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client2->getStatus('hasSsl'));
		
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_password_put', $json[0]['name']);
		$this->assertEquals('ssl_password_test', $json[1]['name']);
		
		$this->assertTrue($client1->getStatus('hasSslInit'));
		$this->assertTrue($client1->getStatus('hasSendSslInit'));
		$this->assertTrue($client1->getStatus('hasSslInitOk'));
		$this->assertTrue($client1->getStatus('hasSslTest'));
		$this->assertTrue($client1->getStatus('hasSslVerify'));
		$this->assertFalse($client1->getStatus('hasSslPasswortPut'));
		$this->assertFalse($client1->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client1->getStatus('hasSsl'));
		
		$this->assertTrue($client2->getStatus('hasSslInit'));
		$this->assertTrue($client2->getStatus('hasSendSslInit'));
		$this->assertTrue($client2->getStatus('hasSslInitOk'));
		$this->assertTrue($client2->getStatus('hasSslTest'));
		$this->assertTrue($client2->getStatus('hasSslVerify'));
		$this->assertTrue($client2->getStatus('hasSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client2->getStatus('hasSsl'));
		
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_password_test', $json[0]['name']);
		$this->assertEquals('ssl_password_verify', $json[1]['name']);
		
		$this->assertTrue($client1->getStatus('hasSslInit'));
		$this->assertTrue($client1->getStatus('hasSendSslInit'));
		$this->assertTrue($client1->getStatus('hasSslInitOk'));
		$this->assertTrue($client1->getStatus('hasSslTest'));
		$this->assertTrue($client1->getStatus('hasSslVerify'));
		$this->assertTrue($client1->getStatus('hasSslPasswortPut'));
		$this->assertTrue($client1->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client1->getStatus('hasSsl'));
		
		$this->assertTrue($client2->getStatus('hasSslInit'));
		$this->assertTrue($client2->getStatus('hasSendSslInit'));
		$this->assertTrue($client2->getStatus('hasSslInitOk'));
		$this->assertTrue($client2->getStatus('hasSslTest'));
		$this->assertTrue($client2->getStatus('hasSslVerify'));
		$this->assertTrue($client2->getStatus('hasSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client2->getStatus('hasSsl'));
		
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_password_verify', $json[0]['name']);
		
		$this->assertTrue($client1->getStatus('hasSslInit'));
		$this->assertTrue($client1->getStatus('hasSendSslInit'));
		$this->assertTrue($client1->getStatus('hasSslInitOk'));
		$this->assertTrue($client1->getStatus('hasSslTest'));
		$this->assertTrue($client1->getStatus('hasSslVerify'));
		$this->assertTrue($client1->getStatus('hasSslPasswortPut'));
		$this->assertTrue($client1->getStatus('hasSslPasswortTest'));
		$this->assertFalse($client1->getStatus('hasSsl'));
		
		$this->assertTrue($client2->getStatus('hasSslInit'));
		$this->assertTrue($client2->getStatus('hasSendSslInit'));
		$this->assertTrue($client2->getStatus('hasSslInitOk'));
		$this->assertTrue($client2->getStatus('hasSslTest'));
		$this->assertTrue($client2->getStatus('hasSslVerify'));
		$this->assertTrue($client2->getStatus('hasSslPasswortPut'));
		$this->assertTrue($client2->getStatus('hasSslPasswortTest'));
		$this->assertTrue($client2->getStatus('hasSsl'));
		
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals(null, $json[0]);
		
		$this->assertTrue($client1->getStatus('hasSslInit'));
		$this->assertTrue($client1->getStatus('hasSendSslInit'));
		$this->assertTrue($client1->getStatus('hasSslInitOk'));
		$this->assertTrue($client1->getStatus('hasSslTest'));
		$this->assertTrue($client1->getStatus('hasSslVerify'));
		$this->assertTrue($client1->getStatus('hasSslPasswortPut'));
		$this->assertTrue($client1->getStatus('hasSslPasswortTest'));
		$this->assertTrue($client1->getStatus('hasSsl'));
		
		$this->assertTrue($client2->getStatus('hasSslInit'));
		$this->assertTrue($client2->getStatus('hasSendSslInit'));
		$this->assertTrue($client2->getStatus('hasSslInitOk'));
		$this->assertTrue($client2->getStatus('hasSslTest'));
		$this->assertTrue($client2->getStatus('hasSslVerify'));
		$this->assertTrue($client2->getStatus('hasSslPasswortPut'));
		$this->assertTrue($client2->getStatus('hasSslPasswortTest'));
		$this->assertTrue($client2->getStatus('hasSsl'));
		
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendSslReput(){
		list($client1, $client2) = $this->sendGenTestDataDefault();
		
		
		$this->sendClientsId($client1, $client2);
		$this->sendClientsSsl($client1, $client2);
		
		$this->assertFalse($client1->getStatus('hasSendReSslPasswortPut'));
		$this->assertFalse($client1->getStatus('hasReSslPasswortPut'));
		$this->assertFalse($client1->getStatus('hasReSslPasswortTest'));
		
		$this->assertFalse($client2->getStatus('hasSendReSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasReSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasReSslPasswortTest'));
		
		// SSL RePut
		$raw = $client1->sendSslPasswordReput('user1');
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_password_reput', $json[0]['name']);
		
		$this->assertTrue($client1->getStatus('hasSendReSslPasswortPut'));
		$this->assertFalse($client1->getStatus('hasReSslPasswortPut'));
		$this->assertFalse($client1->getStatus('hasReSslPasswortTest'));
		
		$this->assertFalse($client2->getStatus('hasSendReSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasReSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasReSslPasswortTest'));
		#return;
		
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_password_reput', $json[0]['name']);
		$this->assertEquals('ssl_password_retest', $json[1]['name']);
		
		$this->assertTrue($client1->getStatus('hasSendReSslPasswortPut'));
		$this->assertFalse($client1->getStatus('hasReSslPasswortPut'));
		$this->assertFalse($client1->getStatus('hasReSslPasswortTest'));
		
		$this->assertTrue($client2->getStatus('hasSendReSslPasswortPut'));
		$this->assertTrue($client2->getStatus('hasReSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasReSslPasswortTest'));
		#return;
		
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_password_retest', $json[0]['name']);
		$this->assertEquals('ssl_password_reverify', $json[1]['name']);
		
		$this->assertTrue($client1->getStatus('hasSendReSslPasswortPut'));
		$this->assertTrue($client1->getStatus('hasReSslPasswortPut'));
		$this->assertTrue($client1->getStatus('hasReSslPasswortTest'));
		
		$this->assertTrue($client2->getStatus('hasSendReSslPasswortPut'));
		$this->assertTrue($client2->getStatus('hasReSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasReSslPasswortTest'));
		#return;
		
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ssl_password_reverify', $json[0]['name']);
		
		$this->assertTrue($client1->getStatus('hasSendReSslPasswortPut'));
		$this->assertTrue($client1->getStatus('hasReSslPasswortPut'));
		$this->assertTrue($client1->getStatus('hasReSslPasswortTest'));
		
		$this->assertFalse($client2->getStatus('hasSendReSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasReSslPasswortPut'));
		$this->assertFalse($client2->getStatus('hasReSslPasswortTest'));
		
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendTalk(){
		list($client1, $client2) = $this->sendGenTestDataDefault();
		
		
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
		
		
		$this->sendClientsId($client1, $client2);
		$this->sendClientsSsl($client1, $client2);
		
		// Talk Request
		$raw = $client1->sendTalkRequest('user1');
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('talk_request', $json[0]['name']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('talk_response', $json[0]['name']);
		#$this->assertEquals('quit', $json[1]['name']);
		
		$raw = $client1->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		#$this->assertEquals('quit', $json[0]['name']);
		
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
	
	public function testSendBridgeSubscribe1(){
		list($client1, $client2) = $this->sendGenTestDataDefault();
		
		$this->sendClientsId($client1, $client2);
		$this->sendClientsSsl($client1, $client2);
		
		// Bridge Subscribe to no bridge server should cause an error.
		$raw = $client1->sendBridgeSubscribe(true);
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(5000, $json[0]['data']['code']);
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendBridgeSubscribe2(){
		list($client1, $client2, $client3) = $this->sendGenTestDataBridge();
		
		// Bridge Subscribe before ID should cause an error.
		$raw = $client1->sendBridgeSubscribe(true);
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		$this->assertEquals(null, $json[0]);
		
		#ve('id');
		$this->sendClientsId($client1, $client2);
		
		// Bridge Subscribe before SSL should cause an error.
		$raw = $client1->sendBridgeSubscribe(true);
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		$this->assertEquals(null, $json[0]);
		
		#ve('ssl');
		$this->sendClientsSsl($client1, $client2);
		
		$raw = $client1->sendBridgeSubscribe(true);
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('bridge_subscribe_response', $json[0]['name']);
		
		
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
		$client3->getServer()->getKernel()->shutdown();
	}
	
	public function testSendPingPong(){
		list($client1, $client2) = $this->sendGenTestDataDefault();
		
		// Ping - Pong
		$raw = $client1->sendPing();
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ping', $json[0]['name']);
		$this->assertEquals('', $json[0]['data']['rid']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('pong', $json[0]['name']);
		$this->assertEquals('', $json[0]['data']['rid']);
		
		$raw = $client1->sendPing('de0bb575-cead-4ffe-adcb-311388511ed7');
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('ping', $json[0]['name']);
		$this->assertEquals('de0bb575-cead-4ffe-adcb-311388511ed7', $json[0]['data']['rid']);
		
		$raw = $client2->dataRecv($raw);
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('pong', $json[0]['name']);
		$this->assertEquals('de0bb575-cead-4ffe-adcb-311388511ed7', $json[0]['data']['rid']);
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendNoop(){
		list($client1, $client2) = $this->sendGenTestDataDefault();
		
		// NoOp
		$raw = $client1->sendNoop();
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('noop', $json[0]['name']);
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
	public function testSendError(){
		list($client1, $client2) = $this->sendGenTestDataDefault();
		
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
		list($client1, $client2) = $this->sendGenTestDataDefault();
		
		// Unknown Command
		$raw = $client2->msgHandleRaw('{"name":"blaaaaa"}');
		$json = $this->rawMsgToJson($raw);
		#ve($json);
		$this->assertEquals('error', $json[0]['name']);
		$this->assertEquals(9020, $json[0]['data']['code']);
		$this->assertEquals('blaaaaa', $json[0]['data']['name']);
		
		$client1->getServer()->getKernel()->shutdown();
		$client2->getServer()->getKernel()->shutdown();
	}
	
}
