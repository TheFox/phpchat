<?php

namespace TheFox\Dht\Kademlia;

use RuntimeException;

use TheFox\Storage\YamlStorage;

class Bucket extends YamlStorage{
	
	static $SIZE_MAX = 20;
	
	private $nodesId = 0;
	private $nodes = array();
	private $localNode = null;
	private $childBucketUpper = null;
	private $childBucketLower = null;
	
	public function __construct($filePath = null){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		parent::__construct($filePath);
		
		#$this->data['id'] = 0;
		#$this->data['prefix'] = '';
		#$this->data['prefixName'] = '';
		$this->data['distance'] = 0;
		$this->data['distanceName'] = '';
		$this->data['mask'] = '';
		#$this->data['mask'] = array_fill(0, Node::ID_LEN, 0);
		$this->data['maskName'] = '';
		$this->data['isFull'] = false;
		$this->data['isUpper'] = false;
		$this->data['isLower'] = false;
		$this->data['sizeMax'] = static::$SIZE_MAX;
		$this->data['timeCreated'] = time();
	}
	
	public function __sleep(){
		return array('nodes');
	}
	
	public function save(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		#$this->data['prefixName'] = intToBin($this->getPrefix());
		$this->data['distanceName'] = intToBin($this->getDistance());
		$this->data['maskName'] = intToBin($this->getMask());
		
		$this->data['nodes'] = array();
		foreach($this->nodes as $nodeId => $node){
			#print __CLASS__.'->'.__FUNCTION__.': '.$nodeId.', '.(int)$node->getDataChanged()."\n";
			
			$this->data['nodes'][$nodeId] = array(
				'path' => $node->getFilePath(),
			);
			$node->save();
		}
		
		$this->data['childBucketUpper'] = null;
		if($this->childBucketUpper){
			$this->data['childBucketUpper'] = $this->childBucketUpper->getFilePath();
			$this->childBucketUpper->save();
		}
		
		$this->data['childBucketLower'] = null;
		if($this->childBucketLower){
			$this->data['childBucketLower'] = $this->childBucketLower->getFilePath();
			$this->childBucketLower->save();
		}
		
		$rv = parent::save();
		unset($this->data['nodes']);
		unset($this->data['childBucketUpper']);
		unset($this->data['childBucketLower']);
		
		return $rv;
	}
	
	public function load(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(parent::load()){
			
			if($this->data){
				if(array_key_exists('nodes', $this->data) && $this->data['nodes']){
					foreach($this->data['nodes'] as $nodeId => $nodeAr){
						if(file_exists($nodeAr['path'])){
							$this->nodesId = $nodeId;
							
							$node = new Node($nodeAr['path']);
							$node->setDatadirBasePath($this->getDatadirBasePath());
							$node->setBucket($this);
							if($node->load()){
								$this->nodes[$this->nodesId] = $node;
							}
						}
					}
				}
				if(array_key_exists('childBucketUpper', $this->data) && $this->data['childBucketUpper']){
					$this->childBucketUpper = new Bucket($this->data['childBucketUpper']);
					$this->childBucketUpper->setDatadirBasePath($this->getDatadirBasePath());
					$this->childBucketUpper->setLocalNode($this->getLocalNode());
					$this->childBucketUpper->load();
				}
				if(array_key_exists('childBucketLower', $this->data) && $this->data['childBucketLower']){
					$this->childBucketLower = new Bucket($this->data['childBucketLower']);
					$this->childBucketLower->setDatadirBasePath($this->getDatadirBasePath());
					$this->childBucketLower->setLocalNode($this->getLocalNode());
					$this->childBucketLower->load();
				}
			}
			
			unset($this->data['nodes']);
			unset($this->data['childBucketUpper']);
			unset($this->data['childBucketLower']);
			
			return true;
		}
		
		return false;
	}
	
	/*public function setId($id){
		$this->data['id'] = (int)$id;
	}
	
	public function getId(){
		return (int)$this->data['id'];
	}*/
	
	public function setDistance($distance){
		$this->data['distance'] = $distance;
	}
	
	public function getDistance(){
		return $this->data['distance'];
	}
	
	public function setMask($mask){
		$this->data['mask'] = $mask;
	}
	
	public function getMask(){
		return $this->data['mask'];
	}
	
	public function getMaskHexStr(){
		$hex = '';
		for($pos = 0; $pos < Node::ID_LEN; $pos++){
			$hex .= dechex($this->data['mask'][$pos]);
		}
		return $hex;
	}
	
