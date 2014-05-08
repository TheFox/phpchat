<?php

namespace TheFox\PhpChat;

use RuntimeException;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Yaml\YamlStorage;
use TheFox\Utilities\Rand;

class Msg extends YamlStorage{
	
	private $srcSslKeyPub = '';
	private $srcUserNickname = '';
	private $dstSslPubKey = '';
	
	private $ssl = null;
	private $msgDb = null;
	
	public function __construct($filePath = null){
		parent::__construct($filePath);
		
		$this->data['version'] = 1;
		$this->data['id'] = '';
		$this->data['relayNodeId'] = '';
		$this->data['srcNodeId'] = '';
		$this->data['dstNodeId'] = '';
		$this->data['text'] = '';
		$this->data['password'] = ''; 
		$this->data['checksum'] = '';
		$this->data['sentNodes'] = array();
		$this->data['relayCount'] = 0;
		$this->data['forwardCycles'] = 0;
		$this->data['encryptionMode'] = '';
		$this->data['status'] = '';
		$this->data['timeCreated'] = time();
		$this->data['timeReceived'] = 0;
	}
	
	public function __sleep(){
		return array(
			'data',
			'dataChanged',
			'srcSslKeyPub',
			'srcUserNickname',
			'dstSslPubKey',
		);
	}
	
	public function __toString(){
		return __CLASS__.'->{'.$this->getId().'}';
	}
	
	public function save(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->data['srcSslKeyPub'] = base64_encode($this->srcSslKeyPub);
		return parent::save();
	}
	
	public function load(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(parent::load()){
			$this->setSrcSslKeyPub(base64_decode($this->data['srcSslKeyPub']));
			unset($this->data['srcSslKeyPub']);
			
			return true;
		}
		return false;
	}
	
	public function setVersion($version){
		$this->data['version'] = $version;
	}
	
	public function getVersion(){
		return $this->data['version'];
	}
	
	public function setId($id){
		$this->data['id'] = $id;
	}
	
	public function getId(){
		#print __CLASS__.'->'.__FUNCTION__.': "'.$this->data['id'].'"'."\n";
		
		if(!isset($this->data['id']) || !$this->data['id']){
			#print __CLASS__.'->'.__FUNCTION__.': no set'."\n";
			try{
				$this->data['id'] = (string)Uuid::uuid4();
				#print __CLASS__.'->'.__FUNCTION__.': new id: '.$this->data['id']."\n";
			}
			catch(UnsatisfiedDependencyException $e){
				# TODO
			}
		}
		return $this->data['id'];
	}
	
	public function setRelayNodeId($relayNodeId){
		$this->data['relayNodeId'] = $relayNodeId;
	}
	
	public function getRelayNodeId(){
		return $this->data['relayNodeId'];
	}
	
	public function setSrcNodeId($srcNodeId){
		$this->data['srcNodeId'] = $srcNodeId;
	}
	
	public function getSrcNodeId(){
		return $this->data['srcNodeId'];
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
		$this->data['dstNodeId'] = $dstNodeId;
	}
	
	public function getDstNodeId(){
		return $this->data['dstNodeId'];
	}
	
	public function setDstSslPubKey($dstSslPubKey){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->dstSslPubKey = $dstSslPubKey;
	}
	
	public function getDstSslPubKey(){
		return $this->dstSslPubKey;
	}
	
	public function setText($text){
		$this->data['text'] = $text;
	}
	
	public function getText(){
		return $this->data['text'];
	}
	
	public function setPassword($password){
		$this->data['password'] = $password;
	}
	
	public function getPassword(){
		return $this->data['password'];
	}
	
	public function setChecksum($checksum){
		$this->data['checksum'] = $checksum;
	}
	
	public function getChecksum(){
		return $this->data['checksum'];
	}
	
	public function setSentNodes($sentNodes){
		$this->data['sentNodes'] = $sentNodes;
	}
	
	public function addSentNode($nodeId){
		$this->data['sentNodes'][] = $nodeId;
	}
	
	public function getSentNodes(){
		return $this->data['sentNodes'];
	}
	
	public function setRelayCount($relayCount){
		$this->data['relayCount'] = (int)$relayCount;
	}
	
	public function getRelayCount(){
		return ((int)$this->data['relayCount']);
	}
	
	public function setForwardCycles($forwardCycles){
		$this->data['forwardCycles'] = (int)$forwardCycles;
	}
	
	public function incForwardCycles(){
		#$this->setForwardCycles($this->getForwardCycles() + 1);
		$this->data['forwardCycles']++;
	}
	
	public function getForwardCycles(){
		return ((int)$this->data['forwardCycles']);
	}
	
