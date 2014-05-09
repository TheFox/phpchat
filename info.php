<?php

require_once __DIR__.'/bootstrap.php';

use TheFox\Dht\Kademlia\Node;

$localNode = new Node();
$localNode->setIdHexStr($settings->data['node']['id']);
$localNode->setPort($settings->data['node']['port']);
$localNode->setSslKeyPub(file_get_contents($settings->data['node']['sslKeyPubPath']));

print "--------\n";
print "Informations about your node:\n";
print "   ID: ".$localNode->getIdHexStr()."\n";
print "   Public key fingerprint: ".$localNode->getSslKeyPubFingerprint()."\n";
print "   Last public IP: ".$settings->data['node']['ipPub']."\n";
print "   Listen IP:Port: ".$settings->data['node']['ip'].':'.$settings->data['node']['port']."\n";
print "   Nickname: ".$settings->data['user']['nickname']."\n";
print "--------\n";
