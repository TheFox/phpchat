<?php

namespace TheFox\Dht\Kademlia;

use TheFox\Yaml\YamlStorage;

class Bucket extends YamlStorage{
	
	const SIZE_MAX = 20;
	#const NODE_TTL = 900; # 15 minutes
	
	private $id;
	private $nodeLocal;
	private $nodesId;
	private $nodes;
	
	public function __construct($id = 0, $mask = ''){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->setId($id);
		$this->data['timeCreated'] = time();
		$this->data['sizeMax'] = Bucket::SIZE_MAX;
		$this->setMask($mask);
		$this->data['isFull'] = false;
		$this->nodesId = 0;
		$this->nodes = new StackableArray();
	}
	
	public function save(){
		#print "".__CLASS__."->".__FUNCTION__.": begin\n";
		
		$this->data['nodes'] = new StackableArray();
		
		foreach($this->nodes as $nodeId => $node){
			$nodeAr = new StackableArray();
			$nodeAr['type']				= $node->getType();
			$nodeAr['id']				= $node->getIdHexStr();
			$nodeAr['ip']				= $node->getIp();
			$nodeAr['port']				= $node->getPort();
			$nodeAr['sslKeyPub']		= base64_encode($node->getSslKeyPub());
			$nodeAr['sslKeyPubFingerprint']		= $node->getSslKeyPubFingerprint();
			$nodeAr['timeCreated']		= $node->getTimeCreated();
			$nodeAr['timeLastSeen']		= $node->getTimeLastSeen();
			
			$this->data['nodes'][] = $nodeAr;
		}
		
		$rv = parent::save();
		unset($this->data['nodes']);
		
		return $rv;
	}
	
	public function load(){
		#print "".__CLASS__."->".__FUNCTION__.": begin\n";
		
		if(parent::load()){
			
			if(isset($this->data['nodes']) && $this->data['nodes']){
				foreach($this->data['nodes'] as $nodeId => $nodeAr){
					$this->nodesId = $nodeId;
					
					$nodeObj = new Node();
					$nodeObj->setType($nodeAr['type']);
					$nodeObj->setIdHexStr($nodeAr['id']);
					$nodeObj->setIp($nodeAr['ip']);
					$nodeObj->setPort($nodeAr['port']);
					if(isset($nodeAr['sslKeyPub']) && $nodeAr['sslKeyPub']){
						$nodeObj->setSslKeyPub(base64_decode($nodeAr['sslKeyPub']));
					}
					elseif(isset($nodeAr['sslKeyPubFingerprint'])){
						$nodeObj->setSslKeyPubFingerprint($nodeAr['sslKeyPubFingerprint']);
					}
					$nodeObj->setTimeCreated($nodeAr['timeCreated']);
					$nodeObj->setTimeLastSeen($nodeAr['timeLastSeen']);
					$nodeObj->setBucket($this);
					
					$this->nodes[$this->nodesId] = $nodeObj;
				}
			}
			
			unset($this->data['nodes']);
			
			return true;
		}
		
		return false;
	}
	
	public function setId($id){
		#print "".__CLASS__."->".__FUNCTION__.": '".$id."'\n";
		$this->id = (int)$id;
	}
	
	public function getId(){
		#print "".__CLASS__."->".__FUNCTION__."\n";
		return (int)$this->id;
	}
	
	public function setMask($mask){
		#print "".__CLASS__."->".__FUNCTION__.": ".$mask."\n";
		$this->data['mask'] = $mask;
	}
	
	public function getMask(){
		#print "".__CLASS__."->".__FUNCTION__."\n";
		return $this->data['mask'];
	}
	
	public function getNodes(){
		return $this->nodes;
	}
	
	public function getNodesNum(){
		return count($this->getNodes());
	}
	
	public function setNodeLocal(Node $node){
		$this->nodeLocal = $node;
	}
	
	public function getNodeLocal(){
		return $this->nodeLocal;
	}
	
	public function nodeFind(Node $node){
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
		if(is_null($this->nodeFind($node))){
			$node->setBucket($this);
			$this->nodesId = $this->nodesId + 1;
			$this->nodes[$this->nodesId] = $node;
			$this->setDataChanged();
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
		
		uasort($nodes, function($node_a, $node_b){
			$dist_a = $this->getNodeLocal()->distance($node_a);
			$dist_b = $this->getNodeLocal()->distance($node_b);
			
			if($dist_a == $dist_b){
				return 0;
			}
			return $dist_a < $dist_b ? -1 : 1;
		});
		$this->setDataChanged();
		
		$this->nodes = new StackableArray($nodes);
	}
	
	public function isFull(){
		if($this->getNodesNum() >= Bucket::SIZE_MAX){
			// If full, stay full.
			$this->data['isFull'] = true;
		}
		
		return $this->data['isFull'];
	}
	
}