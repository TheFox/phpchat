<?php

namespace TheFox\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use TheFox\Dht\Kademlia\Node;

class InfoCommand extends BasicCommand{
	
	public function getPidfilePath(){
		return 'pid/info.pid';
	}
	
	protected function configure(){
		$this->setName('info');
		$this->setDescription('Show infos about this node.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output){
		$this->executePre($input, $output);
		
		$settings = $this->getSettings();
		
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
		
		$this->executePost();
	}
	
}
