<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;
use TheFox\PhpChat\Cronjob;
use TheFox\PhpChat\MsgDb;
use TheFox\PhpChat\Msg;
use TheFox\PhpChat\Settings;
use TheFox\Dht\Simple\Table;
use TheFox\Dht\Kademlia\Node;
use TheFox\PhpChat\NodesNewDb;
use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler as LoggerStreamHandler;

class CronjobTest extends PHPUnit_Framework_TestCase{
	
	const NODE_LOCAL_SSL_KEY_PRV = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,3816DB7169EE9CF2

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
-----END RSA PRIVATE KEY-----
';
	const NODE_LOCAL_SSL_KEY_PUB = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAznSTQMh8UIkcVEtzqOUi
H0+EW8d1y1G/wE9UFjVHiVs9lj0oGifkPXGdNM8+5BmHF7XGGaVT6pRhmyAbI6Ua
eZdDKMPe29wELizLlvVRr/6EH6K667KKPG2o1EJp0uamyYkcJfj5qaBN6gVDzFUJ
DzzQLnng25F1Rus5ZLdS3zhs5G6HlFpg2asqOb9+jmBej5doeldh5KKxYDYW3yxf
FytBG2ReUthJIoA1IhAJXeFojQSEBJ8hJn4f8dBkFzZuxiFCYIonPhT4+vOw0FMj
s5HlS/G/xcAGPfM3yCdc+jtPYwiXqvdnCLUkKY0/L44X3vqy1J46ukF/9EFfFUG/
jLKnWEkGBqh2VkC7EixGqU9axB41MevbFlrFmaeW/8o9bxZVbKqYl3tv26wxEH5Y
HVSQixlBIiEQ2EiVoFbUgHGLcNJXRxkdU4YzYDRaL5wrn6jF5bigHUdqko3+c4nY
u/fxl0//YxtjoZolv2PNVQioZ8Rqpp547dfy0i0qA8jciEQ8vxPF77vSkaYGyc2v
EfkgsdcYjokApKmHGnzKzgSuSdWXWrEquf0vWGolXqQPGgpcDQ4ADFDSUKzfrCAi
ySj0UbxOszWhy+YSOG94NkyZXu9LtydlWdU7lBL6PtwkOGh4KdDekMOxeW96269T
KvIgKHuK+G9nj9AMzpIvbWkCAwEAAQ==
-----END PUBLIC KEY-----
';
	
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
-----END PUBLIC KEY-----
';
	const NODE1_SSL_KEY_PUB = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAwXvEYPhRNDaPkoaPrqYD
deHPg6V3tG+g30S1FEWD8Lgb20adYuglrdKq1uUmjACWl5QB38I++1+UUUmez+eq
6/ow9XteenCmznAlXtfo8eFGrw6XsldgIashU3Z9LM3aoVTW5rDrLh14m3Lc56CK
YpakTX53czECRkuUlOxB68VoIJKTOjtFWRSvSpfD3GMKKkgKbdC/kBHEfWc5KkIy
RrOxEGSX+B1/Oa9YN85xUIqmW8MgetLTZOyzZObOUDgtqnnrGXa98vE74xfT48zD
wPmXlPdc8Djy0i63GxiDqrHHMEmsflILS3yt9qo/ewRX73p61NUQibv8TtDYRleF
fKRaPO534VB3nN38DTWhzYPAooxhEM+14L2aV63iD8HsepSPvq6MFLsTh2Z3qVTq
rOVg26MDri063F2cHdhHszzoUeL717uyMuAnkJkRpAql7Y3Vq8/qFdNQWoSTntk5
pmStANQaXEKIiboX0nU2ZkQjTidaqEEAwt4fqFMsPHBcQqyZHTSc365TVi/IU6q6
w1EUlWW8zUtHhlSZdy9FGJZjOYlSENuDSwLD2FeeJRfTUqe1oAyhTaHWZI639Vxo
miGuHBLwWgNEuYk/awstYK8RAHzkQ3A0QaaegG7n8iHQpIwe5zp+PmI+VF2j1Jxx
0RZS+oG3g68cy4U/IklrFWECAwEAAQ==
-----END PUBLIC KEY-----
';
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
-----END PUBLIC KEY-----
';
	const NODE3_SSL_KEY_PUB = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAzZ6R9xKIyasuKdgqBT6i
ZabdF2C28APtE3/Fk0bwMI2arWIaZndc0LTXc7cjGJIkHwr/PE4sEPMijN4OzhJU
wsygK7XALYFc1z4U95uG7A581pjJbcIjrwdCvyq225ULdN9yfUkcp8LBM68oa0Y1
FuebqThdzh6g4rH9fxxfoxf/M9SMmTni2XSUvp+NJ46ej1HvgtOduy/+wL6MDaFD
DKWrLCseaNHJ8e0FOGwziuH70k2o1tEK6G71exRYjO4j7yRAC9wyJbSUyA19yhO8
rnYewt5clS0ztIrJwGhuv5VIjgTb+yO9SFK+Xu+HEmYRafO7KAkF6VQj4ZE+3wyR
3zO4sOcB/IgK0GqYqK4JDqCXoakzWTMLsEyFaFNuhFnjcVNIi6pMW0BYj6nkd7x1
j5gfNhw6qAp+FtfNIPqBtosh16Ejr82BPrNH361x5gQC5nTSCE0qEvJbmWcWlHfl
EImj9TRZu8uE3jRh3If8padF1KSGvEVxIC1m/UDGwdls2V9lJ7THXXC+S/dosrr5
zv/Db7Jze0ciMz/GEZuxawhdeSLgnlwYaUqjebh27o+Md47T94iIiNMrp0bYZ2ts
t6QRVUqOCJfest5TsW4aXYbdBLXdPe+w7qZn8nd74yHX8Dn378JR9tLd2bJcZRZ4
ADnq5PkXUx130syhP4qjMDUCAwEAAQ==
-----END PUBLIC KEY-----
';
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
-----END PUBLIC KEY-----
';
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
-----END PUBLIC KEY-----
';
	
