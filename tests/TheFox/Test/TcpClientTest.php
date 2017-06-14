<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;

use TheFox\Dht\Kademlia\Node;
use TheFox\PhpChat\TcpClient;

class TcpClientTest extends PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $node = new Node();
        $node->setIdHexStr('cafed00d-2131-4159-8e11-0b4dbadb1738');

        $client = new TcpClient();
        $client->setId(21);
        $client->setUri('tcp://127.0.0.1:25000');
        $client->setNode($node);

        $client = unserialize(serialize($client));

        $this->assertEquals(21, $client->getId());
        $this->assertEquals('tcp://127.0.0.1:25000', (string)$client->getUri());
        $this->assertEquals($node, $client->getNode());
    }
}
