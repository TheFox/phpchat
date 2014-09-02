<?php

namespace TheFox\Dht\Simple;

use Exception;
use RuntimeException;

use Symfony\Component\Filesystem\Filesystem;

use TheFox\Storage\YamlStorage;
use TheFox\Dht\Kademlia\Node;

class Table extends YamlStorage{
	
	static $NODES_MAX = 5000;
	static $NODE_TTL = 1209600; // 14 days
	static $NODE_CONNECTIONS_OUTBOUND_ATTEMPTS_MAX = 100;
	
	private $localNode = null;
	private $nodes = array();
	
	public function __construct($filePath = null){
		parent::__construct($filePath);
		
		$this->data['timeCreated'] = time();
	}
	
	public function __sleep(){
		return array(
			'data',
			'localNode',
			'nodes',
		);
	}
	
	public function save(){
		$this->data['nodes'] = array();
		foreach($this->nodes as $nodeId => $node){
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
			
			if($this->data){
				if(array_key_exists('nodes', $this->data) && $this->data['nodes']){
					foreach($this->data['nodes'] as $nodeId => $nodeAr){
						if(file_exists($nodeAr['path'])){
							$node = new Node($nodeAr['path']);
							$node->setDatadirBasePath($this->getDatadirBasePath());
							
							if($node->load()){
								$this->nodes[$nodeId] = $node;
							}
						}
					}
				}
			}
			
			unset($this->data['nodes']);
			
			return true;
		}
		
		return false;
	}
	
	public function setLocalNode(Node $node){
		$this->localNode = $node;
	}
	
	public function getLocalNode(){
		return $this->localNode;
	}
	
	public function getNodes(){
		return $this->nodes;
	}
	
	public function getNodesNum(){
		return count($this->getNodes());
	}
	
	public function getNodesClosest($num = 20){
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$nodes = $this->nodes;
		$nodes = array_slice($nodes, 0, $num);
		ksort($nodes, SORT_STRING);
		
		$rv = array_values($nodes);
		return $rv;
	}
	
	public function nodeFind(Node $node){
		if(isset($this->nodes[$node->getIdHexStr()])){
			return $this->nodes[$node->getIdHexStr()];
		}
		
		return null;
	}
	
	public function nodeFindByUri($uri){
		$uri = (string)$uri;
		if($uri){
			foreach($this->nodes as $nodeId => $node){
				if((string)$node->getUri() == $uri){
					return $node;
				}
			}
		}
		
		return null;
	}
	
	public function nodesFindByUri($uri){
		$rv = array();
		$uri = (string)$uri;
		if($uri){
			foreach($this->nodes as $nodeId => $node){
				if((string)$node->getUri() == $uri){
					$rv[] = $node;
				}
			}
		}
		
		return $rv;
	}
	
	public function nodeFindByKeyPubFingerprint($fingerprint){
		foreach($this->nodes as $nodeId => $node){
			if($node->getSslKeyPubFingerprint() == $fingerprint){
				return $node;
			}
		}
		
		return null;
	}
	
	public function nodeFindClosest(Node $node, $num = 8){
		$nodes = array();
		foreach($this->nodes as $onodeId => $onode){
			if(!$onode->isEqual($node)){
				$nodes[$onode->distanceHexStr($node)] = $onode;
				ksort($nodes, SORT_STRING);
				$nodes = array_slice($nodes, 0, $num);
			}
		}
		$rv = array_values($nodes);
		return $rv;
	}
	
	public function nodeEnclose(Node $node){
		#fwrite(STDOUT, __FUNCTION__.': '.$node."\n");
		
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$returnNode = $node;
		
		if(! $this->getLocalNode()->isEqual($node)){
			#$found = false;
			
			$onode = $this->nodeFind($node);
			if($onode){
				$onode->update($node);
				$returnNode = $onode;
			}
			else{
				$nodeId = $node->getIdHexStr();
				if($nodeId != '00000000-0000-4000-8000-000000000000'){
					/*$onode = $this->nodeFindByUri($node->getUri());
					if($onode){
						$onode->setUri('');
						$onode->setDataChanged(true);
					}*/
					
					foreach($this->nodesFindByUri($node->getUri()) as $onodeId => $onode){
						$onode->setUri('');
						$onode->setDataChanged(true);
					}
					
					$filePath = null;
					if($this->getDatadirBasePath()){
						#$filePath = $this->getDatadirBasePath().'/node_'.substr($nodeId, -5).'.yml';
						$filePath = $this->getDatadirBasePath().'/node_'.$nodeId.'.yml';
					}
					if(!$node->getFilePath()){
						$node->setFilePath($filePath);
					}
					$node->setDatadirBasePath($this->getDatadirBasePath());
					$node->setDataChanged(true);
					
					$this->nodes[$nodeId] = $node;
					$this->setDataChanged(true);
					
					$this->nodesSort();
				}
			}
		}
		
		return $returnNode;
	}
	
	public function nodesClean(){
		$this->nodesSort();
		
		foreach($this->nodes as $nodeId => $node){
			#fwrite(STDOUT, 'node delete: '.(time() - $node->getTimeCreated()).' '.(time() - $node->getTimeLastSeen()).' '.PHP_EOL);
			if(
				$node->getTimeCreated() <= time() - static::$NODE_TTL && !$node->getTimeLastSeen()
				|| $node->getTimeLastSeen() && $node->getTimeLastSeen() <= time() - static::$NODE_TTL
				|| $node->getConnectionsOutboundAttempts() >= static::$NODE_CONNECTIONS_OUTBOUND_ATTEMPTS_MAX
					&& $node->getConnectionsOutboundSucceed() == 0
					&& $node->getConnectionsInboundSucceed() == 0
			){
				$this->nodeRemove($node);
			}
		}
		
		if(count($this->nodes) > static::$NODES_MAX){
			$nodesToDelete = array_slice($this->nodes, static::$NODES_MAX);
			
			foreach($nodesToDelete as $nodeId => $node){
				$this->nodeRemove($node);
			}
		}
	}
	
	private function nodeRemove(Node $node){
		$filesystem = new Filesystem();
		$nodeId = $node->getIdHexStr();
		
		if(isset($this->nodes[$nodeId])){
			unset($this->nodes[$nodeId]);
		}
		
		#fwrite(STDOUT, 'node delete: '.$nodeId.' /'.$node->getFilePath().'/'.PHP_EOL);
		$filesystem->remove($node->getFilePath());
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
	}
	
}
