<?php

namespace TheFox\Dht\Kademlia;

use Exception;

use TheFox\Yaml\YamlStorage;

class Table extends YamlStorage{
	
	private $nodeLocal;
	private $bucketsId;
	private $buckets;
	private $bucketsByMask;
	
	public function __construct($datadirBasePath, Node $nodeLocal){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->setDatadirBasePath($datadirBasePath);
		if($this->getDatadirBasePath()){
			$this->setFilePath($this->getDatadirBasePath().'/table.yml');
		}
		$this->setNodeLocal($nodeLocal);
		
		
		$this->data['timeCreated'] = time();
		$this->data['bucketsId'] = 1000;
		$this->buckets = new StackableArray();
		$this->bucketsByMask = new StackableArray();
		$this->load();
	}
	
	public function save(){
		#print "".__CLASS__."->".__FUNCTION__.": begin\n";
		$this->data['buckets'] = new StackableArray();
		
		$buckets = $this['buckets'];
		foreach($buckets as $id => $bucket){
			$bucket->save();
			
			$bucketAr = new StackableArray();
			$bucketAr['id'] 		= $bucket->getId();
			$bucketAr['path'] 		= $bucket->getFilePath();
			$bucketAr['mask'] 		= $bucket->getMask();
			
			$this->data['buckets'][$id] = $bucketAr;
		}
		
		$rv = parent::save();
		unset($this->data['buckets']);
		
		return $rv;
	}
	
	public function load(){
		if(parent::load()){
			
			if(isset($this->data['buckets']) && $this->data['buckets']){
				foreach($this->data['buckets'] as $id => $bucket){
					if(file_exists($bucket['path'])){
						$bucketObj = new Bucket($id, $bucket['mask']);
						$bucketObj->setFilePath($bucket['path']);
						$bucketObj->setNodeLocal($this->getNodeLocal());
						$bucketObj->load();
						
						$this->buckets[$id] = $bucketObj;
						$this->bucketsByMask[$bucket['mask']] = $bucketObj;
					}
				}
			}
			
			unset($this->data['buckets']);
			
			$this->data['bucketsId'] = (int)$this->data['bucketsId'];
			
			return true;
		}
		
		return false;
	}
	
	public function bucketsIdInc(){
		$this->data['bucketsId'] = (int)$this->data['bucketsId'] + 1;
		
		$this->setDataChanged();
	}
	
	public function getBucketsId(){
		return $this->data['bucketsId'];
	}
	
	public function bucketAdd(Bucket $bucket){
		$this->buckets[$this->getBucketsId()] = $bucket;
		$this->bucketsByMask[$bucket->getMask()] = $bucket;
		$this->setDataChanged();
	}
	
	public function setNodeLocal(Node $node){
		$this->nodeLocal = $node;
	}
	
	public function getNodeLocal(){
		return $this->nodeLocal;
	}
	
	public function getNodes(){
		$nodes = array();
		
		foreach($this->buckets as $bucketId => $bucket){
			foreach($bucket->getNodes() as $cnodeId => $cnode){
				$nodes[] = $cnode;
			}
		}
		
		return new StackableArray($nodes);
	}
	
	public function getNodesNum(){
		return count($this->getNodes());
	}
	
	public function getNodesClosest($num = 20){
		$nodes = array();
		
		foreach($this->buckets as $bucketId => $bucket){
			foreach($bucket->getNodes() as $cnodeId => $cnode){
				$nodes[$this->getNodeLocal()->distanceHexStr($cnode)] = $cnode;
				ksort($nodes, SORT_STRING);
				$nodes = array_slice($nodes, 0, $num);
			}
		}
		
		$rv = array_values($nodes);
		return new StackableArray($rv);
	}
	
	public function nodeFindInBuckets(Node $node){
		foreach($this->buckets as $bucketId => $bucket){
			if($onode = $bucket->nodeFind($node)){
				return $onode;
			}
		}
		
		return null;
	}
	
	public function nodeFindClosest(Node $node, $num = 8){
		$nodes = array();
		
		foreach($this->buckets as $bucketId => $bucket){
			foreach($bucket->getNodes() as $cnodeId => $cnode){
				if(!$cnode->isEqual($node)){
					$nodes[$cnode->distanceHexStr($node)] = $cnode;
					ksort($nodes, SORT_STRING);
					$nodes = array_slice($nodes, 0, $num);
				}
			}
		}
		
		$rv = array_values($nodes);
		return new StackableArray($rv);
	}
	
	public function nodeFindByKeyPubFingerprint($fingerprint){
		foreach($this->buckets as $bucketId => $bucket){
			foreach($bucket->getNodes() as $cnodeId => $cnode){
				if($fingerprint == $cnode->getSslKeyPubFingerprint()){
					return $cnode;
				}
			}
		}
		
		return null;
	}
	
