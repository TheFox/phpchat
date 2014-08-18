<?php

namespace TheFox\PhpChat;

use TheFox\Storage\YamlStorage;
use TheFox\Dht\Kademlia\Node;

class NodesNewDb extends YamlStorage{
	
	public function __construct($filePath = null){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		parent::__construct($filePath);
		
		$this->data['timeCreated'] = time();
		$this->data['nodesId'] = 0;
		$this->data['nodes'] = array();
	}
	
	public function __sleep(){
		return array('data');
	}
	
	public function nodeAdd($uri){
		#print __CLASS__.'->'.__FUNCTION__.': '.$uri."\n";
		
		$found = false;
		foreach($this->data['nodes'] as $nodeId => $node){
			if($node['uri'] == $uri){
				$found = true;
				break;
			}
		}
		if(!$found){
			$this->data['nodesId']++;
			$this->data['nodes'][$this->data['nodesId']] = array(
				'uri' => $uri,
				'connectAttempt' => 0,
			);
			$this->setDataChanged(true);
		}
	}
	
	public function nodeIncConnectAttempt($id){
		if(isset($this->data['nodes'][$id])){
			$this->data['nodes'][$id]['connectAttempt']++;
			$this->setDataChanged(true);
		}
	}
	
	public function nodeRemove($id){
		if(isset($this->data['nodes'][$id])){
			unset($this->data['nodes'][$id]);
		}
		$this->setDataChanged(true);
	}
	
	public function getNodes(){
		return $this->data['nodes'];
	}
	
}
