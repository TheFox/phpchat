<?php

namespace TheFox\Dht\Kademlia;

use Exception;
use RuntimeException;

use TheFox\Yaml\YamlStorage;

class Table extends YamlStorage{
	
	private $buckets = array();
	private $bucketsByMask = array();
	private $localNode = null;
	
	public function __construct($filePath = null){
		parent::__construct($filePath);
		
		$this->data['bucketsId'] = 0;
	}
	
	public function save(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->data['buckets'] = array();
		
		foreach($this->buckets as $bucketId => $bucket){
			$this->data['buckets'][$bucketId] = array(
				'path' => $bucket->getFilePath(),
			);
			
			$bucket->save();
		}
		
		$rv = parent::save();
		unset($this->data['buckets']);
		
		return $rv;
	}
	
	public function load(){
		if(parent::load()){
			
			if(isset($this->data['buckets']) && $this->data['buckets']){
				foreach($this->data['buckets'] as $bucketId => $bucketAr){
					$bucket = new Bucket($bucketAr['path']);
					$bucket->setDatadirBasePath($this->getDatadirBasePath());
					$bucket->load();
					
					$this->buckets[$bucketId] = $bucket;
					$this->bucketsByMask[$bucket->getMask()] = $bucket;
				}
			}
			
			unset($this->data['buckets']);
			
			$this->data['bucketsId'] = (int)$this->data['bucketsId'];
			
			return true;
		}
		
		return false;
	}
	
	public function getBucketsId(){
		return $this->data['bucketsId'];
	}
	
	public function bucketNew(){
		$this->data['bucketsId']++;
		
		$bucket = new Bucket($this->getDatadirBasePath().'/bucket_'.$this->data['bucketsId'].'.yml');
		$bucket->setId($this->data['bucketsId']);
		$bucket->setDatadirBasePath($this->getDatadirBasePath());
		$bucket->setLocalNode($this->getLocalNode());
		
		$this->buckets[$this->getBucketsId()] = $bucket;
		$this->bucketsByMask[$bucket->getMask()] = $bucket;
		
		$this->setDataChanged(true);
		
		return $bucket;
	}
	
	public function setLocalNode(Node $node){
		$this->localNode = $node;
	}
	
	public function getLocalNode(){
		return $this->localNode;
	}
	
	public function getNodes(){
		$nodes = array();
		
		foreach($this->buckets as $bucketId => $bucket){
			foreach($bucket->getNodes() as $cnodeId => $cnode){
				$nodes[] = $cnode;
			}
		}
		
		return $nodes;
	}
	
	public function getNodesNum(){
		return count($this->getNodes());
	}
	
	public function getNodesClosest($num = 20){
		$nodes = array();
		
		foreach($this->buckets as $bucketId => $bucket){
			foreach($bucket->getNodes() as $cnodeId => $cnode){
				$nodes[$this->getLocalNode()->distanceHexStr($cnode)] = $cnode;
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
		
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$rv = $node;
		
		if( !$this->getLocalNode()->isEqual($node) ){
			$distance = $this->getLocalNode()->distance($node);
			
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
				$mbase = array_fill(0, Node::ID_LEN, 0);
				
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
								$bdistance = $this->getLocalNode()->distance($bnode);
								
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
									$nbucket = $this->bucketNew();
									$nbucket->nodeAdd($bnode);
									
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
						$this->bucketNew();
						
						break;
					}
					
				}
			}
			
		}
		
		return $rv;
	}
	
}