	public function testMsgDbDefault(){
		$uuid1 = '10000000-1000-4001-8001-1000000000';
		$uuid2 = '20000000-2000-4002-8002-20000000';
		
		@unlink('test_data/bucket_root.yml');
		
		$runName = uniqid('', true);
		$prvFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.prv';
		$pubFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.pub';
		$settignsFileName = 'testfile_cronjob_settings_'.date('Ymd_His').'_'.$runName.'.pub';
		
		file_put_contents('test_data/'.$prvFileName, static::NODE_LOCAL_SSL_KEY_PRV);
		file_put_contents('test_data/'.$pubFileName, static::NODE_LOCAL_SSL_KEY_PUB);
		
		$settings = new Settings('test_data/'.$settignsFileName);
		$settings->data['datadir'] = 'test_data';
		$settings->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB);
		$settings->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings->data['node']['sslKeyPrvPath'] = 'test_data/'.$prvFileName;
		$settings->data['node']['sslKeyPubPath'] = 'test_data/'.$pubFileName;
		$settings->data['node']['bridge']['client']['enabled'] = false;
		#$settings->setDataChanged(true);
		#$settings->save();
		
		$localNode = new Node();
		$localNode->setIdHexStr($settings->data['node']['id']);
		$localNode->setUri($settings->data['node']['uriLocal']);
		$localNode->setSslKeyPub(file_get_contents($settings->data['node']['sslKeyPubPath']));
		
		$this->assertEquals(static::NODE_LOCAL_SSL_KEY_PUB, $localNode->getSslKeyPub());
		
		// @codingStandardsIgnoreStart
		#$this->assertEquals('FC_WwG2GdTmCLSKhpEmJso6pejm9c6oACjX', Node::genSslKeyFingerprint($localNode->getSslKeyPub()));
		$this->assertEquals('FC_6t6Z9dYVWEDfEzGQGDSAteLQsFE8SDwZFK2PoiQuM2ezFUA2yNpBPiT9oBwvFBfzDWZzZF5sxBtcSd', Node::genSslKeyFingerprint($localNode->getSslKeyPub()));
		// @codingStandardsIgnoreEnd
		
		$nodes = array();
		$nodes[0] = new Node();
		$nodes[0]->setIdHexStr($uuid1.'00');
		$nodes[0]->setUri('tcp://127.0.0.0:25000');
		$nodes[0]->setSslKeyPub(static::NODE0_SSL_KEY_PUB);
		
		$nodes[1] = new Node();
		$nodes[1]->setIdHexStr($uuid1.'01');
		$nodes[1]->setUri('tcp://127.0.0.1:25000');
		
		$nodes[2] = new Node();
		$nodes[2]->setIdHexStr($uuid1.'02');
		$nodes[2]->setUri('tcp://127.0.0.2:25000');
		$nodes[2]->setSslKeyPub(static::NODE2_SSL_KEY_PUB);
		
		$nodes[3] = new Node();
		$nodes[3]->setIdHexStr($uuid1.'03');
		$nodes[3]->setUri('tcp://127.0.0.3:25000');
		
		$nodes[4] = new Node();
		$nodes[4]->setIdHexStr($uuid1.'04');
		$nodes[4]->setUri('tcp://127.0.0.4:25000');
		$nodes[4]->setSslKeyPub(static::NODE4_SSL_KEY_PUB);
		
		$nodes[5] = new Node();
		$nodes[5]->setIdHexStr($uuid1.'05');
		$nodes[5]->setUri('tcp://127.0.0.5:25000');
		$nodes[5]->setSslKeyPub(static::NODE5_SSL_KEY_PUB);
		
		$table = new Table();
		$table->setDatadirBasePath($settings->data['datadir']);
		$table->setLocalNode($localNode);
		$table->nodeEnclose($nodes[0]);
		$table->nodeEnclose($nodes[1]);
		$table->nodeEnclose($nodes[2]);
		#$table->nodeEnclose($nodes[3]); // Test not in table.
		#$table->nodeEnclose($nodes[4]);
		#$table->nodeEnclose($nodes[5]);
		
		$msgs = array();
		for($nodeNo = 1000; $nodeNo <= 1011; $nodeNo++){
			$msg = new Msg();
			
			$msg->setId($uuid2.$nodeNo);
			$this->assertEquals($uuid2.$nodeNo, $msg->getId());
			
			$msg->setSrcNodeId($settings->data['node']['id']);
			$msg->setSrcSslKeyPub($table->getLocalNode()->getSslKeyPub());
			$msg->setSrcUserNickname($settings->data['user']['nickname']);
			
			$msg->setText('this is  a test. '.date('Y-m-d H:i:s'));
			$msg->setSslKeyPrvPath(
				$settings->data['node']['sslKeyPrvPath'], $settings->data['node']['sslKeyPrvPass']);
			$msg->setStatus('O');
			
			$msg->setDstNodeId( $nodes[0]->getIdHexStr() );
			
			$msg->setEncryptionMode('D');
			$msg->setDstSslPubKey( $nodes[0]->getSslKeyPub() );
			
			$msgs[$nodeNo] = $msg;
			
			#fwrite(STDOUT, __METHOD__.' msg setup: '.$nodeNo.''.PHP_EOL);
		}
		
		$msgs[1001]->setDstNodeId( $nodes[1]->getIdHexStr() );
		$msgs[1001]->setEncryptionMode('S');
		$msgs[1001]->setDstSslPubKey($table->getLocalNode()->getSslKeyPub());
		$this->assertEquals('S', $msgs[1001]->getEncryptionMode());
		
		$msgs[1002]->setDstNodeId( $nodes[2]->getIdHexStr() );
		$msgs[1002]->setEncryptionMode('S');
		$msgs[1002]->setDstSslPubKey($table->getLocalNode()->getSslKeyPub());
		$this->assertEquals('S', $msgs[1002]->getEncryptionMode());
		
		$msgs[1003]->setDstNodeId( $nodes[3]->getIdHexStr() );
		$msgs[1003]->setEncryptionMode('S');
		$msgs[1003]->setDstSslPubKey($table->getLocalNode()->getSslKeyPub());
		$this->assertEquals('S', $msgs[1003]->getEncryptionMode());
		
