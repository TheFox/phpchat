<?php

namespace TheFox\Dht\Kademlia;

use RuntimeException;

use Zend\Uri\UriFactory;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Storage\YamlStorage;
use TheFox\Utilities\Hex;
use TheFox\Utilities\Base58;

class Node extends YamlStorage{
	
	const ID_LEN_BYTE = 16;
	const ID_LEN_BIT = 128;
	const SSL_KEY_LEN_MIN = 4096;
	
	private $id = array();
	private $uri = null;
	private $sslKeyPub = null;
	private $bucket = null;
	
	public function __construct($filePath = null){
		parent::__construct($filePath);
		
		$this->uri = UriFactory::factory('tcp://');
		#ve($this->uri);
		
		$this->data['id'] = '00000000-0000-4000-8000-000000000000';
		$this->data['uri'] = '';
		$this->data['sslKeyPubFingerprint'] = '';
		$this->data['sslKeyPubStatus'] = 'U';
		$this->data['distance'] = null;
		$this->data['connectionsOutboundSucceed'] = 0;
		$this->data['connectionsOutboundAttempts'] = 0;
		$this->data['connectionsInboundSucceed'] = 0;
		#$this->data['connectionsInboundAttempts'] = 0;
		$this->data['bridgeServer'] = false;
		$this->data['bridgeClient'] = false;
		$this->data['bridgeDst'] = array();
		$this->data['timeCreated'] = time();
		$this->data['timeLastSeen'] = 0;
		
		$this->setIdHexStr($this->data['id']);
	}
	
	public function __sleep(){
		return array('data', 'id', 'uri', 'sslKeyPub');
	}
	
	public function __toString(){
		if($this->getIdHexStr() != '00000000-0000-4000-8000-000000000000'){
			return __CLASS__.'->{ID:'.$this->getIdHexStr().'}';
		}
		if((string)$this->getUri()){
			return __CLASS__.'->{URI:'.$this->getUri().'}';
		}
	}
	
	public function save(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->data['uri'] = (string)$this->uri;
		$this->data['sslKeyPub'] = base64_encode($this->sslKeyPub);
		return parent::save();
	}
	