	public function setIsFull($isFull){
		$this->data['isFull'] = $isFull;
	}
	
	public function getIsFull(){
		return $this->data['isFull'];
	}
	
	public function isFull(){
		if($this->getNodesNum() >= static::$SIZE_MAX){
			// If full, stay full.
			$this->setIsFull(true);
		}
		
		return $this->getIsFull();
	}
	
	public function setIsUpper($isUpper){
		$this->data['isUpper'] = $isUpper;
	}
	
	public function getIsUpper(){
		return $this->data['isUpper'];
	}
	
	public function setIsLower($isLower){
		$this->data['isLower'] = $isLower;
	}
	
	public function getIsLower(){
		return $this->data['isLower'];
	}
	
	public function getNodes($levelMax = 0, $level = 0){
		#return $this->nodes;
		$nodes = $this->nodes;
		if($levelMax <= 0 || $level <= $levelMax){
			if($this->childBucketUpper){
				$nodes = array_merge($nodes, $this->childBucketUpper->getNodes($levelMax, $level + 1));
			}
			if($this->childBucketLower){
				$nodes = array_merge($nodes, $this->childBucketLower->getNodes($levelMax, $level + 1));
			}
		}
		return $nodes;
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
		$id = strtolower($id);
		foreach($this->nodes as $nodeId => $node){
			if($node->getIdHexStr() == $id){
				return $node;
			}
		}
		if($this->childBucketUpper){
			return $this->childBucketUpper->nodeFindByIdHexStr($id);
		}
		if($this->childBucketLower){
			return $this->childBucketLower->nodeFindByIdHexStr($id);
		}
		return null;
	}
	
	public function nodeFindByUri($uri){
		#fwrite(STDOUT, 'nodeFindByUri'."\n");
		foreach($this->nodes as $nodeId => $node){
			#fwrite(STDOUT, 'nodeFindByUri: '.$node->getIdHexStr().', '.(string)$node->getUri()."\n");
			if((string)$node->getUri() == $uri){
				#fwrite(STDOUT, 'nodeFindByUri: '.$node->getIdHexStr().', found'."\n");
				return $node;
			}
		}
		if($this->childBucketUpper){
			return $this->childBucketUpper->nodeFindByUri($id);
		}
		if($this->childBucketLower){
			return $this->childBucketLower->nodeFindByUri($id);
		}
		return null;
	}
	
	private function nodeAdd(Node $node, $sortNodes = true){
		$filePath = null;
		if($this->getDatadirBasePath()){
			#$filePath = $this->getDatadirBasePath().'/node_'.$node->getIdHexStr().'.yml';
			#$filePath = $this->getDatadirBasePath().'/node_'.$node->getIdHexStr().'_'.mt_rand(1000, 9999).'.yml';
			#$filePath = $this->getDatadirBasePath().'/node_'.substr($node->getIdHexStr(), -5).'_'.time().'.yml';
			$filePath = $this->getDatadirBasePath().'/node_'.substr($node->getIdHexStr(), -5).'.yml';
			#$filePath = $this->getDatadirBasePath().'/node_'.$node->getIdHexStr().'.yml';
		}
		if(!$node->getFilePath()){
			$node->setFilePath($filePath);
		}
		$node->setDatadirBasePath($this->getDatadirBasePath());
		$node->setBucket($this);
		$node->setDataChanged(true);
		
		$this->nodesId++;
		$this->nodes[$this->nodesId] = $node;
		$this->isFull();
		$this->setDataChanged(true);
		
		if($sortNodes){
			$this->nodesSort();
		}
	}
	
	private function setChildBucketUpper($distance){
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$mask = $this->getMask();
		if(!$this->childBucketUpper && $mask > 0){
			$newMask = $mask >> 1;
			
			#fwrite(STDOUT, 'upper new: '.intToBin($newMask).' ('.$newMask.')'."\n");
			
			$filePath = null;
			if($this->getDatadirBasePath()){
				#$filePath = $this->getDatadirBasePath().'/bucket_'.intToBin($newMask).'_1_'.time().'_'.mt_rand(1000, 9999).'.yml';
				#$filePath = $this->getDatadirBasePath().'/bucket_'.intToBin($newMask).'_1.yml';
				$filePath = $this->getDatadirBasePath().'/bucket_d'.intToBin($distance).'_m'.intToBin($newMask).'_1.yml';
				if(file_exists($filePath)){
					// This should never happen.
					throw new RuntimeException('path for bucket alread exists: '.$filePath);
				}
			}
			
			$bucket = new Bucket($filePath);
			$bucket->setDistance($distance);
			$bucket->setMask($newMask);
			$bucket->setIsUpper(true);
			$bucket->setDatadirBasePath($this->getDatadirBasePath());
			$bucket->setLocalNode($this->getLocalNode());
			$bucket->setDataChanged(true);
			$this->childBucketUpper = $bucket;
			
			$this->setDataChanged(true);
		}
		else{
			#fwrite(STDOUT, 'upper is set: '.intToBin($this->childBucketUpper->getMask()).' ('.$this->childBucketUpper->getMask().')'."\n");
		}
	}
	