		$msgs[1004]->setSentNodes(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10));
		$this->assertEquals(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10), $msgs[1004]->getSentNodes());
		
		$msgs[1005]->setStatus('U');
		
		$msgs[1006]->setStatus('S');
		
		$msgs[1007]->setStatus('S');
		$msgs[1007]->setSentNodes(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21));
		
		$msgs[1008]->setStatus('S');
		$msgs[1008]->setForwardCycles(110);
		$msgs[1008]->setSentNodes(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10));
		
		$msgs[1009]->setStatus('S');
		$msgs[1009]->setSentNodes(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, $uuid1.'00'));
		
		$msgs[1010]->setRelayNodeId($uuid1.'01');
		
		$msgs[1011]->setSrcNodeId( $nodes[4]->getIdHexStr() );
		$msgs[1011]->setDstNodeId( $nodes[5]->getIdHexStr() );
		$msgs[1011]->setStatus('U');
		
		
		$msgDb = new MsgDb();
		$msgDb->setDatadirBasePath($settings->data['datadir']);
		
		$cronjobLog = new Logger('cronjob');
		#$cronjobLog->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$cronjob = new Cronjob();
		$cronjob->setLog($cronjobLog);
		$cronjob->setMsgDb($msgDb);
		$cronjob->setSettings($settings);
		$cronjob->setTable($table);
		
		// Encrypt
		foreach($msgs as $msgId => $msg){
			#fwrite(STDOUT, __METHOD__.' encrypt: '.$msgId.PHP_EOL);
			
			$encrypted = false;
			try{
				$encrypted = $msg->encrypt();
			}
			catch(Exception $e){
				print 'ERROR: '.$e->getMessage().PHP_EOL;
			}
			$this->assertTrue($encrypted);
			
			$rv = $cronjob->getMsgDb()->msgAdd($msg);
		}
		$this->assertEquals(12, $cronjob->getMsgDb()->getMsgsCount());
		
		
		// Init Nodes
		#fwrite(STDOUT, __METHOD__.' init nodes'.PHP_EOL);
		$cronjob->msgDbInitNodes();
		
		$cronjobMsgs = $cronjob->getMsgDb()->getMsgs();
		
		#foreach($cronjobMsgs as $msgId => $msg){
		#	$logOut = $msg->getId().', '.$msg->getStatus().', '.$msg->getEncryptionMode();
		#	fwrite(STDOUT, __METHOD__.' cronjobMsgs: '.$logOut.PHP_EOL);
		#}
		
		$this->assertEquals(12, count($cronjobMsgs));
		
		$this->assertEquals($msgs[1000], $cronjobMsgs[$uuid2.'1000']);
		$this->assertEquals($msgs[1001], $cronjobMsgs[$uuid2.'1001']);
		$this->assertEquals($msgs[1002], $cronjobMsgs[$uuid2.'1002']);
		$this->assertEquals($msgs[1003], $cronjobMsgs[$uuid2.'1003']);
		$this->assertEquals($msgs[1004], $cronjobMsgs[$uuid2.'1004']);
		$this->assertEquals($msgs[1005], $cronjobMsgs[$uuid2.'1005']);
		$this->assertEquals($msgs[1006], $cronjobMsgs[$uuid2.'1006']);
		$this->assertEquals($msgs[1007], $cronjobMsgs[$uuid2.'1007']);
		$this->assertEquals($msgs[1008], $cronjobMsgs[$uuid2.'1008']);
		$this->assertEquals($msgs[1009], $cronjobMsgs[$uuid2.'1009']);
		$this->assertEquals($msgs[1010], $cronjobMsgs[$uuid2.'1010']);
		$this->assertEquals($msgs[1011], $cronjobMsgs[$uuid2.'1011']);
		
		$this->assertEquals('D', $cronjobMsgs[$uuid2.'1000']->getEncryptionMode());
		$this->assertEquals('S', $cronjobMsgs[$uuid2.'1001']->getEncryptionMode());
		$this->assertEquals('D', $cronjobMsgs[$uuid2.'1002']->getEncryptionMode());
		$this->assertEquals('S', $cronjobMsgs[$uuid2.'1003']->getEncryptionMode());
		$this->assertEquals('D', $cronjobMsgs[$uuid2.'1004']->getEncryptionMode());
		$this->assertEquals('D', $cronjobMsgs[$uuid2.'1005']->getEncryptionMode());
		$this->assertEquals('D', $cronjobMsgs[$uuid2.'1006']->getEncryptionMode());
		$this->assertEquals('D', $cronjobMsgs[$uuid2.'1007']->getEncryptionMode());
		$this->assertEquals('D', $cronjobMsgs[$uuid2.'1008']->getEncryptionMode());
		$this->assertEquals('D', $cronjobMsgs[$uuid2.'1009']->getEncryptionMode());
		$this->assertEquals('D', $cronjobMsgs[$uuid2.'1010']->getEncryptionMode());
		$this->assertEquals('D', $cronjobMsgs[$uuid2.'1011']->getEncryptionMode());
		
		
		$updateMsgs = $cronjob->msgDbSendAll();
		
		/*foreach($updateMsgs as $msgId => $msg){
			$logOut = '/'.$msg['obj']->getId().'/';
			$logOut .= ' /'.$msg['obj']->getStatus().'/';
			$logOut .= ' /'.$msg['obj']->getEncryptionMode().'/'.' '.count($msg['nodes']);
			fwrite(STDOUT, __METHOD__.' update msg: '.$logOut.PHP_EOL);
			#ve($msg['nodes']);
		}*/
		
		$this->assertEquals(6, count($updateMsgs));
		
		$this->assertEquals('O', $msgs[1000]->getStatus());
		$this->assertEquals('O', $msgs[1002]->getStatus());
		$this->assertEquals('U', $msgs[1005]->getStatus());
		$this->assertEquals('S', $msgs[1006]->getStatus());
		$this->assertEquals('O', $msgs[1010]->getStatus());
		$this->assertEquals('U', $msgs[1011]->getStatus());
		
		$this->assertEquals('D', $msgs[1000]->getEncryptionMode());
		$this->assertEquals('D', $msgs[1002]->getEncryptionMode());
		$this->assertEquals('D', $msgs[1005]->getEncryptionMode());
		$this->assertEquals('D', $msgs[1006]->getEncryptionMode());
		$this->assertEquals('D', $msgs[1010]->getEncryptionMode());
		$this->assertEquals('D', $msgs[1011]->getEncryptionMode());
		
		$this->assertTrue(array_key_exists($uuid2.'1000', $updateMsgs));
		$this->assertTrue(array_key_exists($uuid2.'1002', $updateMsgs));
		$this->assertTrue(array_key_exists($uuid2.'1005', $updateMsgs));
		$this->assertTrue(array_key_exists($uuid2.'1006', $updateMsgs));
		$this->assertTrue(array_key_exists($uuid2.'1010', $updateMsgs));
		$this->assertTrue(array_key_exists($uuid2.'1011', $updateMsgs));
		
		$this->assertEquals(3, count($updateMsgs[$uuid2.'1000']['nodes']));
		$this->assertEquals(3, count($updateMsgs[$uuid2.'1002']['nodes']));
		$this->assertEquals(3, count($updateMsgs[$uuid2.'1005']['nodes']));
		$this->assertEquals(3, count($updateMsgs[$uuid2.'1006']['nodes']));
		$this->assertEquals(2, count($updateMsgs[$uuid2.'1010']['nodes']));
		$this->assertEquals(3, count($updateMsgs[$uuid2.'1011']['nodes']));
		
		$this->assertTrue(array_key_exists($uuid1.'00', $updateMsgs[$uuid2.'1000']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'01', $updateMsgs[$uuid2.'1000']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'02', $updateMsgs[$uuid2.'1000']['nodes']));
		
		$this->assertTrue(array_key_exists($uuid1.'00', $updateMsgs[$uuid2.'1002']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'01', $updateMsgs[$uuid2.'1002']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'02', $updateMsgs[$uuid2.'1002']['nodes']));
		
		$this->assertTrue(array_key_exists($uuid1.'00', $updateMsgs[$uuid2.'1005']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'01', $updateMsgs[$uuid2.'1005']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'02', $updateMsgs[$uuid2.'1005']['nodes']));
		
		$this->assertTrue(array_key_exists($uuid1.'00', $updateMsgs[$uuid2.'1006']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'01', $updateMsgs[$uuid2.'1006']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'02', $updateMsgs[$uuid2.'1006']['nodes']));
		
		$this->assertTrue(array_key_exists($uuid1.'00', $updateMsgs[$uuid2.'1010']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'02', $updateMsgs[$uuid2.'1010']['nodes']));
		
		$this->assertTrue(array_key_exists($uuid1.'00', $updateMsgs[$uuid2.'1011']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'01', $updateMsgs[$uuid2.'1011']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'02', $updateMsgs[$uuid2.'1011']['nodes']));
	}
	
	public function testMsgDbBridge(){
		$uuid1 = '11000000-1000-4001-8001-1000000000';
		$uuid2 = '21000000-2000-4002-8002-20000000';
		
		$runName = uniqid('', true);
		$prvFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.prv';
		$pubFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.pub';
		$settignsFileName = 'testfile_cronjob_settings_'.date('Ymd_His').'_'.$runName.'.pub';
		
		file_put_contents('test_data/'.$prvFileName, static::NODE_LOCAL_SSL_KEY_PRV);
		file_put_contents('test_data/'.$pubFileName, static::NODE_LOCAL_SSL_KEY_PUB);
		
		$settings = new Settings('test_data/'.$settignsFileName);
		$settings->data['datadir'] = 'test_data';
		$settings->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB);
		$settings->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings->data['node']['sslKeyPrvPath'] = 'test_data/'.$prvFileName;
		$settings->data['node']['sslKeyPubPath'] = 'test_data/'.$pubFileName;
		$settings->data['node']['bridge']['client']['enabled'] = true;
		
		$localNode = new Node();
		$localNode->setIdHexStr($settings->data['node']['id']);
		$localNode->setUri($settings->data['node']['uriLocal']);
		$localNode->setSslKeyPub(file_get_contents($settings->data['node']['sslKeyPubPath']));
		
		
		$nodes = array();
		
		$nodes[1] = new Node();
		$nodes[1]->setIdHexStr($uuid1.'01');
		$nodes[1]->setUri('tcp://127.0.0.1:25000');
		$nodes[1]->setSslKeyPub(static::NODE1_SSL_KEY_PUB);
		
		$nodes[2] = new Node();
		$nodes[2]->setIdHexStr($uuid1.'02');
		$nodes[2]->setUri('tcp://127.0.0.2:25000');
		$nodes[2]->setSslKeyPub(static::NODE2_SSL_KEY_PUB);
		
		// Bridge Server
		$nodes[3] = new Node();
		$nodes[3]->setIdHexStr($uuid1.'03');
		$nodes[3]->setUri('tcp://127.0.0.3:25000');
		$nodes[3]->setSslKeyPub(static::NODE3_SSL_KEY_PUB);
		$nodes[3]->setBridgeServer(true);
		
		// Bridge Server
		$nodes[4] = new Node();
		$nodes[4]->setIdHexStr($uuid1.'04');
		$nodes[4]->setUri('tcp://127.0.0.4:25000');
		$nodes[4]->setSslKeyPub(static::NODE4_SSL_KEY_PUB);
		$nodes[4]->setBridgeServer(true);
		
		$nodes[5] = new Node();
		$nodes[5]->setIdHexStr($uuid1.'05');
		$nodes[5]->setUri('tcp://127.0.0.5:25000');
		$nodes[5]->setSslKeyPub(static::NODE5_SSL_KEY_PUB);
		
		$table = new Table();
		$table->setDatadirBasePath($settings->data['datadir']);
		$table->setLocalNode($localNode);
		
		$table->nodeEnclose($nodes[1]);
		$table->nodeEnclose($nodes[2]);
		$table->nodeEnclose($nodes[3]);
		$table->nodeEnclose($nodes[4]);
		$table->nodeEnclose($nodes[5]);
		
		
		$msgs = array();
		for($nodeNo = 2001; $nodeNo <= 2004; $nodeNo++){
			$msg = new Msg();
			
			$msg->setId($uuid2.$nodeNo);
			$msg->setSrcNodeId($settings->data['node']['id']);
			$msg->setSrcSslKeyPub($table->getLocalNode()->getSslKeyPub());
			#$msg->setSrcUserNickname($settings->data['user']['nickname']);
			$msg->setText('this is  a test. '.date('Y-m-d H:i:s'));
			$msg->setSslKeyPrvPath($settings->data['node']['sslKeyPrvPath'], $settings->data['node']['sslKeyPrvPass']);
			$msg->setStatus('O');
			#$msg->setDstNodeId($nodes[0]->getIdHexStr());
			#$msg->setDstSslPubKey($nodes[0]->getSslKeyPub());
			$msg->setEncryptionMode('D');
			
			$msgs[$nodeNo] = $msg;
			
			#fwrite(STDOUT, __METHOD__.' msg setup: '.$nodeNo.' /'.$msg->getId().'/'.PHP_EOL);
		}
		
		$msgs[2001]->setDstNodeId($nodes[1]->getIdHexStr());
		$msgs[2001]->setDstSslPubKey($nodes[1]->getSslKeyPub());
		
		$msgs[2002]->setDstNodeId($nodes[2]->getIdHexStr());
		$msgs[2002]->setDstSslPubKey($nodes[2]->getSslKeyPub());
		
		$msgs[2003]->setDstNodeId($nodes[3]->getIdHexStr());
		$msgs[2003]->setDstSslPubKey($nodes[3]->getSslKeyPub());
		
		// Foreign msg.
		$msgs[2004]->setSrcNodeId($nodes[4]->getIdHexStr());
		$msgs[2004]->setDstNodeId($nodes[5]->getIdHexStr());
		$msgs[2004]->setDstSslPubKey($nodes[5]->getSslKeyPub());
		$msgs[2004]->setStatus('U');
		
		
		$msgDb = new MsgDb();
		$msgDb->setDatadirBasePath($settings->data['datadir']);
		
		$cronjobLog = new Logger('cronjob');
		#$cronjobLog->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$cronjob = new Cronjob();
		$cronjob->setLog($cronjobLog);
		$cronjob->setMsgDb($msgDb);
		$cronjob->setSettings($settings);
		$cronjob->setTable($table);
		
		// Encrypt
		foreach($msgs as $msgId => $msg){
			try{
				$msg->encrypt();
			}
			catch(Exception $e){
				fwrite(STDOUT, 'ERROR: '.$e->getMessage().PHP_EOL);
			}
			$cronjob->getMsgDb()->msgAdd($msg);
		}
		
		// Init Nodes
		#fwrite(STDOUT, __METHOD__.' init nodes'.PHP_EOL);
		$cronjob->msgDbInitNodes();
		
		$cronjobMsgs = $cronjob->getMsgDb()->getMsgs();
		
		#foreach($cronjobMsgs as $msgId => $msg){
		#	$outMsg = '/'.$msg->getId().'/ /'.$msg->getStatus().'/ /'.$msg->getEncryptionMode().'/';
		#	fwrite(STDOUT, __METHOD__.' cronjob msg: '.$outMsg.PHP_EOL);
		#}
		
		$updateMsgs = $cronjob->msgDbSendAll();
		
		/*foreach($updateMsgs as $msgId => $msg){
			$outMsg = '/'.$msg['obj']->getId().'/';
			$outMsg .= ' /'.$msg['obj']->getStatus().'/';
			$outMsg .= ' /'.$msg['obj']->getEncryptionMode().'/ '.count($msg['nodes']);
			fwrite(STDOUT, __METHOD__.' msg: '.$outMsg.PHP_EOL);
			
			foreach($msg['nodes'] as $nodeId => $node){
				$outMsg = $nodeId.' /'.(int)$node->getBridgeServer().'/';
				$outMsg = '/'.(int)is_object($node).'/ /'.$node.'/';
				fwrite(STDOUT, __METHOD__.'     node: '.$outMsg.PHP_EOL);
			}
		}*/
		
		$this->assertEquals('O', $msgs[2001]->getStatus());
		$this->assertEquals('O', $msgs[2002]->getStatus());
		$this->assertEquals('O', $msgs[2003]->getStatus());
		$this->assertEquals('U', $msgs[2004]->getStatus());
		
		$this->assertEquals('D', $msgs[2001]->getEncryptionMode());
		$this->assertEquals('D', $msgs[2002]->getEncryptionMode());
		$this->assertEquals('D', $msgs[2003]->getEncryptionMode());
		$this->assertEquals('D', $msgs[2004]->getEncryptionMode());
		
		$this->assertEquals(2, count($updateMsgs[$uuid2.'2001']['nodes']));
		$this->assertEquals(2, count($updateMsgs[$uuid2.'2002']['nodes']));
		$this->assertEquals(2, count($updateMsgs[$uuid2.'2003']['nodes']));
		$this->assertEquals(2, count($updateMsgs[$uuid2.'2004']['nodes']));
		
		$this->assertTrue(array_key_exists($uuid1.'03', $updateMsgs[$uuid2.'2001']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'04', $updateMsgs[$uuid2.'2001']['nodes']));
		
		$this->assertTrue(array_key_exists($uuid1.'03', $updateMsgs[$uuid2.'2002']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'04', $updateMsgs[$uuid2.'2002']['nodes']));
		
		$this->assertTrue(array_key_exists($uuid1.'03', $updateMsgs[$uuid2.'2003']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'04', $updateMsgs[$uuid2.'2003']['nodes']));
		
		$this->assertTrue(array_key_exists($uuid1.'03', $updateMsgs[$uuid2.'2004']['nodes']));
		$this->assertTrue(array_key_exists($uuid1.'04', $updateMsgs[$uuid2.'2004']['nodes']));
	}
	
	public function testBootstrapNodesEncloseDefault(){
		$runName = uniqid('', true);
		$prvFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.prv';
		$pubFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.pub';
		$settignsFileName = 'testfile_cronjob_settings_'.date('Ymd_His').'_'.$runName.'.pub';
		
		file_put_contents('test_data/'.$prvFileName, static::NODE_LOCAL_SSL_KEY_PRV);
		file_put_contents('test_data/'.$pubFileName, static::NODE_LOCAL_SSL_KEY_PUB);
		
		$settings = new Settings('test_data/'.$settignsFileName);
		$settings->data['datadir'] = 'test_data';
		$settings->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB);
		$settings->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings->data['node']['sslKeyPrvPath'] = 'test_data/'.$prvFileName;
		$settings->data['node']['sslKeyPubPath'] = 'test_data/'.$pubFileName;
		$settings->data['node']['bridge']['client']['enabled'] = false;
		
		$localNode = new Node();
		$localNode->setIdHexStr($settings->data['node']['id']);
		$localNode->setUri($settings->data['node']['uriLocal']);
		$localNode->setSslKeyPub(file_get_contents($settings->data['node']['sslKeyPubPath']));
		
		$table = new Table();
		$table->setDatadirBasePath($settings->data['datadir']);
		$table->setLocalNode($localNode);
		
		$cronjobLog = new Logger('cronjob');
		#$cronjobLog->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$cronjob = new Cronjob();
		$cronjob->setLog($cronjobLog);
		$cronjob->setSettings($settings);
		$cronjob->setTable($table);
		
		
		$jsonSource = json_encode(array(
			'nodes' => array(
				array('uri' => 'tcp://10.0.0.11:25000'),
				array('active' => false, 'id' => 'cafed00d-2131-4159-8e11-0b4dbadb1738'),
				
				array('active' => true),
				array('active' => true, 'id' => $localNode->getIdHexStr()),
				
				array('active' => true, 'id' => 'cafed00d-2131-4159-8e11-0b4dbadb1739'),
				array('active' => true, 'uri' => 'tcp://192.168.241.24'),
				array('active' => true, 'id' => 'cafed00d-2131-4159-8e11-0b4dbadb1740', 'uri' => 'tcp://192.168.241.25'),
				
				array('active' => true, 'id' => 'cafed00d-2131-4159-8e11-0b4dbadb1741', 'bridgeServer' => true),
				array('active' => true, 'uri' => 'tcp://192.168.241.26', 'bridgeServer' => true),
			),
		));
		
		$nodes = $cronjob->bootstrapNodesEncloseJson(json_decode($jsonSource, true));
		#ve($nodes);
		
		$this->assertEquals(5, count($nodes));
		
		$this->assertEquals('find', $nodes[0]['type']);
		$this->assertEquals('connect', $nodes[1]['type']);
		$this->assertEquals('enclose', $nodes[2]['type']);
		$this->assertEquals('find', $nodes[3]['type']);
		$this->assertEquals('connect', $nodes[4]['type']);
		
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1739', $nodes[0]['node']->getIdHexStr());
		$this->assertEquals('00000000-0000-4000-8000-000000000000', $nodes[1]['node']->getIdHexStr());
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1740', $nodes[2]['node']->getIdHexStr());
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1741', $nodes[3]['node']->getIdHexStr());
		$this->assertEquals('00000000-0000-4000-8000-000000000000', $nodes[4]['node']->getIdHexStr());
		
		$this->assertEquals('', (string)$nodes[0]['node']->getUri());
		$this->assertEquals('tcp://192.168.241.24', (string)$nodes[1]['node']->getUri());
		$this->assertEquals('tcp://192.168.241.25', (string)$nodes[2]['node']->getUri());
		$this->assertEquals('', (string)$nodes[3]['node']->getUri());
		$this->assertEquals('tcp://192.168.241.26', (string)$nodes[4]['node']->getUri());
		
		$this->assertFalse($nodes[0]['node']->getBridgeServer());
		$this->assertFalse($nodes[1]['node']->getBridgeServer());
		$this->assertFalse($nodes[2]['node']->getBridgeServer());
		$this->assertTrue($nodes[3]['node']->getBridgeServer());
		$this->assertTrue($nodes[4]['node']->getBridgeServer());
	}
	
	public function testBootstrapNodesEncloseBridge(){
		$runName = uniqid('', true);
		$prvFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.prv';
		$pubFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.pub';
		$settignsFileName = 'testfile_cronjob_settings_'.date('Ymd_His').'_'.$runName.'.pub';
		
		file_put_contents('test_data/'.$prvFileName, static::NODE_LOCAL_SSL_KEY_PRV);
		file_put_contents('test_data/'.$pubFileName, static::NODE_LOCAL_SSL_KEY_PUB);
		
		$settings = new Settings('test_data/'.$settignsFileName);
		$settings->data['datadir'] = 'test_data';
		$settings->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB);
		$settings->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings->data['node']['sslKeyPrvPath'] = 'test_data/'.$prvFileName;
		$settings->data['node']['sslKeyPubPath'] = 'test_data/'.$pubFileName;
		$settings->data['node']['bridge']['client']['enabled'] = true;
		
		$localNode = new Node();
		$localNode->setIdHexStr($settings->data['node']['id']);
		$localNode->setUri($settings->data['node']['uriLocal']);
		$localNode->setSslKeyPub(file_get_contents($settings->data['node']['sslKeyPubPath']));
		
		$table = new Table();
		$table->setDatadirBasePath($settings->data['datadir']);
		$table->setLocalNode($localNode);
		
		$cronjobLog = new Logger('cronjob');
		#$cronjobLog->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$cronjob = new Cronjob();
		$cronjob->setLog($cronjobLog);
		$cronjob->setSettings($settings);
		$cronjob->setTable($table);
		
		$jsonSource = json_encode(array(
			'nodes' => array(
				array('active' => true, 'id' => 'cafed00d-2131-4159-8e11-0b4dbadb1742'),
				array('active' => true, 'uri' => 'tcp://192.168.241.27'),
				array('active' => true, 'id' => 'cafed00d-2131-4159-8e11-0b4dbadb1743', 'uri' => 'tcp://192.168.241.28'),
				
				array('active' => true, 'id' => 'cafed00d-2131-4159-8e11-0b4dbadb1744', 'bridgeServer' => true),
				array('active' => true, 'uri' => 'tcp://192.168.241.29', 'bridgeServer' => true),
				array('active' => true, 'id' => 'cafed00d-2131-4159-8e11-0b4dbadb1745',
					'uri' => 'tcp://192.168.241.30', 'bridgeServer' => true),
			),
		));
		
		$nodes = $cronjob->bootstrapNodesEncloseJson(json_decode($jsonSource, true));
		#ve($nodes);
		
		$this->assertEquals(5, count($nodes));
		
		$this->assertEquals('find', $nodes[0]['type']);
		$this->assertEquals('enclose', $nodes[1]['type']);
		$this->assertEquals('find', $nodes[2]['type']);
		$this->assertEquals('connect', $nodes[3]['type']);
		$this->assertEquals('enclose', $nodes[4]['type']);
		
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1742', $nodes[0]['node']->getIdHexStr());
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1743', $nodes[1]['node']->getIdHexStr());
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1744', $nodes[2]['node']->getIdHexStr());
		$this->assertEquals('00000000-0000-4000-8000-000000000000', $nodes[3]['node']->getIdHexStr());
		$this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1745', $nodes[4]['node']->getIdHexStr());
		
		$this->assertEquals('', (string)$nodes[0]['node']->getUri());
		$this->assertEquals('tcp://192.168.241.28', (string)$nodes[1]['node']->getUri());
		$this->assertEquals('', (string)$nodes[2]['node']->getUri());
		$this->assertEquals('tcp://192.168.241.29', (string)$nodes[3]['node']->getUri());
		$this->assertEquals('tcp://192.168.241.30', (string)$nodes[4]['node']->getUri());
		
		$this->assertFalse($nodes[0]['node']->getBridgeServer());
		$this->assertFalse($nodes[1]['node']->getBridgeServer());
		$this->assertTrue($nodes[2]['node']->getBridgeServer());
		$this->assertTrue($nodes[3]['node']->getBridgeServer());
		$this->assertTrue($nodes[4]['node']->getBridgeServer());
	}
	
	public function testNodesNewEncloseDefault(){
		$runName = uniqid('', true);
		$prvFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.prv';
		$pubFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.pub';
		$settignsFileName = 'testfile_cronjob_settings_'.date('Ymd_His').'_'.$runName.'.pub';
		
		file_put_contents('test_data/'.$prvFileName, static::NODE_LOCAL_SSL_KEY_PRV);
		file_put_contents('test_data/'.$pubFileName, static::NODE_LOCAL_SSL_KEY_PUB);
		
		$settings = new Settings('test_data/'.$settignsFileName);
		$settings->data['datadir'] = 'test_data';
		$settings->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB);
		$settings->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings->data['node']['sslKeyPrvPath'] = 'test_data/'.$prvFileName;
		$settings->data['node']['sslKeyPubPath'] = 'test_data/'.$pubFileName;
		$settings->data['node']['bridge']['client']['enabled'] = false;
		
		$localNode = new Node();
		$localNode->setIdHexStr($settings->data['node']['id']);
		$localNode->setUri($settings->data['node']['uriLocal']);
		$localNode->setSslKeyPub(file_get_contents($settings->data['node']['sslKeyPubPath']));
		
		$table = new Table();
		$table->setDatadirBasePath($settings->data['datadir']);
		$table->setLocalNode($localNode);
		
		$nodesNewDb = new NodesNewDb('test_data/testfile_cronjob_nodesnewdb1.yml');
		$nodesNewDb->nodeAddConnect('tcp://192.168.241.21', false);
		$nodesNewDb->nodeAddConnect('tcp://192.168.241.22', true);
		$nodesNewDb->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1742', false);
		$nodesNewDb->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1743', true);
		#$nodesNewDb->setDataChanged(true);
		#$nodesNewDb->save();
		
		$cronjobLog = new Logger('cronjob');
		#$cronjobLog->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$cronjob = new Cronjob();
		$cronjob->setLog($cronjobLog);
		$cronjob->setSettings($settings);
		$cronjob->setTable($table);
		$cronjob->setNodesNewDb($nodesNewDb);
		
		$nodes = $cronjob->nodesNewEnclose();
		
		$this->assertEquals(4, count($nodes));
		
		$this->assertEquals('connect', $nodes[0]['type']);
		$this->assertEquals('connect', $nodes[1]['type']);
		$this->assertEquals('find', $nodes[2]['type']);
		$this->assertEquals('find', $nodes[3]['type']);
		
		$this->assertTrue(is_object($nodes[0]['node']));
		$this->assertTrue(is_object($nodes[1]['node']));
		$this->assertTrue(is_object($nodes[2]['node']));
		$this->assertTrue(is_object($nodes[3]['node']));
		
		$this->assertFalse($nodes[0]['node']->getBridgeServer());
		$this->assertTrue($nodes[1]['node']->getBridgeServer());
		$this->assertFalse($nodes[2]['node']->getBridgeServer());
		$this->assertTrue($nodes[3]['node']->getBridgeServer());
		
		#$nodesNewDb->setDataChanged(true);
		#$nodesNewDb->save();
	}
	
	public function testNodesNewEncloseBridge(){
		$runName = uniqid('', true);
		$prvFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.prv';
		$pubFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.pub';
		$settignsFileName = 'testfile_cronjob_settings_'.date('Ymd_His').'_'.$runName.'.pub';
		
		file_put_contents('test_data/'.$prvFileName, static::NODE_LOCAL_SSL_KEY_PRV);
		file_put_contents('test_data/'.$pubFileName, static::NODE_LOCAL_SSL_KEY_PUB);
		
		$settings = new Settings('test_data/'.$settignsFileName);
		$settings->data['datadir'] = 'test_data';
		$settings->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB);
		$settings->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings->data['node']['sslKeyPrvPath'] = 'test_data/'.$prvFileName;
		$settings->data['node']['sslKeyPubPath'] = 'test_data/'.$pubFileName;
		$settings->data['node']['bridge']['client']['enabled'] = true;
		
		$localNode = new Node();
		$localNode->setIdHexStr($settings->data['node']['id']);
		$localNode->setUri($settings->data['node']['uriLocal']);
		$localNode->setSslKeyPub(file_get_contents($settings->data['node']['sslKeyPubPath']));
		
		$table = new Table();
		$table->setDatadirBasePath($settings->data['datadir']);
		$table->setLocalNode($localNode);
		
		$nodesNewDb = new NodesNewDb('test_data/testfile_cronjob_nodesnewdb2.yml');
		$nodesNewDb->nodeAddConnect('tcp://192.168.241.21', false);
		$nodesNewDb->nodeAddConnect('tcp://192.168.241.22', true);
		$nodesNewDb->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1742', false);
		$nodesNewDb->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1743', true);
		#$nodesNewDb->setDataChanged(true);
		#$nodesNewDb->save();
		
		$cronjobLog = new Logger('cronjob');
		#$cronjobLog->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$cronjob = new Cronjob();
		$cronjob->setLog($cronjobLog);
		$cronjob->setSettings($settings);
		$cronjob->setTable($table);
		$cronjob->setNodesNewDb($nodesNewDb);
		
		$nodes = $cronjob->nodesNewEnclose();
		
		
		$this->assertEquals(4, count($nodes));
		
		$this->assertEquals('remove', $nodes[0]['type']);
		$this->assertEquals('connect', $nodes[1]['type']);
		$this->assertEquals('find', $nodes[2]['type']);
		$this->assertEquals('find', $nodes[3]['type']);
		
		$this->assertTrue(is_object($nodes[0]['node']));
		$this->assertTrue(is_object($nodes[1]['node']));
		$this->assertTrue(is_object($nodes[2]['node']));
		$this->assertTrue(is_object($nodes[3]['node']));
		
		$this->assertFalse($nodes[0]['node']->getBridgeServer());
		$this->assertTrue($nodes[1]['node']->getBridgeServer());
		$this->assertFalse($nodes[2]['node']->getBridgeServer());
		$this->assertTrue($nodes[3]['node']->getBridgeServer());
		
		#$nodesNewDb->setDataChanged(true);
		#$nodesNewDb->save();
	}
	
	public function testCreateGuzzleHttpClient(){
		$runName = uniqid('', true);
		$prvFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.prv';
		$pubFileName = 'testfile_cronjob_id_rsa_'.date('Ymd_His').'_'.$runName.'.pub';
		$settignsFileName = 'testfile_cronjob_settings_'.date('Ymd_His').'_'.$runName.'.pub';
		
		file_put_contents('test_data/'.$prvFileName, static::NODE_LOCAL_SSL_KEY_PRV);
		file_put_contents('test_data/'.$pubFileName, static::NODE_LOCAL_SSL_KEY_PUB);
		
		$settings = new Settings('test_data/'.$settignsFileName);
		$settings->data['datadir'] = 'test_data';
		$settings->data['node']['id'] = Node::genIdHexStr(static::NODE_LOCAL_SSL_KEY_PUB);
		$settings->data['node']['sslKeyPrvPass'] = 'my_password';
		$settings->data['node']['sslKeyPrvPath'] = 'test_data/'.$prvFileName;
		$settings->data['node']['sslKeyPubPath'] = 'test_data/'.$pubFileName;
		$settings->data['node']['bridge']['client']['enabled'] = true;
		
		$localNode = new Node();
		$localNode->setIdHexStr($settings->data['node']['id']);
		$localNode->setUri($settings->data['node']['uriLocal']);
		$localNode->setSslKeyPub(file_get_contents($settings->data['node']['sslKeyPubPath']));
		
		$table = new Table();
		$table->setDatadirBasePath($settings->data['datadir']);
		$table->setLocalNode($localNode);
		
		$nodesNewDb = new NodesNewDb('test_data/testfile_cronjob_nodesnewdb2.yml');
		$nodesNewDb->nodeAddConnect('tcp://192.168.241.21', false);
		$nodesNewDb->nodeAddConnect('tcp://192.168.241.22', true);
		$nodesNewDb->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1742', false);
		$nodesNewDb->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1743', true);
		
		$cronjobLog = new Logger('cronjob');
		#$cronjobLog->pushHandler(new LoggerStreamHandler('php://stdout', Logger::DEBUG));
		
		$cronjob = new Cronjob();
		$cronjob->setLog($cronjobLog);
		$cronjob->setSettings($settings);
		$cronjob->setTable($table);
		$cronjob->setNodesNewDb($nodesNewDb);
		
		$httpClient = $cronjob->createGuzzleHttpClient();
		#fwrite(STDOUT, 'client: '.get_class($httpClient).''.PHP_EOL);
		#\Doctrine\Common\Util\Debug::dump($httpClient);
		$this->assertTrue(is_object($httpClient));
		
		$url = 'http://www.example.com/';
		$response = null;
		try{
			#fwrite(STDOUT, 'get url: '.$url.''.PHP_EOL);
			$request = $httpClient->get($url);
			#fwrite(STDOUT, 'request: '.get_class($request).''.PHP_EOL);
			
			$response = $request->send();
			#fwrite(STDOUT, 'response: '.get_class($response).''.PHP_EOL);
		}
		catch(Exception $e){
			#fwrite(STDOUT, 'url failed, "'.$url.'": '.$e->getMessage().PHP_EOL);
		}
		
		/*if($response){
			fwrite(STDOUT, 'response: '.$response->getStatusCode().PHP_EOL);
			fwrite(STDOUT, 'content-type: '.$response->getHeader('content-type').PHP_EOL);
		}
		else{
			fwrite(STDOUT, 'response failed'.PHP_EOL);
		}*/
	}
	
}
