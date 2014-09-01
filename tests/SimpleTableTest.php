<?php

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use TheFox\Dht\Simple\Table;
use TheFox\Dht\Kademlia\Node;

class SimpleTableTest extends PHPUnit_Framework_TestCase{
	
	const NODE_PUB1 = '-----BEGIN PUBLIC KEY-----
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
	
	const NODE_PUB2 = '-----BEGIN PUBLIC KEY-----
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
	
	const NODE_PUB3 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAyRIx6t6y6v5Z7e40ve2S
8y2897UEz9OPMcs14V0Ld9XsW19R7Kabbb7GSZ08yQ3+FQbasY675YttWjGTj5yz
Chyw7bRtOp13IsWce79lsVGDa54bXf6InDMB1A05YFeGILdF4KqMs/sN5aosFl9W
7iPMVlQEHuBNA/h+dswFW3O2kX7DVT1mYDX1bSMx/xz0HwY7dzD/1Aw0wCidd5uK
gHfYkJMVD9Zk2FtQyDPisz5+FSylaGSx0Jcp1Gn+H+dmMlhPlFoPjoQK+m7DhLOD
MZdVFj6es9Imf+XCpdxwetLwQMVQwlONfTwKFom8VHbUY1nNaaN1Ma6O0xJLdLZN
i822g5IFQuuaR3JDViRL3nKpTj+6XY8+9khOSuLoQi2YDBeBwCe3RbdCHeMFOEIS
VOcHslJm+K7sz/b3l4D7Skr2jNnd1EMoNeLMK8DiafsAc6iYUIEASyGNy0XnFnEq
3Kmr2u44Rw7w/Heudm5RM6BqpQ3uiFwwjW8FKdg17Qf7L9s2zaqFt/5IwB2/dceb
b5xK7OKDkSG1TXPKMOPiQa4ao+ztOa2qDIc9avt4yZWLPg8JpgtHezX2CAvMjFa1
7nkuYMruCGDvX06pbYX7VkxMLTYXbYFIFp5W3+DS8rJubWqdU1Qi28y6GMBtXOG0
axD+8TC4hYpjW6ZnMRKF39kCAwEAAQ==
-----END PUBLIC KEY-----
';
	
	const NODE_PUB4 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA4BCs7V3rNckr8fjfLGb4
iWvnRptQ2BW3ZP/ffGxRUE30F9NUlR2SnIahR9CBXaJ6ZI27dI0u3B1p5el8FBGT
tLGvLnjqsYS/fF8Nbrj2A9t480PH87uDj06uh8Kl6HIIZITwp314lg8nrMoe24X0
rVV/l/tksjKqzenLewW3LBgNHsLYHlLyKw9BLizjOFOlkEc945dVs7SDIXefPEAZ
eOtGkkbHKNXSFQd0HEKIz2XNJGKJLDmSQYs15dCgLNMgk9sBgbYX8ZKvZzR2hpu3
mn4V0tFvamj7k5lVU40m5IaIjdQlY5pHuLdMSpNkosKMX7Ib9Doo2myGcbnr92ox
NUwysEzwgif6czqn3xxh21yrqnjQKywoL9CDWZahA8z0Gc/I6oSzOEzj39Yw7pMq
dhMsOnbypMBbiICbdXOgucPYDeEBT9OW/4uVWmtAV6ZPGS3UOaHxo8fUV6i4Xuuy
MC2gNfNm+ZqfjI8/emAO7JIcwoZSVcgT2yA8M4B0V++I3t9ZXnaeBouc+Gh7TUnI
5dGplYLw9Y/efOyKDd6eowE/bcDnADtIIwHXMaLy3XMhPUAc46vkUIoje9PlKxcy
2WCLfvEUYVLx3nx2rNHhvF6o1XJ8W3HA+HOfE4SbJ1FyvrYmDeHA/yMR2gkhv/h8
ACgdCZcyA+B3xL8UMtVKz4sCAwEAAQ==
-----END PUBLIC KEY-----
';
	
