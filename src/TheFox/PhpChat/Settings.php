<?php

namespace TheFox\PhpChat;

use TheFox\Yaml\YamlStorage;

class Settings extends YamlStorage{
	
	const USER_NICKNAME_LEN_MAX = 256;
	const SSL_KEY_LEN_MIN = 2048;
	
	public function __construct($filePath = null){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		$this->setFilePath($filePath);
		
		
		$this->data['version'] = '2.0.0';
		$this->data['release'] = 2;
		$this->data['datadir'] = 'data';
		$this->data['firstRun'] = true;
		#$this->data['isBootstrap'] = true;
		
		$this->data['node'] = array();
		$this->data['node']['timeCreated'] = 0;
		$this->data['node']['addr'] = '0.0.0.0';
		$this->data['node']['addr_pub'] = null;
		$this->data['node']['port'] = 25000;
		$this->data['node']['uuid'] = '';
		
		$this->data['node']['ssl_key_prv_pass'] = '';
		$this->data['node']['ssl_key_prv_path'] = 'id_rsa.prv';
		$this->data['node']['ssl_key_pub_path'] = 'id_rsa.pub';
		
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
	
}