	private function setChildBucketLower($distance){
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$mask = $this->getMask();
		if(!$this->childBucketLower && $mask > 0){
			$newMask = $mask >> 1;
			
			#fwrite(STDOUT, 'lower new: '.intToBin($newMask).' ('.$newMask.')'."\n");
			
			$filePath = null;
			if($this->getDatadirBasePath()){
				#$filePath = $this->getDatadirBasePath().'/bucket_'.intToBin($newMask).'_0_'.time().'_'.mt_rand(1000, 9999).'.yml';
				#$filePath = $this->getDatadirBasePath().'/bucket_'.intToBin($newMask).'_0.yml';
				$filePath = $this->getDatadirBasePath().'/bucket_d'.intToBin($distance).'_m'.intToBin($newMask).'_0.yml';
				if(file_exists($filePath)){
					// This should never happen.
					throw new RuntimeException('path for bucket alread exists: '.$filePath);
				}
			}
			
			$bucket = new Bucket($filePath);
			$bucket->setDistance($distance);
			$bucket->setMask($newMask);
			$bucket->setIsLower(true);
			$bucket->setDatadirBasePath($this->getDatadirBasePath());
			$bucket->setLocalNode($this->getLocalNode());
			$bucket->setDataChanged(true);
			$this->childBucketLower = $bucket;
			
			$this->setDataChanged(true);
		}
		else{
			#fwrite(STDOUT, 'lower is set: '.intToBin($this->childBucketLower->getMask()).' ('.$this->childBucketLower->getMask().')'."\n");
		}
	}
	
	private function nodesReEnclose($sortNodes = true, $level = 1){
		fwrite(STDOUT, str_repeat("\t", $level).'reenclose: '.$level."\n");
		
		if($level >= 1000){
			fwrite(STDOUT, str_repeat("\t", $level).'ERROR: level '.$level.' is too deep'."\n");
			throw new RuntimeException('reenclose level too deep: '.$level);
			#return;
		}
		
		$mask = $this->getMask();
		if($mask > 0){
			foreach($this->nodes as $nodeId => $node){
				fwrite(STDOUT, str_repeat("\t", $level).'reenclose node: '.$nodeId."\n");
				
				$distance = $this->getLocalNode()->distance($node);
				
				$bucket = null;
				if($distance[15] & $mask){
					fwrite(STDOUT, str_repeat("\t", $level).'reenclose match: upper'."\n");
					$this->setChildBucketUpper($distance[15]); # TODO
					$bucket = $this->childBucketUpper;
				}
				else{
					fwrite(STDOUT, str_repeat("\t", $level).'reenclose match: lower'."\n");
					$this->setChildBucketLower($distance[15]); # TODO
					$bucket = $this->childBucketLower;
				}
				
				if($bucket === null){
					fwrite(STDOUT, str_repeat("\t", $level).'reenclose bucket is full: l='.$level.' d='.intToBin($distance[15]).' m='.intToBin($mask).' n='.$node->getIdHexStr().''."\n");
					#throw new RuntimeException('reenclose bucket is full: l='.$level.' d='.intToBin($distance[15]).' m='.intToBin($mask).' n='.$node->getIdHexStr().'');
				}
				else{
					fwrite(STDOUT, str_repeat("\t", $level).'reenclose child bucket: '.intToBin($bucket->getMask()).' d='.intToBin($bucket->getDistance()).''."\n");
					
					$bucket->nodeEnclose($node, $sortNodes, $level + 1);
				}
			}
			
			$this->nodes = array();
		}
		else{
			fwrite(STDOUT, str_repeat("\t", $level).'reenclose failed: '.intToBin($mask).' ('.$mask.')'."\n");
			#throw new RuntimeException('reenclose failed: l='.$level.' m='.intToBin($mask).'');
			
			# TODO: what happens when a bucket is full?
		}
	}
	