	public function testSerialize(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$localNode->setTimeCreated(1408371221);
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-010000000002');
		$node_a->setTimeCreated(1408371221);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-010000000004');
		$node_b->setTimeCreated(1408371221);
		
		$node_c = new Node();
		$node_c->setIdHexStr('10000001-2002-4004-8008-010000000008');
		$node_c->setTimeCreated(1408371221);
		
		$node_d = new Node();
		$node_d->setIdHexStr('10000001-2002-4004-8008-010000000010');
		$node_d->setTimeCreated(1408371221);
		
		$node_e = new Node();
		$node_e->setIdHexStr('10000001-2002-4004-8008-020000000008');
		$node_e->setTimeCreated(1408371221);
		
		$table->nodeEnclose($node_a);
		$table->nodeEnclose($node_b);
		$table->nodeEnclose($node_c);
		$table->nodeEnclose($node_d);
		$table->nodeEnclose($node_e);
		
		$table = unserialize(serialize($table));
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-010000000002');
		$node_a->setTimeCreated(1408371221);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-010000000004');
		$node_b->setTimeCreated(1408371221);
		
		$node_c = new Node();
		$node_c->setIdHexStr('10000001-2002-4004-8008-010000000008');
		$node_c->setTimeCreated(1408371221);
		
		$node_d = new Node();
		$node_d->setIdHexStr('10000001-2002-4004-8008-010000000010');
		$node_d->setTimeCreated(1408371221);
		
		$node_e = new Node();
		$node_e->setIdHexStr('10000001-2002-4004-8008-020000000008');
		$node_e->setTimeCreated(1408371221);
		
		
		$this->assertEquals($localNode, $table->getLocalNode());
		
		$nodes = $table->getNodes();
		$this->assertEquals(5, count($nodes));
	}
	
	public function testNodeFind1(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_a->setUri('tcp://192.168.241.1');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-100000000002');
		
		$onode = $table->nodeFind($node_b);
		
		$this->assertEquals('192.168.241.1', $onode->getUri()->getHost());
	}
	
	public function testNodeFind2(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_a->setUri('tcp://192.168.241.1');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-100000000003');
		$node_b->setUri('tcp://192.168.241.2');
		
		$onode = $table->nodeFind($node_b);
		$this->assertEquals(null, $onode);
	}
	
	public function testNodeFindByUri(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_a->setUri('tcp://192.168.241.1');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-100000000003');
		$node_b->setUri('tcp://192.168.241.2');
		$table->nodeEnclose($node_b);
		
		$onode = $table->nodeFindByUri('tcp://192.168.241.3');
		$this->assertEquals(null, $onode);
		
		$onode = $table->nodeFindByUri('tcp://192.168.241.2');
		$this->assertEquals($node_b, $onode);
	}
	
	public function testNodeFindClosest(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-010000000002');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-010000000004');
		$table->nodeEnclose($node_b);
		
		$node_c = new Node();
		$node_c->setIdHexStr('10000001-2002-4004-8008-010000000008');
		$table->nodeEnclose($node_c);
		
		$node_d = new Node();
		$node_d->setIdHexStr('10000001-2002-4004-8008-010000000010');
		$table->nodeEnclose($node_d);
		
		
		$node_e = new Node();
		$node_e->setIdHexStr('10000001-2002-4004-8008-020000000008');
		
		$nodes = $table->nodeFindClosest($node_e);
		
