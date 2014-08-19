<?php

namespace TheFox\Dht\Kademlia;

use RuntimeException;

use Zend\Uri\UriFactory;

use TheFox\Storage\YamlStorage;
use TheFox\Utilities\Hex;
use TheFox\Utilities\Base58;

class Node extends YamlStorage{
	
	const ID_LEN = 16;
	const ID_LEN_BITS = 128;
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
		$this->data['timeCreated'] = time();
		$this->data['timeLastSeen'] = 0;
		
		$this->setIdHexStr($this->data['id']);
	}
	
	public function __sleep(){
		return array('data', 'id', 'uri', 'sslKeyPub');
	}
	
	public function __toString(){
		return __CLASS__.'->{'.$this->getIdHexStr().'}';
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
			
			#ve($this->data);
			
			if(isset($this->data['sslKeyPub'])){
				if($this->data['sslKeyPub']){
					$this->setSslKeyPub(base64_decode($this->data['sslKeyPub']));
				}
				unset($this->data['sslKeyPub']);
			}
			
			if(isset($this->data['uri'])){
				#fwrite(STDOUT, 'load node: uri /'.$this->data['uri'].'/'."\n");
				$this->setUri($this->data['uri']);
				unset($this->data['uri']);
			}
			
			return true;
		}
		return false;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function setIdHexStr($id){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		$id = strtolower($id);
		$this->id = array_fill(0, static::ID_LEN, 0);
		
		if(strIsUuid($id)){
			#print __CLASS__.'->'.__FUNCTION__.': strIsUuid'."\n";
			$this->data['id'] = $id;
			
			$id = str_replace('-', '', $id);
			for($idPos = 0; $idPos < static::ID_LEN; $idPos++){
				$this->id[$idPos] = hexdec(substr($id, 0, 2));
				$id = substr($id, 2);
			}
		}
		#else{ print __CLASS__.'->'.__FUNCTION__.': strIsUuid FAILED: '.$id."\n"; }
	}
	
	public function getIdHexStr(){
		return $this->data['id'];
	}
	
	public function getIdBitStr(){
		$rv = '';
		for($idPos = 0; $idPos < static::ID_LEN; $idPos++){
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
	
	public static function genSslKeyFingerprint($key){
		$key = str_replace("\r", '', $key);
		$key = str_replace("\n", '', $key);
		#$key = str_replace("-----BEGIN PUBLIC KEY-----\n", '', $key);
		$key = str_replace('-----BEGIN PUBLIC KEY-----', '', $key);
		#$key = str_replace("\n-----END PUBLIC KEY-----\n", '', $key);
		$key = str_replace('-----END PUBLIC KEY-----', '', $key);
		#ve($key);
		
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
		$rv = array_fill(0, static::ID_LEN, 0);
		
		if($node && $this !== $node){
			$nodeId = $node->getId();
			$thisId = $this->getId();
			
			for($idPos = 0; $idPos < static::ID_LEN; $idPos++){
				$rv[$idPos] = $thisId[$idPos] ^ $nodeId[$idPos];
			}
		}
		
		return $rv;
	}
	
	public function distanceBitStr(Node $node){
		$distance = $this->distance($node);
		
		$rv = '';
		for($idPos = 0; $idPos < static::ID_LEN; $idPos++){
			for($bits = 7; $bits >= 0; $bits--){
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
	
	public function isInTable(){
		return $this->getBucket() !== null;
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
	
}