	public function nodeEnclose(Node $node, $sortNodes = true, $level = 1){
		$nodeEncloseReturnValue = $node;
		
		if($level <= 1000){
			if($node->getIdHexStr() != '00000000-0000-4000-8000-000000000000'){
				$distance = $this->getLocalNode()->distance($node);
				
				$mask = 1 << 2; // Root Mask # TODO
				
				if($this->getIsUpper() || $this->getIsLower()){
					fwrite(STDOUT, str_repeat("\t", $level).'no root bucket'."\n");
					$mask = $this->getMask();
				}
				else{
					fwrite(STDOUT, str_repeat("\t", $level).'root bucket'."\n");
					$this->setMask($mask);
				}
				#if($mask > 0){
				#	$newMask = $mask >> 1; # TODO: not needed
				#}
				
				fwrite(STDOUT, str_repeat("\t", $level).'level: '.$level."\n");
				fwrite(STDOUT, str_repeat("\t", $level).'node: '.$node->getIdHexStr()."\n");
				fwrite(STDOUT, str_repeat("\t", $level).'dist: '.intToBin($distance[15])."\n");
				fwrite(STDOUT, str_repeat("\t", $level).'mask: '.intToBin($mask)."\n");
				
				if($this->childBucketUpper){
					fwrite(STDOUT, str_repeat("\t", $level).'upper: '.intToBin($this->childBucketUpper->getMask())."\n");
				}
				else{
					fwrite(STDOUT, str_repeat("\t", $level).'upper: N/A'."\n");
				}
				if($this->childBucketLower){
					fwrite(STDOUT, str_repeat("\t", $level).'lower: '.intToBin($this->childBucketLower->getMask())."\n");
				}
				else{
					fwrite(STDOUT, str_repeat("\t", $level).'lower: N/A'."\n");
				}
				
				$onode = $this->nodeFind($node);
				if(!$onode){
					
					if($this->getNodesNum() < static::$SIZE_MAX && !$this->getIsFull()){
						fwrite(STDOUT, str_repeat("\t", $level).'add node'."\n");
						
						$this->nodeAdd($node, $sortNodes);
						$nodeEncloseReturnValue = $node;
						
						if($this->isFull()){
							fwrite(STDOUT, str_repeat("\t", $level).'FULL end'."\n");
							$this->nodesReEnclose($sortNodes, $level + 1);
						}
					}
					else{
						fwrite(STDOUT, str_repeat("\t", $level).'FULL new'."\n");
						
						$bucket = null;
						if($distance[15] & $mask){ # TODO
							fwrite(STDOUT, str_repeat("\t", $level).'match: upper'."\n");
							$this->setChildBucketUpper($distance[15]); # TODO
							$bucket = $this->childBucketUpper;
						}
						else{
							fwrite(STDOUT, str_repeat("\t", $level).'match: lower'."\n");
							$this->setChildBucketLower($distance[15]); # TODO
							$bucket = $this->childBucketLower;
						}
						
						if($bucket === null){
							fwrite(STDOUT, str_repeat("\t", $level).'enclose: bucket is full: l='.$level.' d='.intToBin($distance[15]).' m='.intToBin($mask).' n='.$node->getIdHexStr().''."\n");
							
							#throw new RuntimeException('enclose: bucket is full: l='.$level.' d='.intToBin($distance[15]).' m='.intToBin($mask).' n='.$node->getIdHexStr().'');
							
							# TODO: what happens when a bucket is not found?
						}
						else{
							fwrite(STDOUT, str_repeat("\t", $level).'child bucket: '.intToBin($bucket->getMask()).' d='.intToBin($bucket->getDistance()).''."\n");
							
							$bucket->nodeEnclose($node, $sortNodes, $level + 1);
						}
					}
				}
				else{
					$onode->update($node);
					$nodeEncloseReturnValue = $onode;
				}
			}
		}
		else{
			# TODO
			fwrite(STDOUT, str_repeat("\t", $level).'ERROR: level '.$level.' is too deep'."\n");
			throw new RuntimeException('enclose level too deep: '.$level);
		}
		
		return $nodeEncloseReturnValue;
	}
	
	public function nodeRemoveByIndex($index){
		unset($this->nodes[$index]);
	}
	
	public function nodesSort(){
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
