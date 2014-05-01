<?php

use TheFox\PhpChat\Cronjob;
use TheFox\PhpChat\MsgDb;
use TheFox\PhpChat\Msg;
use TheFox\PhpChat\Settings;
use TheFox\Dht\Kademlia\Table;
use TheFox\Dht\Kademlia\Node;

class CronjobTest extends PHPUnit_Framework_TestCase{
	
	const NODE0_SSL_KEY_PUB = '-----BEGIN PUBLIC KEY-----
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
	const NODE2_SSL_KEY_PUB = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAq8TIxa14ktxxDd7nyppI
a5ic4bNDCPyNkzIrps78uH9rALeZGMVqJSt1BizPTCJdL9Uy4BZ4dv2qE5n1IN9E
udBUzqLAhTVK9cZooFRFrqmZv7BUAB/T+7/Gc24mAOl7kGwuIBBTuOiDd5J0QkRQ
cmiVV0p4XThaI2uH8Xp+pUpL1ablkFtKGarhxZuCJAOb2t1U2pxumquGtj5oszZT
1ek+s5LBvqJ0CYrrWGE+T+nboR2YvMsTKG/zbdX2JtDEHuDPZzeSxvw6Hv3aPiU+
2aE2Szj6xTzXrMiskCkkM2i3GF/ZwomJfyszELsbyK5Brkb+wSSoNzU6N/2T+ou/
nEygFuaI1BwpQET/y/t9LATNyandVlC8+BlQoGdrE1dNDA+SKtkLLLZGcu3oG5/L
mST+6WLQP6W2j1TWW7u9FfYb0DXQ9RjUDCOomP3doJPGYK5skV7ikCrnKftKLQhC
C6+rvBhPPnCrK4rd0q9lz0H8SAtu18W+EQB8cc4323jis1vs6vkZ9c6LxnTLe2pO
kvgfS69CROdHHFmsZI8y0Lqlo6aC7JIEW5vtMWPNvBmc/GlsZDerK3I3R17JGnB0
xs0c/uLAMzZRFhqwaH2lOsMrU9RD75dKDPF5o3hV7ZQ8knlkDXhk+5WCL6UK9SVQ
oBtclXATtUzixobkK04g4KMCAwEAAQ==
-----END PUBLIC KEY-----';
	const NODE4_SSL_KEY_PUB = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAq9iZCsGcuwT1hVAwZ7ea
ZMmpKgbANR9Sta0cjdhl6pKE27gX89d7jne1wSHvyQ8CF+Kfzn3UbEHHY9xI/Rr/
CFS8PALZfQTXkw19Sp5cXheUFFV9tNctiZNM/ViCSKr8e52tvZB6atLYDyAziaE+
w+cJgA86IxAj4Vh5tkH7Zn6ef71wZUUFp4oMag3z/rttktfToEi3AuTN/RqsbrhV
nOmLqP+AXW4RP0mXIwsmdHg7+udCZNVy/Ye6suG9Cbzwc9aoX7BlfwzV2ujYF7ht
LgYa+lhNAcjEkE4JbH27CHI1HK4FcAYvpiONhdX6ZkZUBO5F5rTMsuOzwk4LT2Hj
8EjGyL7WETmCEGyDVOVBisln0fm6IrDt24b0zA4FVZG1dDyrRspNyj6oJIU1wfkx
8mDKEaSI0QA4Rx9Gnjsax9T2Yflgn8KE+PIeKTjHVnxfiz3+CxYhkHgxiHqy9Qu/
qDJlpPvhXQwxcanwpSS7LOe87NlIxyt2LMLr6VmOryeTcLdebPDZhUTaCxKdoqUy
eCN5XntYsLIcE+zk4EgQoAEatFj2N72t0n///1Zprsps2CZEE6FWAfyvgp4sPKnf
HpiAVgRN79aIegqfrjin0jTizJfpLhOMICpuRCBec2BthRbuBR5aXSPQ8LXyzJFN
6Vo3RwyC0PBci/OQxABgUKkCAwEAAQ==
-----END PUBLIC KEY-----';
	const NODE5_SSL_KEY_PUB = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAs/VYhvwdV9ww8XQTlcCU
BOa6fdKhcJ19tfedSZ/UxILRJdJIaO/vmgXlBg9+K8PagkxABgzz83OcZwp4XsS6
B18WX1zRrCVyRXP3wMefRv44q+9qn2JN6U3siB2Vkh2ARG5QfFTzIyIJXgX7nU98
HOuiy5zpwCH9fxypqFURVzsBQ0HxTvfHW0d4Td4mJeiNtd4aCquwCVxrzVBUaa+s
YmXPHJ56RbeWGSypIlAvfPAq7oRO0JsPGZyVEYO6PWzNTwCEgZvdfoO/9ddCXd6X
toP8RfIoR3SiVefzLeRBuR0jQ0o++hPMnRCCHm7BUpbE2aTTv6q63d9I43gB1nGH
gl39CaEDn7IvLKkFu/ltaCA6jkZHV4izQXt7fhQGovV2mgsU60lqtThmWDuavV4I
yD17OLI4tUP7dpv9yrTN3pNUjrHfDCC2QY2TYJw/iG+BjaR/5iyEBmNEHknHmgm3
y9MvarGoDXg9JJ4ofZ6epysV2iW/JiPFzAmaVPgWEOaEh+WA+9WxFp5YAp9Uf8oh
VLm/ARle/gLdp0/pra8B1AWSpvkaYbmCJEiOvuM8tzxLfX5O10UdcR67jooHdsU7
KG43fr2B2ba/b77pCJZCB1/pDFYsViirsfqW0P4UAR6vIHKgcHYz1gvtrBuu61JO
nx+hUJnDdYkHKNZibhlsXNECAwEAAQ==
-----END PUBLIC KEY-----';
	
