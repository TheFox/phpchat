<?php

namespace TheFox\Dht\Kademlia;

use Exception;
use RuntimeException;

use TheFox\Storage\YamlStorage;

class Table extends YamlStorage{
	
	private $buckets = array();
	private $bucketsByMask = array();
	private $localNode = null;
	
	public function __construct($filePath = null){
		parent::__construct($filePath);
		
		$this->data['bucketsId'] = 0;
		$this->data['timeCreated'] = time();
	}
	
	public function __sleep(){
		return array('data', 'buckets', 'localNode');
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
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(parent::load()){
			
			if($this->data && array_key_exists('buckets', $this->data) && $this->data['buckets']){
				foreach($this->data['buckets'] as $bucketId => $bucketAr){
					if(file_exists($bucketAr['path'])){
						$bucket = new Bucket($bucketAr['path']);
						$bucket->setDatadirBasePath($this->getDatadirBasePath());
						$bucket->setLocalNode($this->getLocalNode());
						
						if($bucket->load()){
							$this->buckets[$bucketId] = $bucket;
							$this->bucketsByMask[$bucket->getMask()] = $bucket;
						}
					}
				}
			}
			
			unset($this->data['buckets']);
			
			$this->data['bucketsId'] = (int)$this->data['bucketsId'];
			
			return true;
		}
		
		return false;
	}
	
	private function bucketNew($mask){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#print __CLASS__.'->'.__FUNCTION__.': '.$this->getDatadirBasePath()."\n";
		
		$this->data['bucketsId']++;
		
		$filePath = null;
		if($this->getDatadirBasePath()){
			$filePath = $this->getDatadirBasePath().'/bucket_'.$this->data['bucketsId'].'.yml';
		}
		
		$bucket = new Bucket($filePath);
		$bucket->setId($this->data['bucketsId']);
		$bucket->setMask($mask);
		$bucket->setDatadirBasePath($this->getDatadirBasePath());
		$bucket->setLocalNode($this->getLocalNode());
		
		$this->buckets[$this->data['bucketsId']] = $bucket;
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
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$nodes = array();
		
		foreach($this->buckets as $bucketId => $bucket){
			foreach($bucket->getNodes() as $cnodeId => $cnode){
				$nodes[$this->getLocalNode()->distanceHexStr($cnode)] = $cnode;
				ksort($nodes, SORT_STRING);
				$nodes = array_slice($nodes, 0, $num);
			}
		}
		
		$rv = array_values($nodes);
		return $rv;
	}
	
	public function nodeFindInBuckets(Node $node){
		foreach($this->buckets as $bucketId => $bucket){
			if($onode = $bucket->nodeFind($node)){
				return $onode;
			}
		}
		
		return null;
	}
	
	public function nodeFindInBucketsByUri($uri){
		#fwrite(STDOUT, 'nodeFindInBucketsByUri'."\n");
		foreach($this->buckets as $bucketId => $bucket){
			#fwrite(STDOUT, 'nodeFindInBucketsByUri: '.$bucketId."\n");
			if($onode = $bucket->nodeFindByUri($uri)){
				#fwrite(STDOUT, 'nodeFindInBucketsByUri: '.$bucketId.', found'."\n");
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
		return $rv;
	}
	
	public function nodeFindByKeyPubFingerprint($fingerprint){
		#print __CLASS__.'->'.__FUNCTION__.': '.$fingerprint."\n";
		
		foreach($this->buckets as $bucketId => $bucket){
			#print __CLASS__.'->'.__FUNCTION__.': bucket '.$bucketId."\n";
			
			foreach($bucket->getNodes() as $cnodeId => $cnode){
				#print __CLASS__.'->'.__FUNCTION__.': node '.$cnodeId."\n";
				
				if($fingerprint == $cnode->getSslKeyPubFingerprint()){
					return $cnode;
				}
			}
		}
		
		return null;
	}
	
	public function nodeEnclose(Node $node){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$rv = $node;
		
		if( !$this->getLocalNode()->isEqual($node) ){
			$distance = $this->getLocalNode()->distance($node);
			
			# TODO: also search node by nodeFindByKeyPubFingerprint()
			
			$onode = $this->nodeFindInBuckets($node);
			if($onode){
				#print __CLASS__.'->'.__FUNCTION__.' old node: '.$onode->getIdHexStr()."\n";
				
				if( $node->getTimeLastSeen() > $onode->getTimeLastSeen() ){
					$onode->setUri($node->getUri());
					$onode->setTimeLastSeen($node->getTimeLastSeen());
					$onode->setDataChanged(true);
				}
				
				$rv = $onode;
			}
			else{
				#print __CLASS__.'->'.__FUNCTION__.' new node: '.$node->getIdHexStr()."\n";
				
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
										throw new RuntimeException('Bucket with mask '.$bmaskName.' can\'t be full.');
									}
									
									$nbucket->nodeAdd($bnode, false);
								}
								else{
									$nbucket = $this->bucketNew($bmaskName);
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
							
							$this->setDataChanged(true);
						}
						else{
							$bucket->nodeAdd($node);
							#$bucket->save();
							break;
						}
						
					}
					else{
						$nbucket = $this->bucketNew($maskName);
						$nbucket->nodeAdd($node);
						
						break;
					}
					
				}
			}
			
		}
		#else{ print __CLASS__.'->'.__FUNCTION__.': same'."\n"; }
		
		return $rv;
	}
	
}
