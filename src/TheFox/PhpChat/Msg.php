<?php

namespace TheFox\PhpChat;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

class Msg{
	
	private $version = 1;
	private $id = '';
	private $srcNodeId = '';
	private $srcSslKeyPub = '';
	private $srcUserNickname = '';
	private $dstNodeId = '';
	private $dstSslPubKey = '';
	private $text = '';
	private $password = '';
	private $checksum = '';
	private $sentNodes = array();
	private $relayCount = 0;
	private $timeCreated = 0;
	
	private $ssl = null;
	
	public function __construct($text = ''){
		try{
			$this->setId((string)Uuid::uuid4());
		}
		catch(UnsatisfiedDependencyException $e){
			# TODO
		}
		
		$this->setText($text);
		$this->setTimeCreated(time());
	}
	
	public function __sleep(){
		return array('version', 'id', 'srcNodeId', 'srcSslKeyPub', 'srcUserNickname', 'dstNodeId', 'text', 'sentNodes', 'relayCount', 'timeCreated');
	}
	
	public function setVersion($version){
		$this->version = $version;
	}
	
	public function getVersion(){
		return $this->version;
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function setSrcNodeId($srcNodeId){
		$this->srcNodeId = $srcNodeId;
	}
	
	public function getSrcNodeId(){
		return $this->srcNodeId;
	}
	
	public function setSrcSslKeyPub($srcSslKeyPub){
		$this->srcSslKeyPub = $srcSslKeyPub;
	}
	
	public function getSrcSslKeyPub(){
		return $this->srcSslKeyPub;
	}
	
	public function setSrcUserNickname($srcUserNickname){
		$this->srcUserNickname = $srcUserNickname;
	}
	
	public function getSrcUserNickname(){
		return $this->srcUserNickname;
	}
	
	public function setDstNodeId($dstNodeId){
		$this->dstNodeId = $dstNodeId;
	}
	
	public function getDstNodeId(){
		return $this->dstNodeId;
	}
	
	public function setDstSslPubKey($dstSslPubKey){
		$this->dstSslPubKey = $dstSslPubKey;
	}
	
	private function getDstSslPubKey(){
		return $this->dstSslPubKey;
	}
	
	public function setText($text){
		$this->text = $text;
	}
	
	public function getText(){
		return $this->text;
	}
	
	public function setPassword($password){
		$this->password = $password;
	}
	
	public function getPassword(){
		return $this->password;
	}
	
	public function setChecksum($checksum){
		$this->checksum = $checksum;
	}
	
	public function getChecksum(){
		return $this->checksum;
	}
	
	public function setSentNodes($sentNodes){
		$this->sentNodes = $sentNodes;
	}
	
	public function addSentNode($nodeId){
		$this->sentNodes[] = $nodeId;
	}
	
	public function getSentNodes(){
		return $this->sentNodes;
	}
	
	public function setRelayCount($relayCount){
		$this->relayCount = (int)$relayCount;
	}
	
	public function getRelayCount(){
		return((int)$this->relayCount);
	}
	
	public function setTimeCreated($timeCreated){
		$this->timeCreated = (int)$timeCreated;
	}
	
	public function getTimeCreated(){
		return((int)$this->timeCreated);
	}
	
	
	public function setSsl($ssl){
		$this->ssl = $ssl;
	}
	
	public function getSsl(){
		return $this->ssl;
	}
	
	public function setSslPrv($sslKeyPrvPath, $sslKeyPrvPass){
		#$this->setSsl(openssl_pkey_get_private(file_get_contents($sslKeyPrvPath), $sslKeyPrvPass)); # TODO
		$this->setSsl(openssl_pkey_get_private($sslKeyPrvPath, $sslKeyPrvPass));
	}
	
	public function encrypt(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
		$rv = false;
		
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.', 1);
		}
		if(!$this->getDstSslPubKey()){
			throw new RuntimeException('dstSslPubKey not set.', 2);
		}
		
		$text = $this->text;
		$password = hash('sha512', mt_rand(0, 999999).'_'.time());
		$passwordEncrypted = '';
		
		if(openssl_sign($password, $sign, $this->getSsl(), OPENSSL_ALGO_SHA1)){
			$sign = base64_encode($sign);
			
			if(openssl_public_encrypt($password, $cryped, $this->getSrcSslKeyPub())){
				$passwordBase64 = base64_encode($cryped);
				$jsonStr = json_encode(array('data' => $passwordBase64, 'sign' => $sign));
				$gzdata = gzencode($jsonStr, 9);
				$passwordEncrypted = base64_encode($gzdata);
				
				$this->setPassword($passwordEncrypted);
			}
			else{
				throw new RuntimeException('openssl_public_encrypt failed.', 101);
			}
		}
		else{
			throw new RuntimeException('openssl_sign failed.', 102);
		}
		
		print __CLASS__.'->'.__FUNCTION__.' password: '.$password."\n";
		#print __CLASS__.'->'.__FUNCTION__.' passwordEncrypted: '.$passwordEncrypted."\n";
		
		if($passwordEncrypted){
			if(openssl_sign($text, $sign, $this->getSsl(), OPENSSL_ALGO_SHA1)){
				$sign = base64_encode($sign);
				$textBase64 = base64_encode($text);
				$userNickname = base64_encode($this->getSrcUserNickname());
				
				$jsonStr = json_encode(array(
					'text' => $textBase64,
					'sign' => $sign,
					'userNickname' => $userNickname,
				));
				$data = gzencode($jsonStr, 9);
				
				$iv = substr(hash('sha512', mt_rand(0, 999999), true), 0, 16);
				$data = openssl_encrypt($data, 'AES-256-CBC', $password, 0, $iv);
				if($data !== false){
					$iv = base64_encode($iv);
					
					$jsonStr = json_encode(array(
						'data' => $data,
						'iv' => $iv,
					));
					$data = gzencode($jsonStr, 9);
					$data = base64_encode($data);
					
					$this->setText($data);
					
					$checksumData = $this->getId().'_'.$this->getSrcNodeId().'_'.$this->getDstNodeId().'_'.base64_encode($text).'_'.$this->getTimeCreated();
					$this->setChecksum(hash_hmac('sha512', $checksumData, $password));
					
					$rv = true;
				}
			}
		}
		else{
			throw new RuntimeException('Can\'t create password.', 103);
		}
		
		print __CLASS__.'->'.__FUNCTION__.' text: '.$this->getText()."\n";
		print __CLASS__.'->'.__FUNCTION__.' checksum: '.$this->getChecksum()."\n";
		
		print __CLASS__.'->'.__FUNCTION__.': done'."\n";
		
		return $rv;
	}
	
}
