<?php

namespace TheFox\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use TheFox\PhpChat\PhpChat;
use TheFox\Dht\Kademlia\Node;

class InfoCommand extends BasicCommand{
	
	protected function configure(){
		$this->setName('info');
		$this->setDescription('Show infos about this node.');
		$this->addOption('name', null, InputOption::VALUE_NONE, 'Print the name this application.');
		$this->addOption('name_lc', null, InputOption::VALUE_NONE, 'Print the lower-case name this application.');
		$this->addOption('version_number', null, InputOption::VALUE_NONE, 'Print the version this application.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output){
		#$this->executePre($input, $output);
		
		if($input->hasOption('name') && $input->getOption('name')){
			print PhpChat::NAME;
		}
		elseif($input->hasOption('name_lc') && $input->getOption('name_lc')){
			print strtolower(PhpChat::NAME);
		}
		elseif($input->hasOption('version_number') && $input->getOption('version_number')){
			print PhpChat::VERSION;
		}
		else{
			$settings = $this->getSettings();
			
			$localNode = new Node();
			$localNode->setIdHexStr($settings->data['node']['id']);
			$localNode->setUri('tcp://'.$settings->data['node']['ip'].':'.$settings->data['node']['port']);
			$localNode->setSslKeyPub(file_get_contents($settings->data['node']['sslKeyPubPath']));
			
			print '--------'."\n";
			print 'Informations about your node:'."\n";
			print '   Version: '.PhpChat::NAME.'/'.PhpChat::VERSION.' ('.PhpChat::RELEASE.')'."\n";
			print '   ID: '.$localNode->getIdHexStr()."\n";
			print '   Public key fingerprint: '.$localNode->getSslKeyPubFingerprint()."\n";
			print '   Last public IP: '.$settings->data['node']['ipPub']."\n";
			print '   Listen IP:Port: '.$settings->data['node']['ip'].':'.$settings->data['node']['port']."\n";
			print '   Nickname: '.$settings->data['user']['nickname']."\n";
			print '   SSL version: '.OPENSSL_VERSION_TEXT."\n";
			print '--------'."\n";
			print '   Pub Key Base64:'."\n".base64_encode($localNode->getSslKeyPub())."\n";
			print '--------'."\n";
		}
		
		
		
		#$this->executePost();
	}
	
}
