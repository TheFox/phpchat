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
	
	public function nodeAddConnect($uri){
		#print __CLASS__.'->'.__FUNCTION__.': '.$uri."\n";
		
		$oldId = 0;
		foreach($this->data['nodes'] as $nodeId => $node){
			if($node['uri'] == $uri){
				$oldId = $nodeId;
				break;
			}
		}
		if($oldId){
			$this->data['nodes'][$oldId]['insertAttempts']++;
		}
		else{
			$this->data['nodesId']++;
			$this->data['nodes'][$this->data['nodesId']] = array(
				'type' => 'connect',
				'uri' => $uri,
				'connectAttempts' => 0,
				'insertAttempts' => 0,
			);
		}
		$this->setDataChanged(true);
	}
	
	public function nodeAddFind($id){
		#print __CLASS__.'->'.__FUNCTION__.': '.$uri."\n";
		
		$oldId = false;
		foreach($this->data['nodes'] as $nodeId => $node){
			if($node['id'] == $id){
				$oldId = $nodeId;
				break;
			}
		}
		if($oldId){
			$this->data['nodes'][$oldId]['insertAttempts']++;
		}
		else{
			$this->data['nodesId']++;
			$this->data['nodes'][$this->data['nodesId']] = array(
				'type' => 'find',
				'id' => $id,
				'findAttempts' => 0,
				'insertAttempts' => 0,
			);
		}
		$this->setDataChanged(true);
	}
	
	public function nodeIncConnectAttempt($id){
		if(isset($this->data['nodes'][$id])){
			$this->data['nodes'][$id]['connectAttempts']++;
			$this->setDataChanged(true);
		}
	}
	
	public function nodeIncFindAttempt($id){
		if(isset($this->data['nodes'][$id])){
			$this->data['nodes'][$id]['findAttempts']++;
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