		$this->assertEquals(4, count($nodes));
		$this->assertEquals(array($node_c, $node_a, $node_b, $node_d), $nodes);
		#foreach($nodes as $nodeId => $node){
		#	fwrite(STDOUT, 'node: /'.$nodeId.'/ '.$node->getIdHexStr()."\n");
		#}
	}
	
	public function testNodeFindByKeyPubFingerprint(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-010000000002');
		$node_a->setSslKeyPub(static::NODE_PUB1);
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-010000000004');
		$node_b->setSslKeyPub(static::NODE_PUB2);
		$table->nodeEnclose($node_b);
		
		$node_c = new Node();
		$node_c->setIdHexStr('10000001-2002-4004-8008-010000000008');
		$node_c->setSslKeyPub(static::NODE_PUB3);
		$table->nodeEnclose($node_c);
		
		$node_d = new Node();
		$node_d->setIdHexStr('10000001-2002-4004-8008-010000000010');
		$node_d->setSslKeyPub(static::NODE_PUB4);
		$table->nodeEnclose($node_d);
		
		
		$this->assertEquals('FC_6JtmjHgZjZmgc3Q5mts5C2wmiuMa1TWyR', $node_a->getSslKeyPubFingerprint());
		$this->assertEquals('FC_FeYW3XyyaF8cHJXaRT1QJMdQRGsCiiz69', $node_b->getSslKeyPubFingerprint());
		$this->assertEquals('FC_NtKjyedUakTj3MPuckWjF4aY23JvGmm8w', $node_c->getSslKeyPubFingerprint());
		$this->assertEquals('FC_EYj4eSzaxkwMfQkmtdTSBLrx5CZqdXPMq', $node_d->getSslKeyPubFingerprint());
		
		$this->assertEquals($node_a, $table->nodeFindByKeyPubFingerprint('FC_6JtmjHgZjZmgc3Q5mts5C2wmiuMa1TWyR'));
		$this->assertEquals($node_b, $table->nodeFindByKeyPubFingerprint('FC_FeYW3XyyaF8cHJXaRT1QJMdQRGsCiiz69'));
		$this->assertEquals($node_c, $table->nodeFindByKeyPubFingerprint('FC_NtKjyedUakTj3MPuckWjF4aY23JvGmm8w'));
		$this->assertEquals($node_d, $table->nodeFindByKeyPubFingerprint('FC_EYj4eSzaxkwMfQkmtdTSBLrx5CZqdXPMq'));
	}
	
	public function testNodeEnclose1(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node = new Node();
		$node->setIdHexStr('10000001-2002-4004-8008-100000000002');
		
		$onode = $table->nodeEnclose($node);
		
		$this->assertEquals($node, $onode);
	}
	
	public function testNodeEnclose2(){
		$localNode = new Node();
		$localNode->setIdHexStr('10000001-2002-4004-8008-100000000001');
		$table = new Table();
		$table->setLocalNode($localNode);
		
		$node_a = new Node();
		$node_a->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_a->setUri('tcp://192.168.241.1');
		$table->nodeEnclose($node_a);
		
		$node_b = new Node();
		$node_b->setIdHexStr('10000001-2002-4004-8008-100000000002');
		$node_b->setUri('tcp://192.168.241.2');
		
		$onode = $table->nodeEnclose($node_b);
		
		$this->assertFalse($node_a === $node_b);
		$this->assertTrue($node_a === $onode);
		
		$this->assertEquals('192.168.241.1', $onode->getUri()->getHost());
	}
	
	public function testNodeEnclose3a(){
		#fwrite(STDOUT, 'testNodeEnclose3a'.PHP_EOL);
		
		$localNode = new Node();
		$localNode->setIdHexStr('11000001-2002-4004-8008-100000000006');
		
		$table = new Table('tests/testfile_table.yml');
		$table->setDatadirBasePath('tests');
		$table->setLocalNode($localNode);
		#$table->load();
		
		$node0 = new Node();
		$node0->setIdHexStr('11000001-2002-4004-8008-100000000000');
		
		$node1 = new Node();
		$node1->setIdHexStr('11000001-2002-4004-8008-100000000001');
		
		$node2 = new Node();
		$node2->setIdHexStr('11000001-2002-4004-8008-100000000002');
		
		$node3 = new Node();
		$node3->setIdHexStr('11000001-2002-4004-8008-100000000003');
		
		$node4 = new Node();
		$node4->setIdHexStr('11000001-2002-4004-8008-100000000004');
		
		$node5 = new Node();
		$node5->setIdHexStr('11000001-2002-4004-8008-100000000005');
		
		$node7 = new Node();
		$node7->setIdHexStr('11000001-2002-4004-8008-100000000007');
		
		$node20 = new Node();
		$node20->setIdHexStr('11000001-2002-4004-8008-100000000020');
		$node20->setTimeLastSeen(time());
		
		
		$baseBitStr = str_repeat('0', 125);
		$this->assertEquals($baseBitStr.'110', $localNode->distanceBitStr($node0));
		$this->assertEquals($baseBitStr.'111', $localNode->distanceBitStr($node1));
		$this->assertEquals($baseBitStr.'100', $localNode->distanceBitStr($node2));
		#$this->assertEquals($baseBitStr.'101', $localNode->distanceBitStr($node3));
		$this->assertEquals($baseBitStr.'010', $localNode->distanceBitStr($node4));
		$this->assertEquals($baseBitStr.'011', $localNode->distanceBitStr($node5));
		$this->assertEquals($baseBitStr.'001', $localNode->distanceBitStr($node7));
		
		
		$table->nodeEnclose($node0);
		$table->nodeEnclose($node1);
		$table->nodeEnclose($node2);
		$table->nodeEnclose($node4);
		$table->nodeEnclose($node5);
		$table->nodeEnclose($node7);
		$table->nodeEnclose($node20);
		
		$table->save();
		
		$this->clean();
	}
	
	/**
	 * @group medium
	 */
	public function testNodeEnclose3b(){
		#fwrite(STDOUT, 'testNodeEnclose3b'.PHP_EOL);
		
		Table::$NODE_TTL = 4;
		
		$localNode = new Node();
		$localNode->setIdHexStr('11000001-2002-4004-8008-100000000006');
		
		$table = new Table('tests/testfile_table.yml');
		$table->setDatadirBasePath('tests');
		$table->setLocalNode($localNode);
		#$table->load();
		
		$node0 = new Node();
		$node0->setIdHexStr('11000001-2002-4004-8008-100000000000');
		
		$node1 = new Node();
		$node1->setIdHexStr('11000001-2002-4004-8008-100000000001');
		
		$node2 = new Node();
		$node2->setIdHexStr('11000001-2002-4004-8008-100000000002');
		
		$node3 = new Node();
		$node3->setIdHexStr('11000001-2002-4004-8008-100000000003');
		
		$node4 = new Node();
		$node4->setIdHexStr('11000001-2002-4004-8008-100000000004');
		
		$node5 = new Node();
		$node5->setIdHexStr('11000001-2002-4004-8008-100000000005');
		
		$node7 = new Node();
		$node7->setIdHexStr('11000001-2002-4004-8008-100000000007');
		
		$node20 = new Node();
		$node20->setIdHexStr('11000001-2002-4004-8008-100000000020');
		
		
		$table->nodeEnclose($node0);
		$table->nodeEnclose($node1);
		$table->nodeEnclose($node2);
		$table->nodeEnclose($node4);
		$table->nodeEnclose($node5);
		$table->nodeEnclose($node7);
		$table->nodeEnclose($node20);
		
		sleep(5);
		
		$node20->setTimeLastSeen(time());
		
		$table->nodesClean();
		$table->save();
		
		$finder = new Finder();
		$files = $finder->in('tests')->name('node_*.yml');
		$this->assertEquals(1, count($files));
		
		$this->clean();
	}
	
	public function testNodeEnclose3c(){
		Table::$NODE_CONNECTIONS_OUTBOUND_ATTEMPTS_MAX = 4;
		
		$localNode = new Node();
		$localNode->setIdHexStr('11000001-2002-4004-8008-100000000006');
		
		$table = new Table('tests/testfile_table.yml');
		$table->setDatadirBasePath('tests');
		$table->setLocalNode($localNode);
		#$table->load();
		
		$node = new Node();
		$node->setIdHexStr('11000001-2002-4004-8008-100000000000');
		$node->setConnectionsOutboundAttempts(0);
		$node->setConnectionsOutboundSucceed(0);
		$node->setConnectionsInboundSucceed(0);
		$table->nodeEnclose($node);
		
		$node = new Node();
		$node->setIdHexStr('11000001-2002-4004-8008-100000000001');
		$node->setConnectionsOutboundAttempts(1);
		$node->setConnectionsOutboundSucceed(0);
		$node->setConnectionsInboundSucceed(0);
		$table->nodeEnclose($node);
		
		$node = new Node();
		$node->setIdHexStr('11000001-2002-4004-8008-100000000002');
		$node->setConnectionsOutboundAttempts(2);
		$node->setConnectionsOutboundSucceed(1);
		$node->setConnectionsInboundSucceed(0);
		$table->nodeEnclose($node);
		
		$node = new Node();
		$node->setIdHexStr('11000001-2002-4004-8008-100000000003');
		$node->setConnectionsOutboundAttempts(3);
		$node->setConnectionsOutboundSucceed(1);
		$node->setConnectionsInboundSucceed(1);
		$table->nodeEnclose($node);
		
		$node = new Node();
		$node->setIdHexStr('11000001-2002-4004-8008-100000000004');
		$node->setConnectionsOutboundAttempts(7);
		$node->setConnectionsOutboundSucceed(0);
		$node->setConnectionsInboundSucceed(0);
		$table->nodeEnclose($node);
		
		$node = new Node();
		$node->setIdHexStr('11000001-2002-4004-8008-100000000005');
		$node->setConnectionsOutboundAttempts(7);
		$node->setConnectionsOutboundSucceed(2);
		$node->setConnectionsInboundSucceed(0);
		$table->nodeEnclose($node);
		
		$node = new Node();
		$node->setIdHexStr('11000001-2002-4004-8008-100000000006');
		$node->setConnectionsOutboundAttempts(1);
		$node->setConnectionsOutboundSucceed(0);
		$node->setConnectionsInboundSucceed(7);
		$table->nodeEnclose($node);
		
		$node = new Node();
		$node->setIdHexStr('11000001-2002-4004-8008-100000000007');
		$node->setConnectionsOutboundAttempts(7);
		$node->setConnectionsOutboundSucceed(0);
		$node->setConnectionsInboundSucceed(7);
		$table->nodeEnclose($node);
		
		$node = new Node();
		$node->setIdHexStr('11000001-2002-4004-8008-100000000008');
		$node->setConnectionsOutboundAttempts(7);
		$node->setConnectionsOutboundSucceed(7);
		$node->setConnectionsInboundSucceed(7);
		$table->nodeEnclose($node);
		
		$node = new Node();
		$node->setIdHexStr('11000001-2002-4004-8008-100000000009');
		$node->setConnectionsOutboundAttempts(0);
		$node->setConnectionsOutboundSucceed(0);
		$node->setConnectionsInboundSucceed(1);
		$table->nodeEnclose($node);
		
		$node = new Node();
		$node->setIdHexStr('11000001-2002-4004-8008-100000000010');
		$node->setConnectionsOutboundAttempts(0);
		$node->setConnectionsOutboundSucceed(0);
		$node->setConnectionsInboundSucceed(7);
		$table->nodeEnclose($node);
		
		
		
		$table->nodesClean();
		$table->save();
		
		$finder = new Finder();
		$files = $finder->in('tests')->depth(0)->name('node_*.yml');
		$this->assertEquals(9, count($files));
		
		#$this->clean();
	}
	
	public function testNodeEnclose4(){
		$NODES = 100;
		Table::$NODES_MAX = 50;
		
		$localNode = new Node();
		$localNode->setIdHexStr('12000001-2002-4004-8008-100000000001');
		$table = new Table('tests/testfile_table.yml');
		$table->setDatadirBasePath('tests');
		$table->setLocalNode($localNode);
		$table->load();
		
		$nodeNoBegin = 100000000002;
		$nodeNoEnd = $nodeNoBegin + $NODES;
		for($nodeNo = $nodeNoBegin; $nodeNo < $nodeNoEnd; $nodeNo++){
			#fwrite(STDOUT, __METHOD__.' node setup: '.$nodeNo.''.PHP_EOL);
			
			$node = new Node();
			$node->setIdHexStr('12000001-2002-4004-8008-'.$nodeNo);
			
			$table->nodeEnclose($node);
		}
		
		$table->nodesClean();
		$table->save();
		
		$nodeNum = $table->getNodesNum();
		#fwrite(STDOUT, 'nodes: '.$nodeNum.''.PHP_EOL);
		$this->assertEquals(50, $nodeNum);
		
		$finder = new Finder();
		$files = $finder->in('tests')->name('node_*.yml');
		$this->assertEquals(50, count($files));
		
		$this->clean();
	}
	
	private function clean(){
		@unlink('tests/testfile_table.yml');
		@unlink('tests/bucket_root.yml');
		
		$finder = new Finder();
		$files = $finder->in('tests')->name('bucket_*.yml');
		$filesystem = new Filesystem();
		foreach($files as $fileId => $file){
			$filesystem->remove($file->getRealPath());
		}
		
		$finder = new Finder();
		$files = $finder->in('tests')->name('node_*.yml');
		$filesystem = new Filesystem();
		foreach($files as $fileId => $file){
			$filesystem->remove($file->getRealPath());
		}
	}
	
}