	public function load(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#fwrite(STDOUT, 'load node'."\n");
		
		if(parent::load()){
			$this->setIdHexStr($this->data['id']);
			
			if($this->data){
				if(array_key_exists('sslKeyPub', $this->data)){
					if($this->data['sslKeyPub']){
						$this->setSslKeyPub(base64_decode($this->data['sslKeyPub']));
					}
					unset($this->data['sslKeyPub']);
				}
				
				if(array_key_exists('uri', $this->data)){
					#fwrite(STDOUT, 'load node: uri /'.$this->data['uri'].'/'."\n");
					if($this->data['uri']){
						$this->setUri($this->data['uri']);
					}
					unset($this->data['uri']);
				}
			}
			
			return true;
		}
		return false;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function setIdHexStr($id){
		#fwrite(STDOUT, __FUNCTION__.''."\n");
		#fwrite(STDOUT, __FUNCTION__.': '.$id."\n");
		#ve(isset($id) ? $id : null);
		
		if($id){
			$id = strtolower($id);
			$this->id = array_fill(0, static::ID_LEN_BYTE, 0);
			
			if(Uuid::isValid($id)){
				#print __CLASS__.'->'.__FUNCTION__.': check valid UUID'."\n";
				$this->data['id'] = $id;
				
				$id = str_replace('-', '', $id);
				for($idPos = 0; $idPos < static::ID_LEN_BYTE; $idPos++){
					$this->id[$idPos] = hexdec(substr($id, 0, 2));
					$id = substr($id, 2);
				}
			}
		}
		#ve($this->id);
		#else{ print __CLASS__.'->'.__FUNCTION__.': check valid UUID FAILED: '.$id."\n"; }
	}
	
	public function getIdHexStr(){
		return $this->data['id'];
	}
	
	public function getIdBitStr(){
		$rv = '';
		for($idPos = 0; $idPos < static::ID_LEN_BYTE; $idPos++){
			for($bits = 7; $bits >= 0; $bits--){
				$rv .= $this->id[$idPos] & (1 << $bits) ? '1' : '0';
			}
			#$rv .= ' ';
		}
		return $rv;
	}
	
	public function setUri($uri){
		if(is_string($uri)){
			if($uri){
				$uri = UriFactory::factory($uri);
			}
			else{
				$uri = UriFactory::factory('tcp://');
			}
		}
		$this->uri = $uri;
	}
	
	public function getUri(){
		return $this->uri;
	}
	
	public function setSslKeyPub($strKeyPub, $force = false){
		if(!$this->sslKeyPub || $force){
			$sslPubKey = openssl_pkey_get_public($strKeyPub);
			if($sslPubKey !== false){
				$sslPubKeyDetails = openssl_pkey_get_details($sslPubKey);
				
				if($sslPubKeyDetails['bits'] >= static::SSL_KEY_LEN_MIN){
					$this->sslKeyPub = $sslPubKeyDetails['key'];
					$this->setSslKeyPubFingerprint(static::genSslKeyFingerprint($strKeyPub));
					
					return true;
				}
			}
			else{
				throw new RuntimeException('SSL: openssl_pkey_get_public failed.', 1);
			}
		}
		
		return false;
	}
	
	public function getSslKeyPub(){
		return $this->sslKeyPub;
	}
	
	public function setSslKeyPubFingerprint($sslKeyPubFingerprint){
		$this->data['sslKeyPubFingerprint'] = $sslKeyPubFingerprint;
	}
	
	public function getSslKeyPubFingerprint(){
		return $this->data['sslKeyPubFingerprint'];
	}
	
	public function setSslKeyPubStatus($sslKeyPubStatus){
		// U = unconfirmed
		// C = confirmed by ID
		
		if($this->data['sslKeyPubStatus'] != 'C'){
			$this->data['sslKeyPubStatus'] = $sslKeyPubStatus;
		}
	}
	
	public function getSslKeyPubStatus(){
		return $this->data['sslKeyPubStatus'];
	}
	
	public static function genSslKeyFingerprint($key){
		$key = sslKeyPubClean($key);
		
		$keyBin = base64_decode($key);
		$keyBinSha512Bin = hash('sha512', $keyBin, true);
		$fingerprintHex = hash('ripemd160', $keyBinSha512Bin, false);
		$fingerprintBin = hash('ripemd160', $keyBinSha512Bin, true);
		
		$checksumHex = hash('sha512', hash('sha512', $fingerprintBin, true));
		$checksumHex = substr($checksumHex, 0, 8); // 4 Bytes
		
		$num = Hex::decode($fingerprintHex.$checksumHex);
		$numBase58 = Base58::encode($num);
		
		$rv = 'FC_'.$numBase58;
		
		return $rv;
	}
	
	public static function sslKeyPubFingerprintVerify($fingerprint){
		if(substr($fingerprint, 0, 3) == 'FC_'){
			$fingerprint = substr($fingerprint, 3);
			
			$fingerprintNum = Base58::decode($fingerprint);
			
			$fingerprintHex = Hex::encode($fingerprintNum);
			$fingerprintHex = str_repeat('0', strlen($fingerprintHex) % 2).$fingerprintHex;
			
			$checksumHex = substr($fingerprintHex, -8);
			
			$fingerprintHex = substr($fingerprintHex, 0, -8);
			
			$fingerprintBin = Hex::dataDecode($fingerprintHex);
			$fingerprintBinChecksumHex = substr(hash('sha512', hash('sha512', $fingerprintBin, true)), 0, 8);
			
			return $checksumHex == $fingerprintBinChecksumHex;
		}
		
		return false;
	}
	
	public static function genIdHexStr($key){
		$key = sslKeyPubClean($key);
		
		$keyBin = base64_decode($key);
		
		try{
			$id = (string)Uuid::uuid5(Uuid::NAMESPACE_X500, $keyBin);
			return $id;
		}
		catch(UnsatisfiedDependencyException $e){
			return null;
		}
	}
	
	public function setDistance($distance){
		$this->data['distance'] = $distance;
		#$this->distance = $distance;
	}
	
	public function getDistance(){
		return $this->data['distance'];
		#return $this->distance;
	}
	
	public function setConnectionsOutboundSucceed($connectionsOutboundSucceed){
		$this->data['connectionsOutboundSucceed'] = $connectionsOutboundSucceed;
	}
	
	public function getConnectionsOutboundSucceed(){
		return $this->data['connectionsOutboundSucceed'];
	}
	
	public function incConnectionsOutboundSucceed($inc = 1){
		$this->data['connectionsOutboundSucceed'] += $inc;
		$this->setDataChanged(true);
	}
	
	public function setConnectionsOutboundAttempts($connectionsOutboundAttempts){
		$this->data['connectionsOutboundAttempts'] = $connectionsOutboundAttempts;
	}
	
	public function getConnectionsOutboundAttempts(){
		return $this->data['connectionsOutboundAttempts'];
	}
	
	public function incConnectionsOutboundAttempts($inc = 1){
		$this->data['connectionsOutboundAttempts'] += $inc;
		$this->setDataChanged(true);
	}
	
	public function setConnectionsInboundSucceed($connectionsInboundSucceed){
		$this->data['connectionsInboundSucceed'] = $connectionsInboundSucceed;
	}
	
	public function getConnectionsInboundSucceed(){
		return $this->data['connectionsInboundSucceed'];
	}
	
	public function incConnectionsInboundSucceed($inc = 1){
		$this->data['connectionsInboundSucceed'] += $inc;
		$this->setDataChanged(true);
	}
	
	/*public function setConnectionsInboundAttempts($connectionsInboundAttempts){
		$this->data['connectionsInboundAttempts'] = $connectionsInboundAttempts;
	}
	
	public function getConnectionsInboundAttempts(){
		return $this->data['connectionsInboundAttempts'];
	}
	
	public function incConnectionsInboundAttempts($inc = 1){
		$this->data['connectionsInboundAttempts'] += $inc;
		$this->setDataChanged(true);
	}*/
	
	public function setBridgeServer($bridgeServer){
		$this->data['bridgeServer'] = (bool)$bridgeServer;
		$this->setDataChanged(true);
	}
	
	public function getBridgeServer(){
		return (bool)$this->data['bridgeServer'];
	}
	
	public function setBridgeClient($bridgeClient){
		$this->data['bridgeClient'] = (bool)$bridgeClient;
		$this->setDataChanged(true);
	}
	
	public function getBridgeClient(){
		return (bool)$this->data['bridgeClient'];
	}
	
	public function addBridgeDst($bridgeDst){
		$this->data['bridgeDst'][] = $bridgeDst;
		$this->data['bridgeDst'] = array_unique($this->data['bridgeDst']);
		$this->setDataChanged(true);
	}
	
	public function getBridgeDst(){
		return $this->data['bridgeDst'];
	}
	
	public function setTimeCreated($timeCreated){
		$this->data['timeCreated'] = $timeCreated;
	}
	
	public function getTimeCreated(){
		return $this->data['timeCreated'];
	}
	
	public function setTimeLastSeen($timeLastSeen){
		$this->data['timeLastSeen'] = $timeLastSeen;
	}
	
	public function getTimeLastSeen(){
		return $this->data['timeLastSeen'];
	}
	
	public function setBucket(Bucket $bucket){
		$this->bucket = $bucket;
	}
	
	public function getBucket(){
		return $this->bucket;
	}
	
	public function distance(Node $node){
		#fwrite(STDOUT, __FUNCTION__.''."\n");
		$rv = array_fill(0, static::ID_LEN_BYTE, 0);
		#ve($rv);
		
		if($node && $this !== $node){
			$thisId = $this->getId();
			$nodeId = $node->getId();
			
			#ve($thisId);
			#ve($nodeId);
			
			for($idPos = 0; $idPos < static::ID_LEN_BYTE; $idPos++){
				$rv[$idPos] = $thisId[$idPos] ^ $nodeId[$idPos];
				#fwrite(STDOUT, __FUNCTION__.'     pos: '.$idPos.' -> '.$rv[$idPos]."\n");
			}
		}
		
		return $rv;
	}
	
	public function distanceBitStr(Node $node){
		$distance = $this->distance($node);
		
		$rv = '';
		for($idPos = 0; $idPos < static::ID_LEN_BYTE; $idPos++){
			#fwrite(STDOUT, __FUNCTION__.' pos: '.$idPos."\n");
			for($bits = 7; $bits >= 0; $bits--){
				#fwrite(STDOUT, __FUNCTION__.'     bit: '.$bits.' -> '.(1 << $bits)."\n");
				$rv .= $distance[$idPos] & (1 << $bits) ? '1' : '0';
			}
			#$rv .= ' ';
		}
		return $rv;
	}
	
	public function distanceHexStr(Node $node){
		$distance = $this->distance($node);
		
		return sprintf('%02x%02x%02x%02x-%02x%02x-%02x%02x-%02x%02x-%02x%02x%02x%02x%02x%02x',
			$distance[0], $distance[1], $distance[2], $distance[3],
			$distance[4], $distance[5], $distance[6], $distance[7],
			$distance[8], $distance[9], $distance[10], $distance[11],
			$distance[12], $distance[13], $distance[14], $distance[15]);
	}
	
	public function isEqual(Node $node){
		return $this->getIdHexStr() == $node->getIdHexStr();
	}
	
	public static function idMinHexStr($hex_a, $hex_b){
		if($hex_a == $hex_b){
			return $hex_a;
		}
		
		$ar = array();
		$ar[] = $hex_a;
		$ar[] = $hex_b;
		sort($ar, SORT_STRING);
		
		return array_shift($ar);
	}
	
	public function update(Node $node){
		if($node->getTimeLastSeen() > $this->getTimeLastSeen()){
			$this->setUri($node->getUri());
			#$this->setConnectionsOutboundSucceed($node->getConnectionsOutboundSucceed());
			#$this->setConnectionsOutboundAttempts($node->getConnectionsOutboundAttempts());
			#$this->setConnectionsInboundSucceed($node->getConnectionsInboundSucceed());
			#$this->setConnectionsInboundAttempts($node->getConnectionsInboundAttempts());
			$this->setTimeLastSeen($node->getTimeLastSeen());
			$this->setDataChanged(true);
		}
	}
	
}
