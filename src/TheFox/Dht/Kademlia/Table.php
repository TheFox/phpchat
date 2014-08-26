<?php

namespace TheFox\Dht\Kademlia;

use Exception;
use RuntimeException;

use TheFox\Storage\YamlStorage;

class Table extends YamlStorage{
	
	private $buckets = array();
	private $bucketsByMask = array();
	private $localNode = null;
	#private $childBucketUpper = null;
	#private $childBucketLower = null;
	private $rootBucket = null;
	
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
		
		/*$this->data['childBucketUpper'] = null;
		if($this->childBucketUpper){
			$this->data['childBucketUpper'] = $this->childBucketUpper->getFilePath();
			$this->childBucketUpper->save();
		}
		
		$this->data['childBucketLower'] = null;
		if($this->childBucketLower){
			$this->data['childBucketLower'] = $this->childBucketLower->getFilePath();
			$this->childBucketLower->save();
		}*/
		
		$this->data['rootBucket'] = null;
		if($this->rootBucket){
			$this->data['rootBucket'] = $this->rootBucket->getFilePath();
			$this->rootBucket->save();
		}
		
		$rv = parent::save();
		unset($this->data['buckets']);
		#unset($this->data['childBucketUpper']);
		#unset($this->data['childBucketLower']);
		unset($this->data['rootBucket']);
		
		return $rv;
	}
	
	public function load(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(parent::load()){
			
			if($this->data){
				if(array_key_exists('buckets', $this->data) && $this->data['buckets']){
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
				/*if(array_key_exists('childBucketUpper', $this->data) && $this->data['childBucketUpper']){
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
				}*/
				if(array_key_exists('rootBucket', $this->data) && $this->data['rootBucket']){
					$this->rootBucket = new Bucket($this->data['rootBucket']);
					$this->rootBucket->setDatadirBasePath($this->getDatadirBasePath());
					$this->rootBucket->setLocalNode($this->getLocalNode());
					$this->rootBucket->load();
				}
			}
			
			unset($this->data['buckets']);
			#unset($this->data['childBucketUpper']);
			#unset($this->data['childBucketLower']);
			unset($this->data['rootBucket']);
			
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
		return $this->nodeEnclose1($node);
	}
	
	public function nodeEnclose1(Node $node){
		fwrite(STDOUT, __FUNCTION__.''."\n");
		
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$rv = $node;
		
		if( !$this->getLocalNode()->isEqual($node) ){
			$distance = $this->getLocalNode()->distance($node);
			
			$onode = $this->nodeFindInBuckets($node);
			if($onode){
				fwrite(STDOUT, __FUNCTION__.'     old node: '.$onode->getIdHexStr()."\n");
				
				if( $node->getTimeLastSeen() > $onode->getTimeLastSeen() ){
					$onode->setUri($node->getUri());
					$onode->setTimeLastSeen($node->getTimeLastSeen());
					$onode->setDataChanged(true);
				}
				
				$rv = $onode;
			}
			else{
				fwrite(STDOUT, __FUNCTION__.'     new node: /'.$node->getIdHexStr().'/'."\n");
				
				$idLenBits = Node::ID_LEN_BITS - 1;
				$mbase = array_fill(0, Node::ID_LEN, 0);
				
				while(true){
					$idPos = 0;
					$mask = 0;
					for($bits = $idLenBits; $bits >= 0; $bits--){
						$idPos = Node::ID_LEN - floor($bits / 8) - 1;
						$mask = $mbase[$idPos] | 1 << (7 - (Node::ID_LEN_BITS - $idPos * 8 - $bits - 1));
						#fwrite(STDOUT, __FUNCTION__.'         mask: /'.$idPos.'/ /'.$mask.'/'."\n");
						if( ($distance[$idPos] & $mask) == $mask ){
							break;
						}
					}
					
					$maskName = ((string)(Node::ID_LEN - $idPos - 1)).'_'.((string)$mask);
					fwrite(STDOUT, __FUNCTION__.'         pos: /'.$idPos.'/ /'.$mask.'/ /'.$maskName.'/'."\n");
					
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
								fwrite(STDOUT, __FUNCTION__.'         move node from old bucket to new'."\n");
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
								fwrite(STDOUT, __FUNCTION__.'         remove node from old bucket'."\n");
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
	
	private function bucketForNode(Node $node){
		fwrite(STDOUT, __FUNCTION__.''."\n");
		
		$distance = $this->getLocalNode()->distance($node);
		#ve($distance);
		
		/*
		foreach($distance as $distPos => $distItem){
			#fwrite(STDOUT, __FUNCTION__.': /'.$distItem.'/ => /'.$distItem.'/'."\n");
			
			if($distItem){
				fwrite(STDOUT, '    ');
				for($bit = 2; $bit >= 0; $bit--){
					fwrite(STDOUT, ($distItem & (1 << $bit)) > 0 ? '1' : '0');
					#fwrite(STDOUT, (1 << $bit).'('.($distItem).','.($distItem & 1 << $bit).') ');
				}
				fwrite(STDOUT, "\n");
			}
		}
		*/
		/*
		fwrite(STDOUT, '    ');
		for($bit = 2; $bit >= 0; $bit--){
			fwrite(STDOUT, ($distance[15] & (1 << $bit)) > 0 ? '1' : '0');
		}
		fwrite(STDOUT, "\n");
		
		
		
		$idLenBits = Node::ID_LEN_BITS - 1;
		$idLenBits = 2;
		$mbase = array_fill(0, Node::ID_LEN, 0);
		$idPos = 0;
		$mask = 0;
		for($bits = $idLenBits; $bits >= 0; $bits--){
			$idPos = Node::ID_LEN - floor($bits / 8) - 1;
			$mask = $mbase[$idPos] | 1 << (7 - (Node::ID_LEN_BITS - $idPos * 8 - $bits - 1));
			#fwrite(STDOUT, __FUNCTION__.'         mask: /'.$idPos.'/ /'.$mask.'/    '."\r");
			#usleep(100000);
			if( ($distance[$idPos] & $mask) == $mask ){
				break;
			}
		}
		fwrite(STDOUT, __FUNCTION__.'         mask: /'.$mask.'/    '."\n");
		fwrite(STDOUT, "\n");
		*/
		
		
		
		return $bucket;
	}
	
	public function nodeEnclose2(Node $node){
		#fwrite(STDOUT, __FUNCTION__.''."\n");
		
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		if(!$this->rootBucket){
			$filePath = null;
			if($this->getDatadirBasePath()){
				#$filePath = $this->getDatadirBasePath().'/bucket_root_'.time().'_'.mt_rand(1000, 9999).'.yml';
				$filePath = $this->getDatadirBasePath().'/bucket_root.yml';
				if(file_exists($filePath)){
					// This should never happen.
					throw new RuntimeException('path for bucket alread exists: '.$filePath);
				}
			}
			
			$bucket = new Bucket($filePath);
			$bucket->setDatadirBasePath($this->getDatadirBasePath());
			$bucket->setLocalNode($this->getLocalNode());
			$this->rootBucket = $bucket;
			
			$this->setDataChanged(true);
		}
		
		#$returnNode = $this->rootBucket->nodeEnclose($node);
		$returnNode = $this->rootBucket->nodeEnclose($node, false);
		
		return $returnNode;
	}
	
}
