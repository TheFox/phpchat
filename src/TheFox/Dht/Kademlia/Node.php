<?php

namespace TheFox\Dht\Kademlia;

use TheFox\Yaml\YamlStorage;
use TheFox\Utilities\Hex;
use TheFox\Utilities\Base58;

class Node extends YamlStorage{
	
	const ID_LEN = 16;
	const ID_LEN_BITS = 128;
	
	private $id;
	private $type;
	private $ip;
	private $port;
	private $sslKeyPub;
	private $sslKeyPubFingerprint;
	#private $sslVerified; # TODO: verify a node by asking 3 other nodes. only if these other 3 connected to the node
	private $timeCreated;
	private $timeLastSeen;
	private $bucket;
	
	public function __construct($datadirBasePath = null){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->setDatadirBasePath($datadirBasePath);
		if($this->getDatadirBasePath()){
			$this->setFilePath($this->getDatadirBasePath().'/node.yml');
		}
		
		$this->setType('tcp');
		$this->setIdHexStr('00000000-0000-4000-8000-000000000000');
		$this->setTimeCreated(time());
		$this->setTimeLastSeen(0);
	}
	
	public function save(){
		#print __CLASS__.'->'.__FUNCTION__.": begin\n";
		
		$this->data = new StackableArray();
		$this->data['id'] = $this->getIdHexStr();
		$this->data['type'] = $this->getType();
		$this->data['ip'] = $this->getIp();
		$this->data['sslKeyPub'] = $this->getSslKeyPub();
		$this->data['sslKeyPubFingerprint'] = $this->getSslKeyPubFingerprint();
		#$this->data['sslVerified'] = $this->getSslVerified();
		$this->data['port'] = $this->getPort();
		$this->data['timeCreated'] = $this->getTimeCreated();
		$this->data['timeLastSeen'] = $this->getTimeLastSeen();
		
		$rv = parent::save();
		unset($this->data);
		
		return $rv;
	}
	
	public function load(){
		if(parent::load()){
			
			$this->setType($this->data['type']);
			$this->setIdHexStr($this->data['id']);
			$this->setIp($this->data['ip']);
			$this->setPort($this->data['port']);
			$this->setSslKeyPub($this->data['sslKeyPub']);
			$this->setSslKeyPubFingerprint($this->data['sslKeyPubFingerprint']);
			$this->setSslVerified($this->data['sslVerified']);
			$this->setTimeCreated($this->data['timeCreated']);
			$this->setTimeLastSeen($this->data['timeLastSeen']);
			
			unset($this->data);
			
			return true;
		}
		
		return false;
	}
	
	public function setType($type){
		$this->type = $type;
	}
	
	public function getType(){
		return $this->type;
	}
	
	public function getId(){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		return $this->id;
	}
	
	public function getIdBitStr(){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		
		$rv = '';
		for($idPos = 0; $idPos < Node::ID_LEN; $idPos++){
			for($bits = 7; $bits >= 0; $bits--){
				$rv .= $this->id[$idPos] & (1 << $bits) ? '1' : '0';
			}
		}
		return $rv;
	}
	
	public function setIdHexStr($id){
		#print __CLASS__.'->'.__FUNCTION__.": $id\n";
		
		$this->id = new StackableArray();
		$this->id[] = 0; $this->id[] = 0; $this->id[] = 0; $this->id[] = 0;
		$this->id[] = 0; $this->id[] = 0; $this->id[] = 0; $this->id[] = 0;
		$this->id[] = 0; $this->id[] = 0; $this->id[] = 0; $this->id[] = 0;
		$this->id[] = 0; $this->id[] = 0; $this->id[] = 0; $this->id[] = 0;
		
		
		if(strIsUuid($id)){
			$id = str_replace('-', '', $id);
			for($idPos = 0; $idPos < Node::ID_LEN; $idPos++){
				$this->id[$idPos] = hexdec(substr($id, 0, 2));
				$id = substr($id, 2);
			}
		}
	}
	
	public function getIdHexStr(){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		
		return sprintf('%02x%02x%02x%02x-%02x%02x-%02x%02x-%02x%02x-%02x%02x%02x%02x%02x%02x', $this->id[0], $this->id[1], $this->id[2], $this->id[3], $this->id[4], $this->id[5], $this->id[6], $this->id[7], $this->id[8], $this->id[9], $this->id[10], $this->id[11], $this->id[12], $this->id[13], $this->id[14], $this->id[15]);
	}
	
	public function setIp($ip, $port = null){
		#print __CLASS__.'->'.__FUNCTION__.": '$ip', '$port'\n";
		
		if($ip){
			$this->ip = $ip;
		}
		if($port){
			$this->setPort($port);
		}
	}
	
	public function getIp(){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		return $this->ip;
	}
	
