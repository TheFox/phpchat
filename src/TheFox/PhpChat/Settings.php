<?php

namespace TheFox\PhpChat;

use TheFox\Yaml\YamlStorage;

class Settings extends YamlStorage{
	
	const USER_NICKNAME_LEN_MAX = 256;
	
	public function __construct($filePath = null){
		parent::__construct($filePath);
		
		$this->data['version'] = '0.3.0-dev';
		$this->data['release'] = 3;
		$this->data['datadir'] = 'data';
		$this->data['firstRun'] = true;
		#$this->data['isBootstrap'] = true;
		
		$this->data['node'] = array();
		$this->data['node']['timeCreated'] = 0;
		$this->data['node']['ip'] = '0.0.0.0';
		$this->data['node']['ipPub'] = null;
		$this->data['node']['port'] = 25000;
		$this->data['node']['id'] = '';
		
		$this->data['node']['sslKeyPrvPass'] = '';
		$this->data['node']['sslKeyPrvPath'] = 'id_rsa.prv';
		$this->data['node']['sslKeyPubPath'] = 'id_rsa.pub';
		
		$this->data['nodes'] = array();
		$this->data['nodes']['timeLastCheck'] = 0;
		
		$this->data['user'] = array();
		$this->data['user']['nickname'] = '';
		
		
		$this->load();
		
		if(!$this->isLoaded()){
			$this->data['node']['timeCreated'] = time();
			$this->data['user']['nickname'] = 'user_'.substr(md5(time()), 0, 4);
			
			$this->setDataChanged(true);
			$this->save();
		}
	}
	
	public function __sleep(){
		return array('data');
	}
	
}