	public function setEncryptionMode($encryptionMode){
		// S = encrypted with source node public key
		// D = encrypted with destination node public key
		
		#print __CLASS__.'->'.__FUNCTION__.': '.$encryptionMode."\n";
		$this->data['encryptionMode'] = $encryptionMode;
		$this->setDataChanged(true);
	}
	
	public function getEncryptionMode(){
		return $this->data['encryptionMode'];
	}
	
	public function setStatus($status){
		#print __CLASS__.'->'.__FUNCTION__.': '.$status."\n";
		if($this->data['status'] != 'D'){
			$this->data['status'] = $status;
			$this->setDataChanged(true);
		}
	}
	
	public function getStatus(){
		return $this->data['status'];
	}
	
	public function getStatusText(){
		$text = array(
			'U' => 'unread, got msg from another node',
			'O' => 'origin, local node created the msg',
			'S' => 'sent at least to one node',
			'D' => 'delivered to destination node',
			'R' => 'read',
			'X' => 'reached MSG_FORWARD_TO_NODES_MIN or MSG_FORWARD_TO_NODES_MAX',
		);
		
		return $text[$this->getStatus()];
	}
	
	public function setTimeCreated($timeCreated){
		$this->data['timeCreated'] = (int)$timeCreated;
	}
	
	public function getTimeCreated(){
		return ((int)$this->data['timeCreated']);
	}
	
	public function setTimeReceived($timeReceived){
		$this->data['timeReceived'] = (int)$timeReceived;
	}
	
	public function getTimeReceived(){
		return ((int)$this->data['timeReceived']);
	}
	
	public function setSsl($ssl){
		$this->ssl = $ssl;
	}
	
	public function getSsl(){
		return $this->ssl;
	}
	
	public function setSslKeyPrvPath($sslKeyPrvPath, $sslKeyPrvPass){
		$this->setSslKeyPrv(file_get_contents($sslKeyPrvPath), $sslKeyPrvPass);
	}
	
	public function setSslKeyPrv($sslKeyPrv, $sslKeyPrvPass){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		$ssl = openssl_pkey_get_private($sslKeyPrv, $sslKeyPrvPass);
		if($ssl){
			$this->setSsl($ssl);
		}
		else{
			throw new RuntimeException("SSL: openssl_pkey_get_private failed. Maybe it's not a private key.", 1);
		}
	}
	
	public function setMsgDb(MsgDb $msgDb){
		$this->msgDb = $msgDb;
	}
	
	public function getMsgDb(){
		return $this->msgDb;
	}
	
	public function encrypt(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		$rv = false;
		
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.', 1);
		}
		if(!$this->getDstSslPubKey()){
			throw new RuntimeException('dstSslPubKey not set.', 2);
		}
		
		$text = $this->getText();
		$password = base64_encode(Rand::data(256));
		$passwordEncrypted = '';
		$signAlgo = OPENSSL_ALGO_SHA1;
		
		$signRv = true;
		$signRv = openssl_sign($password, $sign, $this->getSsl(), $signAlgo);
		if($signRv){
			$sign = base64_encode($sign);
			
			$pubEncRv = openssl_public_encrypt($password, $cryped, $this->getDstSslPubKey());
			if($pubEncRv){
				$passwordBase64 = base64_encode($cryped);
				$jsonStr = json_encode(array(
					'password' => $passwordBase64,
					'sign' => $sign,
					'signAlgo' => $signAlgo,
				));
				
				$gzdata = gzencode($jsonStr, 9);
				$passwordEncrypted = base64_encode($gzdata);
				
				$this->setPassword($passwordEncrypted);
			}
			else{
				throw new RuntimeException('openssl_public_encrypt failed: "'.openssl_error_string().'"', 101);
			}
		}
		else{
			throw new RuntimeException('openssl_sign failed.', 102);
		}
		
		if($passwordEncrypted){
			$signRv = openssl_sign($text, $sign, $this->getSsl(), $signAlgo);
			if($signRv){
				$sign = base64_encode($sign);
				$textBase64 = base64_encode($text);
				$srcUserNickname = base64_encode($this->getSrcUserNickname());
				
				$jsonStr = json_encode(array(
					'text' => $textBase64,
					'sign' => $sign,
					'signAlgo' => $signAlgo,
					'srcUserNickname' => $srcUserNickname,
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
					
					$checksumData = $this->getVersion().'_'.$this->getId();
					$checksumData .= '_'.$this->getSrcNodeId();
					$checksumData .= '_'.$this->getDstNodeId().'_'.base64_encode($this->getDstSslPubKey());
					$checksumData .= '_'.base64_encode($text).'_'.$this->getTimeCreated();
					
					$checksumSha512Bin = hash_hmac('sha512', $checksumData, $password, true);
					$fingerprintHex = hash('ripemd160', $checksumSha512Bin, false);
					$fingerprintBin = hash('ripemd160', $checksumSha512Bin, true);
					$checksumHex = hash('sha512', hash('sha512', $fingerprintBin, true));
					$checksumHex = substr($checksumHex, 0, 8); // 4 Bytes
					$checksum = $fingerprintHex.$checksumHex;
					
					$this->setChecksum($checksum);
					
					$rv = true;
				}
			}
		}
		else{
			throw new RuntimeException('Can\'t create password.', 103);
		}
		
		return $rv;
	}
	