	protected static $cronjob = null;
	protected static $nodes = array();
	protected static $msgs = array();
	
	public static function setUpBeforeClass(){
		fwrite(STDOUT, __METHOD__.''."\n");
		
		$settings = new Settings('./settings.yml');
		
		$localNode = new Node();
		$localNode->setIdHexStr($settings->data['node']['id']);
		$localNode->setPort($settings->data['node']['port']);
		$localNode->setSslKeyPub(file_get_contents($settings->data['node']['sslKeyPubPath']));
		
		self::$nodes[0] = new Node();
		self::$nodes[0]->setIdHexStr('10000000-1000-4001-8001-100000000000');
		self::$nodes[0]->setSslKeyPub(static::NODE0_SSL_KEY_PUB);
		
		self::$nodes[1] = new Node();
		self::$nodes[1]->setIdHexStr('10000000-1000-4001-8001-100000000001');
		
		self::$nodes[2] = new Node();
		self::$nodes[2]->setIdHexStr('10000000-1000-4001-8001-100000000002');
		self::$nodes[2]->setSslKeyPub(static::NODE2_SSL_KEY_PUB);
		
		self::$nodes[3] = new Node();
		self::$nodes[3]->setIdHexStr('10000000-1000-4001-8001-100000000003');
		
		self::$nodes[4] = new Node();
		self::$nodes[4]->setIdHexStr('10000000-1000-4001-8001-100000000004');
		self::$nodes[4]->setSslKeyPub(static::NODE4_SSL_KEY_PUB);
		
		self::$nodes[5] = new Node();
		self::$nodes[5]->setIdHexStr('10000000-1000-4001-8001-100000000005');
		self::$nodes[5]->setSslKeyPub(static::NODE5_SSL_KEY_PUB);
		
		$table = new Table();
		$table->setDatadirBasePath($settings->data['datadir']);
		$table->setLocalNode($localNode);
		$table->nodeEnclose(self::$nodes[0]);
		$table->nodeEnclose(self::$nodes[1]);
		$table->nodeEnclose(self::$nodes[2]);
		#$table->nodeEnclose(self::$nodes[3]); // Test not in table.
		#$table->nodeEnclose(self::$nodes[4]);
		#$table->nodeEnclose(self::$nodes[5]);
		
		$msgDb = new MsgDb();
		$msgDb->setDatadirBasePath($settings->data['datadir']);
		
		for($nodeNo = 1000; $nodeNo <= 1011; $nodeNo++){
			$msg = new Msg();
			
			$msg->setId('20000000-2000-4002-8002-20000000'.$nodeNo);
			self::assertEquals('20000000-2000-4002-8002-20000000'.$nodeNo, $msg->getId());
			
			$msg->setSrcNodeId($settings->data['node']['id']);
			$msg->setSrcSslKeyPub($table->getLocalNode()->getSslKeyPub());
			$msg->setSrcUserNickname($settings->data['user']['nickname']);
			
			$msg->setText('this is  a test. '.date('Y/m/d H:i:s'));
			$msg->setSslKeyPrvPath($settings->data['node']['sslKeyPrvPath'], $settings->data['node']['sslKeyPrvPass']);
			$msg->setStatus('O');
			
			$msg->setDstNodeId( self::$nodes[0]->getIdHexStr() );
			
			$msg->setEncryptionMode('D');
			$msg->setDstSslPubKey( self::$nodes[0]->getSslKeyPub() );
			
			self::$msgs[$nodeNo] = $msg;
		}
		
		self::$msgs[1001]->setDstNodeId( self::$nodes[1]->getIdHexStr() );
		self::$msgs[1001]->setEncryptionMode('S');
		self::$msgs[1001]->setDstSslPubKey($table->getLocalNode()->getSslKeyPub());
		self::assertEquals('S', self::$msgs[1001]->getEncryptionMode());
		
		self::$msgs[1002]->setDstNodeId( self::$nodes[2]->getIdHexStr() );
		self::$msgs[1002]->setEncryptionMode('S');
		self::$msgs[1002]->setDstSslPubKey($table->getLocalNode()->getSslKeyPub());
		self::assertEquals('S', self::$msgs[1002]->getEncryptionMode());
		
		self::$msgs[1003]->setDstNodeId( self::$nodes[3]->getIdHexStr() );
		self::$msgs[1003]->setEncryptionMode('S');
		self::$msgs[1003]->setDstSslPubKey($table->getLocalNode()->getSslKeyPub());
		self::assertEquals('S', self::$msgs[1003]->getEncryptionMode());
		
		self::$msgs[1004]->setSentNodes(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10));
		self::assertEquals(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10), self::$msgs[1004]->getSentNodes());
		
		self::$msgs[1005]->setStatus('U');
		
		self::$msgs[1006]->setStatus('S');
		
		self::$msgs[1007]->setStatus('S');
		self::$msgs[1007]->setSentNodes(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11,
			12, 13, 14, 15, 16, 17, 18, 19, 20, 21));
		
		self::$msgs[1008]->setStatus('S');
		self::$msgs[1008]->setForwardCycles(110);
		self::$msgs[1008]->setSentNodes(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10));
		
		self::$msgs[1009]->setStatus('S');
		self::$msgs[1009]->setSentNodes(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, '10000000-1000-4001-8001-100000000000'));
		
		self::$msgs[1010]->setRelayNodeId('10000000-1000-4001-8001-100000000001');
		
		self::$msgs[1011]->setSrcNodeId( self::$nodes[4]->getIdHexStr() );
		self::$msgs[1011]->setDstNodeId( self::$nodes[5]->getIdHexStr() );
		self::$msgs[1011]->setStatus('U');
		
		self::$cronjob = new Cronjob();
		self::$cronjob->setMsgDb($msgDb);
		self::$cronjob->setSettings($settings);
		self::$cronjob->setTable($table);
	}
	
	public function testEncrpt(){
		foreach(self::$msgs as $msgId => $msg){
			#fwrite(STDOUT, __METHOD__.': '.$msgId."\n");
			
			$encrypted = false;
			try{
				$encrypted = $msg->encrypt();
			}
			catch(Exception $e){
				print 'ERROR: '.$e->getMessage().PHP_EOL;
			}
			$this->assertTrue($encrypted);
			
			$rv = self::$cronjob->getMsgDb()->msgAdd($msg);
		}
	}
	
	public function testMsgDbInitNodes(){
		self::$cronjob->msgDbInitNodes();
		
		$msgs = self::$cronjob->getMsgDb()->getMsgs();
		
		$this->assertGreaterThanOrEqual(3, self::$cronjob->getMsgDb()->getMsgsCount() );
		$this->assertGreaterThanOrEqual(3, count($msgs));
		
		$this->assertEquals(self::$msgs[1000], $msgs['20000000-2000-4002-8002-200000001000']);
		$this->assertEquals(self::$msgs[1001], $msgs['20000000-2000-4002-8002-200000001001']);
		$this->assertEquals(self::$msgs[1002], $msgs['20000000-2000-4002-8002-200000001002']);
		$this->assertEquals(self::$msgs[1003], $msgs['20000000-2000-4002-8002-200000001003']);
		$this->assertEquals(self::$msgs[1004], $msgs['20000000-2000-4002-8002-200000001004']);
		
		$this->assertEquals('D', $msgs['20000000-2000-4002-8002-200000001000']->getEncryptionMode());
		$this->assertEquals('S', $msgs['20000000-2000-4002-8002-200000001001']->getEncryptionMode());
		$this->assertEquals('D', $msgs['20000000-2000-4002-8002-200000001002']->getEncryptionMode());
		$this->assertEquals('S', $msgs['20000000-2000-4002-8002-200000001003']->getEncryptionMode());
	}
	
	public function testMsgDbSendAll(){
		#$this->markTestIncomplete('This test has not been implemented yet.');
		
		$updateMsgs = self::$cronjob->msgDbSendAll();
		#ve($updateMsgs);
		
		#fwrite(STDOUT, __METHOD__.': '.self::$msgs[1007]->getStatus()."\n");
		
		foreach($updateMsgs as $msgId => $msg){
			fwrite(STDOUT, __METHOD__.': '.$msg->getId().', '.$msg->getStatus().', '.$msg->getDstNodeId()."\n");
		}
		
		$this->assertFalse(array_key_exists('20000000-2000-4002-8002-200000001007', $updateMsgs));
		$this->assertEquals('X', self::$msgs[1007]->getStatus());
		
		$this->assertFalse(array_key_exists('20000000-2000-4002-8002-200000001008', $updateMsgs));
		$this->assertEquals('X', self::$msgs[1008]->getStatus());
		
		$this->assertFalse(array_key_exists('20000000-2000-4002-8002-200000001009', $updateMsgs));
		$this->assertEquals('D', self::$msgs[1009]->getStatus());
		
	}
	
}
