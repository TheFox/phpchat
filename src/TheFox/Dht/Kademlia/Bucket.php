<?php

namespace TheFox\Dht\Kademlia;

use RuntimeException;

use TheFox\Storage\YamlStorage;

class Bucket extends YamlStorage{
	
	static $SIZE_MAX = 20;
	
	#private $nodesId = 0;
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
		$this->data['maskByte'] = 0;
		$this->data['maskByteName'] = 0;
		$this->data['maskBit'] = 0;
		$this->data['maskBitName'] = 0;
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
		$this->data['maskBitName'] = intToBin($this->getMaskByte());
		$this->data['maskBitName'] = intToBin($this->getMaskBit());
		
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
		#fwrite(STDOUT, __FUNCTION__.': '.$this->getFilePath().''."\n");
		
		if(parent::load()){
			
			if($this->data){
				if(array_key_exists('nodes', $this->data) && $this->data['nodes']){
					foreach($this->data['nodes'] as $nodeId => $nodeAr){
						if(file_exists($nodeAr['path'])){
							#$this->nodesId = $nodeId;
							
							$node = new Node($nodeAr['path']);
							$node->setDatadirBasePath($this->getDatadirBasePath());
							$node->setBucket($this);
							if($node->load()){
								#$this->nodes[$this->nodesId] = $node;
								$this->nodes[$nodeId] = $node;
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
	
	public function setMaskByte($maskByte){
		$this->data['maskByte'] = $maskByte;
	}
	
	public function getMaskByte(){
		return $this->data['maskByte'];
	}
	
	public function setMaskBit($maskBit){
		$this->data['maskBit'] = $maskBit;
	}
	
	public function getMaskBit(){
		return $this->data['maskBit'];
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
		/*foreach($this->nodes as $nodeId => $node){
			if($node->getIdHexStr() == $id){
				return $node;
			}
		}*/
		if(isset($this->nodes[$id])){
			return $this->nodes[$id];
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
		$nodeId = $node->getIdHexStr();
		
		$filePath = null;
		if($this->getDatadirBasePath()){
			#$filePath = $this->getDatadirBasePath().'/node_'.$node->getIdHexStr().'.yml';
			#$filePath = $this->getDatadirBasePath().'/node_'.$node->getIdHexStr().'_'.mt_rand(1000, 9999).'.yml';
			#$filePath = $this->getDatadirBasePath().'/node_'.substr($nodeId, -5).'_'.time().'.yml';
			$filePath = $this->getDatadirBasePath().'/node_'.substr($nodeId, -5).'.yml';
			#$filePath = $this->getDatadirBasePath().'/node_'.$nodeId.'.yml'; # TODO
		}
		if(!$node->getFilePath()){
			$node->setFilePath($filePath);
		}
		$node->setDatadirBasePath($this->getDatadirBasePath());
		$node->setBucket($this);
		$node->setDataChanged(true);
		
		#$this->nodesId++;
		#$this->nodes[$this->nodesId] = $node;
		$this->nodes[$nodeId] = $node;
		$this->isFull();
		$this->setDataChanged(true);
		
		/*if($sortNodes){
			$this->nodesSort();
		}*/
	}
	
	private function maskDecr(){
		$maskByte = $this->getMaskByte();
		$maskBit = $this->getMaskBit();
		
		$newMaskByte = $maskByte;
		$newMaskBit = $maskBit - 1;
		$newMaskBitValue = 1 << $newMaskBit;
		
		if($newMaskBitValue <= 0){
			$newMaskByte++;
			$newMaskBit = 7;
			$newMaskBitValue = 1 << $newMaskBit;
		}
		
		return array($newMaskByte, $newMaskBit, $newMaskBitValue);
	}
	
	private function setChildBucketUpper($distance){
		if(!$this->childBucketUpper){
			list($newMaskByte, $newMaskBit, $newMaskBitValue) = $this->maskDecr();
			
			if($newMaskByte < Node::ID_LEN_BYTE && $newMaskBitValue > 0){
				$filePath = null;
				if($this->getDatadirBasePath()){
					#$filePath = $this->getDatadirBasePath().'/bucket_'.intToBin($newMaskBit).'_1_'.time().'_'.mt_rand(1000, 9999).'.yml';
					#$filePath = $this->getDatadirBasePath().'/bucket_'.intToBin($newMaskBit).'_1.yml';
					#$filePath = $this->getDatadirBasePath().'/bucket_d'.intToBin($distance).'_m'.intToBin($newMaskBit).'_1.yml';
					#$filePath = $this->getDatadirBasePath().'/bucket_d'.intToBin($distance).'_m'.$newMaskByte.'.'.intToBin($newMaskBit).'_1.yml';
					
					$filePath = $this->getDatadirBasePath().'/bucket';
					$filePath .= '_m'.sprintf('%02d', $newMaskByte).'.'.$newMaskBit;
					$filePath .= '_d'.sprintf('%03d', $distance);
					$filePath .= '_u';
					$filePath .= '.yml';
					
					if(file_exists($filePath)){
						// This should never happen.
						throw new RuntimeException('path for bucket alread exists: '.$filePath);
					}
				}
				
				$bucket = new Bucket($filePath);
				$bucket->setDistance($distance);
				$bucket->setMaskByte($newMaskByte);
				$bucket->setMaskBit($newMaskBit);
				$bucket->setIsUpper(true);
				$bucket->setDatadirBasePath($this->getDatadirBasePath());
				$bucket->setLocalNode($this->getLocalNode());
				$bucket->setDataChanged(true);
				$this->childBucketUpper = $bucket;
				
				$this->setDataChanged(true);
			}
			else{
				throw new RuntimeException('setChildBucketUpper: mask is at the end: /'.$maskByte.'/ /'.intToBin($maskBitValue).'/ ('.$maskBitValue.')');
			}
		}
		else{
			#fwrite(STDOUT, 'upper is set: '.intToBin($this->childBucketUpper->getMaskBit()).' ('.$this->childBucketUpper->getMaskBit().')'."\n");
		}
	}
	
	private function setChildBucketLower($distance){
		if(!$this->childBucketLower){
			list($newMaskByte, $newMaskBit, $newMaskBitValue) = $this->maskDecr();
			
			if($newMaskByte < Node::ID_LEN_BYTE && $newMaskBitValue > 0){
				$filePath = null;
				if($this->getDatadirBasePath()){
					#$filePath = $this->getDatadirBasePath().'/bucket_'.intToBin($newMaskBit).'_0_'.time().'_'.mt_rand(1000, 9999).'.yml';
					#$filePath = $this->getDatadirBasePath().'/bucket_'.intToBin($newMaskBit).'_0.yml';
					#$filePath = $this->getDatadirBasePath().'/bucket_d'.intToBin($distance).'_m'.intToBin($newMaskBit).'_0.yml';
					#$filePath = $this->getDatadirBasePath().'/bucket_d'.intToBin($distance).'_m'.$newMaskByte.'.'.intToBin($newMaskBit).'_0.yml';
					
					$filePath = $this->getDatadirBasePath().'/bucket';
					$filePath .= '_m'.sprintf('%02d', $newMaskByte).'.'.$newMaskBit;
					$filePath .= '_d'.sprintf('%03d', $distance);
					$filePath .= '_l';
					$filePath .= '.yml';
					
					if(file_exists($filePath)){
						// This should never happen.
						throw new RuntimeException('path for bucket alread exists: '.$filePath);
					}
				}
				
				$bucket = new Bucket($filePath);
				$bucket->setDistance($distance);
				$bucket->setMaskByte($newMaskByte);
				$bucket->setMaskBit($newMaskBit);
				$bucket->setIsLower(true);
				$bucket->setDatadirBasePath($this->getDatadirBasePath());
				$bucket->setLocalNode($this->getLocalNode());
				$bucket->setDataChanged(true);
				$this->childBucketLower = $bucket;
				
				$this->setDataChanged(true);
			}
			else{
				throw new RuntimeException('setChildBucketLower: mask is at the end: /'.$maskByte.'/ /'.intToBin($maskBitValue).'/ ('.$maskBitValue.')');
			}
		}
		else{
			#fwrite(STDOUT, 'upper is set: '.intToBin($this->childBucketLower->getMaskBit()).' ('.$this->childBucketLower->getMaskBit().')'."\n");
		}
	}
	
	private function nodesReEnclose($sortNodes = true, $level = 1){
		$printLevel = $level;
		if($printLevel >= 5){
			$printLevel = 5;
		}
		#fwrite(STDOUT, str_repeat("\t", $printLevel).'reenclose: '.$level."\n");
		
		/*if($level >= 129){
			#fwrite(STDOUT, str_repeat("\t", $printLevel).'ERROR: level '.$level.' is too deep'."\n");
			throw new RuntimeException('reenclose level too deep: '.$level);
			#return;
		}*/
		
		$maskByte = $this->getMaskByte();
		$maskBit = $this->getMaskBit();
		$maskBitValue = 1 << $maskBit;
		if($maskByte < Node::ID_LEN_BYTE && $maskBitValue > 0){
			foreach($this->nodes as $nodeId => $node){
				#fwrite(STDOUT, str_repeat("\t", $printLevel).'reenclose node: '.$nodeId."\n");
				
				#$distance = $this->getLocalNode()->distance($node);
				$distance = $node->getDistance();
				
				$bucket = null;
				if($distance[$maskByte] & $maskBitValue){
					#fwrite(STDOUT, str_repeat("\t", $printLevel).'reenclose match: upper'."\n");
					$this->setChildBucketUpper($distance[$maskByte]);
					$bucket = $this->childBucketUpper;
				}
				else{
					#fwrite(STDOUT, str_repeat("\t", $printLevel).'reenclose match: lower'."\n");
					$this->setChildBucketLower($distance[$maskByte]);
					$bucket = $this->childBucketLower;
				}
				
				if($bucket === null){
					#fwrite(STDOUT, str_repeat("\t", $printLevel).'reenclose bucket is full: l='.$level.' d='.intToBin($distance[$maskByte]).' m='.intToBin($maskBitValue).' n='.$node->getIdHexStr().''."\n");
					#throw new RuntimeException('reenclose bucket is full: l='.$level.' d='.intToBin($distance[$maskByte]).' m='.intToBin($maskBitValue).' n='.$node->getIdHexStr().'');
				}
				else{
					#fwrite(STDOUT, str_repeat("\t", $printLevel).'reenclose child bucket: /'.$bucket->getMaskByte().'/ '.intToBin($bucket->getMaskBit()).' d='.intToBin($bucket->getDistance()).''."\n");
					
					$bucket->nodeEnclose($node, $sortNodes, $level + 1);
				}
			}
			
			$this->nodes = array();
			$this->setDataChanged(true);
		}
		else{
			#fwrite(STDOUT, str_repeat("\t", $printLevel).'reenclose failed: '.intToBin($maskBitValue).' ('.$maskBitValue.')'."\n");
			#throw new RuntimeException('reenclose failed: l='.$level.' m='.intToBin($maskBitValue).'');
			
			# TODO: what happens when a bucket is full?
		}
	}
	
	public function nodeEnclose(Node $node, $sortNodes = true, $level = 1){
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$nodeEncloseReturnValue = $node;
		
		
		/*if($level >= 260){
			fwrite(STDOUT, str_repeat("\t", $level).'ERROR: level '.$level.' is too deep'."\n");
			throw new RuntimeException('enclose level too deep: '.$level);
		}*/
		
		$printLevel = $level;
		if($printLevel >= 5){
			$printLevel = 5;
		}
		
		if($node->getIdHexStr() != '00000000-0000-4000-8000-000000000000'){
			$distance = $node->getDistance();
			if(!$distance){
				$distance = $this->getLocalNode()->distance($node);
				$node->setDistance($distance);
			}
			
			$maskByte = 0;
			$maskBit = 7; // Root MaskBit
			
			if($this->getIsUpper() || $this->getIsLower()){
				#fwrite(STDOUT, str_repeat("\t", $printLevel).'no root bucket'."\n");
				$maskByte = $this->getMaskByte();
				$maskBit = $this->getMaskBit();
			}
			else{
				#fwrite(STDOUT, str_repeat("\t", $printLevel).'root bucket'."\n");
				$this->setMaskByte($maskByte);
				$this->setMaskBit($maskBit);
			}
			
			$maskBitValue = 1 << $maskBit;
			
			#fwrite(STDOUT, str_repeat("\t", $printLevel).'level: '.$level."\n");
			#fwrite(STDOUT, str_repeat("\t", $printLevel).'node: '.$node->getIdHexStr()."\n");
			#fwrite(STDOUT, str_repeat("\t", $printLevel).'dist: '.intToBin($distance[$maskByte])."\n");
			#fwrite(STDOUT, str_repeat("\t", $printLevel).'maskByte: '.$maskByte."\n");
			#fwrite(STDOUT, str_repeat("\t", $printLevel).'maskBit: '.$maskBit."\n");
			#fwrite(STDOUT, str_repeat("\t", $printLevel).'maskBitValue: '.intToBin($maskBitValue)."\n");
			#fwrite(STDOUT, str_repeat("\t", $printLevel).'mask: /'.$maskByte.'/ /'.$maskBit.'/ /'.intToBin($maskBitValue).'/'."\n");
			
			if($this->childBucketUpper){
				#fwrite(STDOUT, str_repeat("\t", $printLevel).'upper: '.intToBin($this->childBucketUpper->getMaskBit())."\n");
			}
			else{
				#fwrite(STDOUT, str_repeat("\t", $printLevel).'upper: N/A'."\n");
			}
			if($this->childBucketLower){
				#fwrite(STDOUT, str_repeat("\t", $printLevel).'lower: '.intToBin($this->childBucketLower->getMaskBit())."\n");
			}
			else{
				#fwrite(STDOUT, str_repeat("\t", $printLevel).'lower: N/A'."\n");
			}
			
			#timeStop('onode find start');
			$onode = $this->nodeFind($node);
			#timeStop('onode find end');
			if(!$onode){
				#fwrite(STDOUT, str_repeat("\t", $printLevel).'old node not found'."\n");
				
				if($this->getNodesNum() < static::$SIZE_MAX && !$this->getIsFull()){
					#fwrite(STDOUT, str_repeat("\t", $printLevel).'add node'."\n");
					
					$this->nodeAdd($node, $sortNodes);
					$nodeEncloseReturnValue = $node;
					
					if($this->isFull()){
						#fwrite(STDOUT, str_repeat("\t", $printLevel).'FULL end'."\n");
						#timeStop('nodesReEnclose start');
						$this->nodesReEnclose($sortNodes, $level + 1);
						#timeStop('nodesReEnclose end');
					}
				}
				else{
					#fwrite(STDOUT, str_repeat("\t", $printLevel).'FULL new'."\n");
					
					$bucket = null;
					if($distance[$maskByte] & $maskBitValue){
						#fwrite(STDOUT, str_repeat("\t", $printLevel).'match: upper'."\n");
						$this->setChildBucketUpper($distance[$maskByte]);
						$bucket = $this->childBucketUpper;
					}
					else{
						#fwrite(STDOUT, str_repeat("\t", $printLevel).'match: lower'."\n");
						$this->setChildBucketLower($distance[$maskByte]);
						$bucket = $this->childBucketLower;
					}
					
					if($bucket === null){
						#fwrite(STDOUT, str_repeat("\t", $printLevel).'enclose: bucket is full: l='.$level.' d='.intToBin($distance[$maskByte]).' m='.intToBin($maskBitValue).' n='.$node->getIdHexStr().''."\n");
						
						#throw new RuntimeException('enclose: bucket is full: l='.$level.' d='.intToBin($distance[$maskByte]).' m='.intToBin($maskBitValue).' n='.$node->getIdHexStr().'');
						
						# TODO: what happens when a bucket is not found?
					}
					else{
						#fwrite(STDOUT, str_repeat("\t", $printLevel).'child bucket: '.intToBin($bucket->getMaskBit()).' d='.intToBin($bucket->getDistance()).''."\n");
						
						$bucket->nodeEnclose($node, $sortNodes, $level + 1);
					}
				}
			}
			else{
				#fwrite(STDOUT, str_repeat("\t", $printLevel).'update existing node'."\n");
				#usleep(1000000);
				#timeStop('onode update start');
				$onode->update($node);
				#timeStop('onode update end');
				$nodeEncloseReturnValue = $onode;
			}
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