	public function setPort($port){
		#print __CLASS__.'->'.__FUNCTION__.": '$port'\n";
		$this->port = (int)$port;
	}
	
	public function getPort(){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		return $this->port;
	}
	
	public function setSslKeyPub($sslKeyPub){
		#print __CLASS__.'->'.__FUNCTION__.': '.$sslKeyPub."\n";
		$this->sslKeyPub = $sslKeyPub;
		$this->setSslKeyPubFingerprint(static::genSslKeyFingerprint($this->sslKeyPub));
	}
	
	public function getSslKeyPub(){
		return $this->sslKeyPub;
	}
	
	/*public function getSslKeyPubBase64(){
		return base64_encode($this->sslKeyPub);
	}*/
	
	public function setSslKeyPubFingerprint($sslKeyPubFingerprint){
		$this->sslKeyPubFingerprint = $sslKeyPubFingerprint;
	}
	
	public function getSslKeyPubFingerprint(){
		#print __CLASS__.'->'.__FUNCTION__.': '.$this->getIdHexStr().', "'.$this->sslKeyPubFingerprint.'"'."\n";
		#ve($this->id);
		return $this->sslKeyPubFingerprint;
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
		
		#print "num: $num (".strlen($num).")\n";
		#print "numBase58: $numBase58 (".strlen($numBase58).")\n";
		
		$rv = 'FC_'.$numBase58;
		
		return $rv;
	}
	
	public static function sslKeyPubFingerprintVerify($fingerprint){
		if(substr($fingerprint, 0, 3) == 'FC_'){
			$fingerprint = substr($fingerprint, 3);
			
			$fingerprintNum = Base58::decode($fingerprint);
			#print "fingerprintNum: $fingerprintNum (".strlen($fingerprintNum).")\n";
			
			$fingerprintHex = Hex::encode($fingerprintNum);
			$fingerprintHex = str_repeat('0', strlen($fingerprintHex) % 2).$fingerprintHex;
			#print "fingerprintHex: $fingerprintHex (".strlen($fingerprintHex).")\n";
			
			$checksumHex = substr($fingerprintHex, -8);
			#print "checksumHex: $checksumHex (".strlen($checksumHex).")\n";
			
			$fingerprintHex = substr($fingerprintHex, 0, -8);
			
			#print "fingerprintHex: $fingerprintHex (".strlen($fingerprintHex).")\n";
			
			$fingerprintBin = Hex::dataDecode($fingerprintHex);
			$fingerprintBinChecksumHex = substr(hash('sha512', hash('sha512', $fingerprintBin, true)), 0, 8);
			#print "fingerprintBinChecksumHex: $fingerprintBinChecksumHex (".strlen($fingerprintBinChecksumHex).")\n";
			
			return $checksumHex == $fingerprintBinChecksumHex;
		}
		
		return false;
	}
	
	/*public function setSslVerified($sslVerified){
		$this->sslVerified = $sslVerified;
	}
	
	public function getSslVerified(){
		return $this->sslVerified;
	}*/
	
	public function setTimeCreated($timeCreated){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		$this->timeCreated = $timeCreated;
	}
	
	public function getTimeCreated(){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		return $this->timeCreated;
	}
	
	public function setTimeLastSeen($timeLastSeen){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		$this->timeLastSeen = $timeLastSeen;
	}
	
	public function getTimeLastSeen(){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		return $this->timeLastSeen;
	}
	
	public function setBucket(Bucket $bucket){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		$this->bucket = $bucket;
	}
	
	public function getBucket(){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		return $this->bucket;
	}
	
	public function distance(Node $node){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		
		$rv = new StackableArray();
		$rv[] = 0; $rv[] = 0; $rv[] = 0; $rv[] = 0;
		$rv[] = 0; $rv[] = 0; $rv[] = 0; $rv[] = 0;
		$rv[] = 0; $rv[] = 0; $rv[] = 0; $rv[] = 0;
		$rv[] = 0; $rv[] = 0; $rv[] = 0; $rv[] = 0;
		
		if($node && $this !== $node){
			$nodeId = $node->getId();
			$thisId = $this->getId();
			
			for($idPos = 0; $idPos < Node::ID_LEN; $idPos++){
				$rv[$idPos] = $thisId[$idPos] ^ $nodeId[$idPos];
			}
		}
		
		return $rv;
	}
	
	public function distanceBitStr(Node $node){
		#print __CLASS__.'->'.__FUNCTION__."\n";
		$distance = $this->distance($node);
		
		$rv = '';
		for($idPos = 0; $idPos < Node::ID_LEN; $idPos++){
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
		#print __CLASS__.'->'.__FUNCTION__.": '".$this->getIdHexStr()."' '".$node->getIdHexStr()."'\n";
		#return $this->getId() == $node->getId();
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
