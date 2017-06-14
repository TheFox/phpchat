<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;
use TheFox\PhpChat\NodesNewDb;

class NodesNewDbTest extends PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $db1 = new NodesNewDb();
        $db1->nodeAddConnect('tcp://192.168.241.24:25000');

        $db2 = unserialize(serialize($db1));

        $this->assertEquals($db1, $db2);
    }

    public function testNodeAddConnect()
    {
        $db = new NodesNewDb();

        $db->nodeAddConnect('tcp://192.168.241.24:25000', false);
        $this->assertTrue(is_array($db->data['nodes']));
        $this->assertEquals(1, count($db->data['nodes']));
        $this->assertEquals('connect', $db->data['nodes'][1]['type']);
        $this->assertEquals(null, $db->data['nodes'][1]['id']);
        $this->assertEquals('tcp://192.168.241.24:25000', (string)$db->data['nodes'][1]['uri']);
        $this->assertFalse($db->data['nodes'][1]['bridgeServer']);
        $this->assertEquals(0, $db->data['nodes'][1]['connectAttempts']);
        $this->assertEquals(0, $db->data['nodes'][1]['findAttempts']);
        $this->assertEquals(0, $db->data['nodes'][1]['insertAttempts']);

        $db->nodeAddConnect('tcp://192.168.241.24:25000', false);
        $this->assertEquals(1, count($db->data['nodes']));
        $this->assertEquals('connect', $db->data['nodes'][1]['type']);
        $this->assertEquals(null, $db->data['nodes'][1]['id']);
        $this->assertEquals('tcp://192.168.241.24:25000', (string)$db->data['nodes'][1]['uri']);
        $this->assertFalse($db->data['nodes'][1]['bridgeServer']);
        $this->assertEquals(0, $db->data['nodes'][1]['connectAttempts']);
        $this->assertEquals(0, $db->data['nodes'][1]['findAttempts']);
        $this->assertEquals(1, $db->data['nodes'][1]['insertAttempts']);

        $db->nodeAddConnect('tcp://192.168.241.24:25000', false);
        $this->assertEquals(1, count($db->data['nodes']));
        $this->assertEquals('connect', $db->data['nodes'][1]['type']);
        $this->assertEquals(null, $db->data['nodes'][1]['id']);
        $this->assertEquals('tcp://192.168.241.24:25000', (string)$db->data['nodes'][1]['uri']);
        $this->assertFalse($db->data['nodes'][1]['bridgeServer']);
        $this->assertEquals(0, $db->data['nodes'][1]['connectAttempts']);
        $this->assertEquals(0, $db->data['nodes'][1]['findAttempts']);
        $this->assertEquals(2, $db->data['nodes'][1]['insertAttempts']);

        $db->nodeAddConnect('tcp://192.168.241.25:25000', true);
        $this->assertEquals(2, count($db->data['nodes']));
        $this->assertEquals('connect', $db->data['nodes'][2]['type']);
        $this->assertEquals(null, $db->data['nodes'][2]['id']);
        $this->assertEquals('tcp://192.168.241.25:25000', (string)$db->data['nodes'][2]['uri']);
        $this->assertTrue($db->data['nodes'][2]['bridgeServer']);
        $this->assertEquals(0, $db->data['nodes'][2]['connectAttempts']);
        $this->assertEquals(0, $db->data['nodes'][2]['findAttempts']);
        $this->assertEquals(0, $db->data['nodes'][2]['insertAttempts']);
    }

    public function testNodeAddFind()
    {
        $db = new NodesNewDb();

        $db->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1738', false);
        $this->assertTrue(is_array($db->data['nodes']));
        $this->assertEquals(1, count($db->data['nodes']));
        $this->assertEquals('find', $db->data['nodes'][1]['type']);
        $this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $db->data['nodes'][1]['id']);
        $this->assertEquals(null, (string)$db->data['nodes'][1]['uri']);
        $this->assertFalse($db->data['nodes'][1]['bridgeServer']);
        $this->assertEquals(0, $db->data['nodes'][1]['connectAttempts']);
        $this->assertEquals(0, $db->data['nodes'][1]['findAttempts']);
        $this->assertEquals(0, $db->data['nodes'][1]['insertAttempts']);

        $db->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1738', false);
        $this->assertEquals(1, count($db->data['nodes']));
        $this->assertEquals('find', $db->data['nodes'][1]['type']);
        $this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1738', $db->data['nodes'][1]['id']);
        $this->assertEquals(null, (string)$db->data['nodes'][1]['uri']);
        $this->assertFalse($db->data['nodes'][1]['bridgeServer']);
        $this->assertEquals(0, $db->data['nodes'][1]['connectAttempts']);
        $this->assertEquals(0, $db->data['nodes'][1]['findAttempts']);
        $this->assertEquals(1, $db->data['nodes'][1]['insertAttempts']);

        $db->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1739', false);
        $this->assertEquals(2, count($db->data['nodes']));
        $this->assertEquals('find', $db->data['nodes'][2]['type']);
        $this->assertEquals('cafed00d-2131-4159-8e11-0b4dbadb1739', $db->data['nodes'][2]['id']);
        $this->assertEquals(null, (string)$db->data['nodes'][2]['uri']);
        $this->assertFalse($db->data['nodes'][2]['bridgeServer']);
        $this->assertEquals(0, $db->data['nodes'][2]['connectAttempts']);
        $this->assertEquals(0, $db->data['nodes'][2]['findAttempts']);
        $this->assertEquals(0, $db->data['nodes'][2]['insertAttempts']);
    }

    public function testNodeIncConnectAttempt()
    {
        $db = new NodesNewDb();

        $db->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1738');
        $db->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1739');
        $this->assertEquals(0, $db->data['nodes'][1]['connectAttempts']);

        $db->nodeIncConnectAttempt(1);
        $this->assertEquals(1, $db->data['nodes'][1]['connectAttempts']);

        $db->nodeIncConnectAttempt(1);
        $this->assertEquals(2, $db->data['nodes'][1]['connectAttempts']);
    }

    public function testNodeIncFindAttempt()
    {
        $db = new NodesNewDb();

        $db->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1738');
        $db->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1739');
        $this->assertEquals(0, $db->data['nodes'][1]['findAttempts']);

        $db->nodeIncFindAttempt(1);
        $this->assertEquals(1, $db->data['nodes'][1]['findAttempts']);

        $db->nodeIncFindAttempt(1);
        $this->assertEquals(2, $db->data['nodes'][1]['findAttempts']);
    }

    public function testNodeRemove()
    {
        $db = new NodesNewDb();

        $db->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1738');
        $this->assertEquals(1, count($db->data['nodes']));
        $this->assertTrue(isset($db->data['nodes'][1]));

        $db->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1739');
        $this->assertEquals(2, count($db->data['nodes']));
        $this->assertTrue(isset($db->data['nodes'][2]));

        $db->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1740');
        $this->assertEquals(3, count($db->data['nodes']));
        $this->assertTrue(isset($db->data['nodes'][3]));

        $db->nodeRemove(2);
        $this->assertEquals(2, count($db->data['nodes']));
        $this->assertTrue(isset($db->data['nodes'][1]));
        $this->assertFalse(isset($db->data['nodes'][2]));
        $this->assertTrue(isset($db->data['nodes'][3]));
    }

    public function testGetNodes()
    {
        $db = new NodesNewDb();
        $this->assertEquals([], $db->getNodes());
        $this->assertEquals(0, count($db->getNodes()));

        $db->nodeAddFind('cafed00d-2131-4159-8e11-0b4dbadb1738');
        $this->assertEquals(1, count($db->getNodes()));
    }
}
