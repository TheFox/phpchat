<?php

namespace TheFox\Dht\Kademlia;

use TheFox\Yaml\YamlStorage;
use TheFox\Utilities\Hex;
use TheFox\Utilities\Base58;

class Node extends YamlStorage{
	
	const ID_LEN = 16;
	const ID_LEN_BITS = 128;
	
	private $id = array();
	private $bucket = null;
	
	public function __construct($filePath = null){
		parent::__construct($filePath);
		
		$this->data['id'] = '00000000-0000-4000-8000-000000000000';
		$this->data['type'] = 'tcp';
		$this->data['ip'] = '';
		$this->data['port'] = 0;
		$this->data['sslKeyPub'] = '';
		$this->data['sslKeyPubFingerprint'] = '';
		$this->data['timeCreated'] = time();
		$this->data['timeLastSeen'] = 0;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function setIdHexStr($id){
		#print __CLASS__.'->'.__FUNCTION__.": $id\n";
		
		$this->id = array_fill(0, static::ID_LEN, 0);
		
		if(strIsUuid($id)){
			$this->data['id'] = $id;
			
			$id = str_replace('-', '', $id);
			for($idPos = 0; $idPos < static::ID_LEN; $idPos++){
				$this->id[$idPos] = hexdec(substr($id, 0, 2));
				$id = substr($id, 2);
			}
		}
	}
	
	public function getIdHexStr(){
		return $this->data['id'];
	}
	
	public function setIp($ip){
		$this->data['ip'] = $ip;
	}
	
	public function getIp(){
		return $this->data['ip'];
	}
	
	public function setPort($port){
		$this->data['port'] = (int)$port;
	}
	
	public function getPort(){
		return $this->data['port'];
	}
	
	public function setSslKeyPub($sslKeyPub){
		$this->data['sslKeyPub'] = $sslKeyPub;
		$this->setSslKeyPubFingerprint(static::genSslKeyFingerprint($this->sslKeyPub));
	}
	
	public function getSslKeyPub(){
		return $this->data['sslKeyPub'];
	}
	
	public function setSslKeyPubFingerprint($sslKeyPubFingerprint){
		$this->data['sslKeyPubFingerprint'] = $sslKeyPubFingerprint;
	}
	
	public function getSslKeyPubFingerprint(){
		return $this->data['sslKeyPubFingerprint'];
	}
	
	public static function genSslKeyFingerprint($key){
		$key = str_replace("\r", '', $key);
		$key = str_replace("-----BEGIN PUBLIC KEY-----\n", '', $key);
		$key = str_replace("\n-----END PUBLIC KEY-----\n", '', $key);
		
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
		}
		return $rv;
	}
	
	public function distanceHexStr(Node $node){
		$distance = $this->distance($node);
		
		return sprintf('%02x%02x%02x%02x-%02x%02x-%02x%02x-%02x%02x-%02x%02x%02x%02x%02x%02x', $distance[0], $distance[1], $distance[2], $distance[3], $distance[4], $distance[5], $distance[6], $distance[7], $distance[8], $distance[9], $distance[10], $distance[11], $distance[12], $distance[13], $distance[14], $distance[15]);
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
	
}