	public function nodeEnclose(Node $node){
		#print "".__CLASS__."->".__FUNCTION__.": begin\n";
		
		$rv = $node;
		
		if( !$this->getNodeLocal()->isEqual($node) ){
			$distance = $this->getNodeLocal()->distance($node);
			
			$onode = $this->nodeFindInBuckets($node);
			if($onode){
				if( $node->getTimeLastSeen() > $onode->getTimeLastSeen() ){
					$onode->setIp($node->getIp());
					$onode->setPort($node->getPort());
					$onode->setTimeLastSeen($node->getTimeLastSeen());
					$onode->setDataChanged();
					
					$onode->getBucket()->setDataChanged();
				}
				
				$rv = $onode;
			}
			else{
				$idLenBits = Node::ID_LEN_BITS - 1;
				$mbase = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
				
				while(true){
					
					$idPos = 0;
					$mask = 0;
					for($bits = $idLenBits; $bits >= 0; $bits--){
						$idPos = Node::ID_LEN - floor($bits / 8) - 1;
						$mask = $mbase[$idPos] | 1 << (7 - (Node::ID_LEN_BITS - $idPos * 8 - $bits - 1));
						if( ($distance[$idPos] & $mask) == $mask ){
							break;
						}
					}
					
					$maskName = ((string)(Node::ID_LEN - $idPos - 1)).'_'.((string)$mask);
					#print "".__CLASS__."->".__FUNCTION__.": pos $idPos, $mask, $maskName\n";
					
					$bucket = null;
					if(isset($this->bucketsByMask[$maskName])){
						$bucket = $this->bucketsByMask[$maskName];
						
						if($bucket->isFull()){
							
							$mbasePos = Node::ID_LEN - floor($idLenBits / 8) - 1;
							$mbase[$mbasePos] = $mbase[$mbasePos] | 1 << (7 - (Node::ID_LEN_BITS - $idLenBits - 1) % 8);
							$idLenBits--;
							
							$bucketsUsed = array();
							foreach($bucket->getNodes() as $bnodeIndex => $bnode){
								$bdistance = $this->getNodeLocal()->distance($bnode);
								
								$idPos = 0;
								$bmask = 0;
								for($bits = $idLenBits; $bits >= 0; $bits--){
									$idPos = Node::ID_LEN - floor($bits / 8) - 1;
									$bmask = $mbase[$idPos] | 1 << (7 - (Node::ID_LEN_BITS - $idPos * 8 - $bits - 1));
									if( ($bdistance[$idPos] & $bmask) == $bmask ){
										break;
									}
								}
								$bmaskName = ((string)(Node::ID_LEN - $idPos - 1)).'_'.((string)$bmask);
								
								// Move node from old bucket to new bucket.
								if(isset($this->bucketsByMask[$bmaskName])){
									$nbucket = $this->bucketsByMask[$bmaskName];
									
									if($nbucket->isFull()){
										throw new Exception('Bucket with mask '.$bmaskName.' can\'t be full.');
									}
									
									$nbucket->nodeAdd($bnode, false);
								}
								else{
									$this->bucketsIdInc();
									
									$nbucket = new Bucket($this->getBucketsId(), $bmaskName);
									if($this->getDatadirBasePath()){
										$nbucket->setFilePath($this->getDatadirBasePath().'/bucket_'.$bmaskName.'.yml');
									}
									$nbucket->setNodeLocal($this->getNodeLocal());
									$nbucket->nodeAdd($bnode);
									
									$this->bucketAdd($nbucket);
									
									$bucketsUsed[$nbucket->getId()] = $nbucket;
								}
								
								// Remove node from old bucket.
								$bucket->nodeRemoveByIndex($bnodeIndex);
							}
							
							#$bucket->save();
							
							foreach($bucketsUsed as $id => $ubucket){
								$ubucket->nodesSort();
								#$ubucket->save();
							}
							
							$this->setDataChanged();
						}
						else{
							$bucket->nodeAdd($node);
							#$bucket->save();
							break;
						}
						
					}
					else{
						$this->bucketsIdInc();
						
						$bucket = new Bucket($this->getBucketsId(), $maskName);
						if($this->getDatadirBasePath()){
							$bucket->setFilePath($this->getDatadirBasePath().'/bucket_'.$maskName.'.yml');
						}
						$bucket->setNodeLocal($this->getNodeLocal());
						$bucket->nodeAdd($node);
						#$bucket->save();
						
						$this->bucketAdd($bucket);
						
						break;
					}
					
				}
			}
			
		}
		
		return $rv;
	}
	
}