	public function decrypt(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		$rv = '';
		
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.', 10);
		}
		if(!$this->getSrcSslKeyPub()){
			throw new RuntimeException('srcSslKeyPub not set.', 20);
		}
		if(!$this->getDstNodeId()){
			throw new RuntimeException('dstNodeId not set.', 30);
		}
		if(!$this->getDstSslPubKey()){
			throw new RuntimeException('dstSslPubKey not set.', 40);
		}
		if(!$this->getPassword()){
			throw new RuntimeException('password not set.', 50);
		}
		if(!$this->getChecksum()){
			throw new RuntimeException('checksum not set.', 60);
		}
		
		$password = '';
		$passwordData = $this->getPassword();
		$passwordData = base64_decode($passwordData);
		$passwordData = gzdecode($passwordData);
		
		$json = json_decode($passwordData, true);
		if($json && isset($json['password']) && isset($json['sign']) && isset($json['signAlgo'])){
			$passwordData = base64_decode($json['password']);
			$sign = base64_decode($json['sign']);
			$signAlgo = (int)$json['signAlgo'];
			
			if(openssl_private_decrypt($passwordData, $decrypted, $this->getSsl())){
				if(openssl_verify($decrypted, $sign, $this->getSrcSslKeyPub(), $signAlgo)){
					$password = $decrypted;
				}
				else{
					throw new RuntimeException('password openssl_verify failed.', 103);
				}
			}
			else{
				throw new RuntimeException('password openssl_private_decrypt failed: "'.openssl_error_string().'"', 102);
			}
		}
		else{
			throw new RuntimeException('password json_decode failed.', 101);
		}
		
		if($password){
			$data = $this->getText();
			$data = base64_decode($data);
			$data = gzdecode($data);
			
			$json = json_decode($data, true);
			if($json && isset($json['data']) && isset($json['iv'])){
				$iv = base64_decode($json['iv']);
				$data = $json['data'];
				
				$data = openssl_decrypt($data, 'AES-256-CBC', $password, 0, $iv);
				if($data !== false){
					$data = gzdecode($data);
					
					$json = json_decode($data, true);
					if($json && isset($json['text']) && isset($json['sign'])
						&& isset($json['signAlgo']) && isset($json['srcUserNickname'])){
						$text = base64_decode($json['text']);
						$sign = base64_decode($json['sign']);
						$signAlgo = (int)$json['signAlgo'];
						$srcUserNickname = base64_decode($json['srcUserNickname']);
						
						if(openssl_verify($text, $sign, $this->getSrcSslKeyPub(), $signAlgo)){
							$checksumData = $this->getVersion().'_'.$this->getId();
							$checksumData .= '_'.$this->getSrcNodeId();
							$checksumData .= '_'.$this->getDstNodeId().'_'.base64_encode($this->getDstSslPubKey());
							$checksumData .= '_'.base64_encode($text).'_'.$this->getTimeCreated();
							
							$checksumSha512Bin = hash_hmac('sha512', $checksumData, $password, true);
							$fingerprintHex = hash('ripemd160', $checksumSha512Bin, false);
							$fingerprintBin = hash('ripemd160', $checksumSha512Bin, true);
							$checksumHex = hash('sha512', hash('sha512', $fingerprintBin, true));
							$checksumHex = substr($checksumHex, 0, 8); // 4 Bytes
							$checksum = $fingerprintHex.$checksumHex;
							
							if($checksum == $this->getChecksum()){
								$this->setSrcUserNickname($srcUserNickname);
								
								$rv = $text;
							}
							else{
								throw new RuntimeException('msg checksum does not match.', 206);
							}
						}
						else{
							throw new RuntimeException('msg openssl_verify failed.', 205);
						}
					}
					else{
						throw new RuntimeException('msg json_decode B failed.', 204);
					}
				}
				else{
					throw new RuntimeException('msg openssl_decrypt failed: "'.openssl_error_string().'"', 203);
				}
				
				
			}
			else{
				throw new RuntimeException('msg json_decode A failed.', 202);
			}
		}
		else{
			throw new RuntimeException('no password set.', 201);
		}
		
		return $rv;
	}
	
}
