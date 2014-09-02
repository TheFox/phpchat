<?php

namespace TheFox\PhpChat;

use TheFox\Storage\YamlStorage;

class Settings extends YamlStorage{
	
	const USER_NICKNAME_LEN_MAX = 256;
	
	public function __construct($filePath = null){
		parent::__construct($filePath);
		
		$this->data['version'] = PhpChat::VERSION;
		$this->data['release'] = PhpChat::RELEASE;
		$this->data['datadir'] = 'data';
		$this->data['firstRun'] = true;
		$this->data['timeCreated'] = time();
		
		$this->data['node'] = array();
		$this->data['node']['uriLocal'] = 'tcp://0.0.0.0:25000';
		$this->data['node']['uriPub'] = '';
		$this->data['node']['id'] = '';
		
		$this->data['node']['sslKeyPrvPass'] = '';
		$this->data['node']['sslKeyPrvPath'] = 'id_rsa.prv';
		$this->data['node']['sslKeyPubPath'] = 'id_rsa.pub';
		
		$this->data['node']['traffic'] = array();
		$this->data['node']['traffic']['in'] = '0';
		$this->data['node']['traffic']['out'] = '0';
		
		$this->data['user'] = array();
		$this->data['user']['nickname'] = '';
		
		$this->data['console'] = array();
		$this->data['console']['history'] = array();
		$this->data['console']['history']['enabled'] = true;
		$this->data['console']['history']['entriesMax'] = 1000;
		$this->data['console']['history']['saveToFile'] = true;
		
		$this->data['message'] = array();
		$this->data['message']['directDelivery'] = true;
		
		$this->load();
		
		if($this->isLoaded()){
			$this->data['version'] = PhpChat::VERSION;
			$this->data['release'] = PhpChat::RELEASE;
		}
		else{
			$this->data['user']['nickname'] = 'user_'.substr(md5(time()), 0, 4);
			
			$this->setDataChanged(true);
			$this->save();
		}
	}
	
	public function __sleep(){
		return array('data');
	}
	
}
