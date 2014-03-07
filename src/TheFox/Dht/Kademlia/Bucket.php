<?php

namespace TheFox\Dht\Kademlia;

use TheFox\Yaml\YamlStorage;

class Bucket extends YamlStorage{
	
	const SIZE_MAX = 20;
	
	private $nodesId = 0;
	private $nodes = array();
	private $localNode = null;
	
	public function __construct($filePath = null){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		parent::__construct($filePath);
		
		$this->data['id'] = 0;
		$this->data['mask'] = '';
		$this->data['isFull'] = false;
		$this->data['sizeMax'] = static::SIZE_MAX;
		$this->data['timeCreated'] = time();
	}
	
	public function save(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->data['nodes'] = array();
		
		foreach($this->nodes as $nodeId => $node){
			#print __CLASS__.'->'.__FUNCTION__.': '.$nodeId.', '.(int)$node->getDataChanged()."\n";
			
			$this->data['nodes'][$nodeId] = array(
				'path' => $node->getFilePath(),
			);
			$node->save();
		}
		
		$rv = parent::save();
		unset($this->data['nodes']);
		
		return $rv;
	}
	
	public function load(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(parent::load()){
			
			if(array_key_exists('nodes', $this->data) && $this->data['nodes']){
				foreach($this->data['nodes'] as $nodeId => $nodeAr){
					$this->nodesId = $nodeId;
					
					$node = new Node($nodeAr['path']);
					$node->setDatadirBasePath($this->getDatadirBasePath());
					$node->load();
					
					$this->nodes[$this->nodesId] = $node;
				}
			}
			
			unset($this->data['nodes']);
			
			return true;
		}
		
		return false;
	}
	
	public function setId($id){
		$this->data['id'] = (int)$id;
	}
	
	public function getId(){
		return (int)$this->data['id'];
	}
	
	public function setMask($mask){
		$this->data['mask'] = $mask;
	}
	
	public function getMask(){
		return $this->data['mask'];
	}
	
	public function setIsFull($isFull){
		$this->data['isFull'] = $isFull;
	}
	
	public function getIsFull(){
		return $this->data['isFull'];
	}
	
	public function isFull(){
		if($this->getNodesNum() >= static::SIZE_MAX){
			// If full, stay full.
			$this->setIsFull(true);
		}
		
		return $this->getIsFull();
	}
	
	
	
	public function getNodes(){
		return $this->nodes;
	}
	
	public function getNodesNum(){
		return count($this->getNodes());
	}
	
	public function setLocalNode(Node $localNode){
		$this->localNode = $localNode;
	}
	
	public function getLocalNode(){
		return $this->localNode;
	}
	
	
	
	public function nodeFind(Node $node){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		return $this->nodeFindByIdHexStr($node->getIdHexStr());
	}
	
	public function nodeFindByIdHexStr($id){
		foreach($this->nodes as $nodeId => $node){
			if($node->getIdHexStr() == $id){
				return $node;
			}
		}
		return null;
	}
	
	public function nodeAdd(Node $node, $sortNodes = true){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		$onode = $this->nodeFind($node);
		if(!$onode){
			#print __CLASS__.'->'.__FUNCTION__.': old node'."\n";
			$node->setBucket($this);
			
			$this->nodesId++;
			$this->nodes[$this->nodesId] = $node;
			
			$this->setDataChanged(true);
		}
		if($sortNodes){
			$this->nodesSort();
		}
	}
	
	public function nodeRemoveByIndex($index){
		unset($this->nodes[$index]);
	}
	
	public function nodesSort(){
		$nodes = (array)$this->nodes;
		
		uasort($this->nodes, function($node_a, $node_b){
			$dist_a = $this->getLocalNode()->distance($node_a);
			$dist_b = $this->getLocalNode()->distance($node_b);
			
			if($dist_a == $dist_b){
				return 0;
			}
			return $dist_a < $dist_b ? -1 : 1;
		});
		
		$this->setDataChanged(true);
	}
	
	
	
}