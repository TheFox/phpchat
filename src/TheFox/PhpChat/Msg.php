<?php

namespace TheFox\PhpChat;

use RuntimeException;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Utilities\Rand;

class Msg{
	
	private $version = 1;
	private $id = '';
	private $relayNodeId = '';
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
	private $forwardCycles = 0;
	private $encryptionMode = '';
	private $status = '';
	private $timeCreated = 0;
	
	private $ssl = null;
	private $msgDb = null;
	
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
		return array(
			'version',
			'id',
			'relayNodeId',
			'srcNodeId',
			'srcSslKeyPub',
			'srcUserNickname',
			'dstNodeId',
			'text',
			'password',
			'checksum',
			'sentNodes',
			'relayCount',
			'forwardCycles',
			'encryptionMode',
			'status',
			'timeCreated',
		);
	}
	
	public function __toString(){
		return __CLASS__.'->{'.$this->getId().'}';
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
	
	public function setRelayNodeId($relayNodeId){
		$this->relayNodeId = $relayNodeId;
	}
	
	public function getRelayNodeId(){
		return $this->relayNodeId;
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
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->dstSslPubKey = $dstSslPubKey;
	}
	
	public function getDstSslPubKey(){
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
	
	public function setForwardCycles($forwardCycles){
		$this->forwardCycles = (int)$forwardCycles;
	}
	
	public function incForwardCycles(){
		#$this->setForwardCycles($this->getForwardCycles() + 1);
		$this->forwardCycles++;
	}
	
	public function getForwardCycles(){
		return((int)$this->forwardCycles);
	}
	
	public function setEncryptionMode($encryptionMode){
		// S = encrypted with source node public key
		// D = encrypted with destination node public key
		
		#print __CLASS__.'->'.__FUNCTION__.': '.$encryptionMode."\n";
		$this->encryptionMode = $encryptionMode;
		$this->setDataChanged(true);
	}
	
	public function getEncryptionMode(){
		return $this->encryptionMode;
	}
	
	public function setStatus($status){
		// U = unread, got msg from another node
		// O = origin, local node created the msg
		// S = sent at least to one node
		// D = delivered to destination node
		// R = read
		// X = reached MSG_FORWARD_TO_NODES_MIN or MSG_FORWARD_TO_NODES_MAX
		//		or dstNodeId is in sentNodes array.
		
		#print __CLASS__.'->'.__FUNCTION__.': '.$status."\n";
		if($this->status != 'D'){
			$this->status = $status;
			$this->setDataChanged(true);
		}
	}
	
	public function getStatus(){
		return $this->status;
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
	
	public function setSslKeyPrvPath($sslKeyPrvPath, $sslKeyPrvPass){
		$this->setSslKeyPrv(file_get_contents($sslKeyPrvPath), $sslKeyPrvPass);
	}
	
	public function setSslKeyPrv($sslKeyPrv, $sslKeyPrvPass){
		$this->setSsl(openssl_pkey_get_private($sslKeyPrv, $sslKeyPrvPass));
	}
	
	public function setMsgDb(MsgDb $msgDb){
		$this->msgDb = $msgDb;
	}
	
	public function getMsgDb(){
		return $this->msgDb;
	}
	
	private function setDataChanged($changed = true){
		if($this->getMsgDb()){
			$this->getMsgDb()->setDataChanged($changed);
		}
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
		
		#ve($this->getDstSslPubKey());
		
		$text = $this->text;
		#$password = hash('sha512', mt_rand(0, 999999).'_'.time());
		$password = base64_encode(Rand::data(256));
		$passwordEncrypted = '';
		
		if(openssl_sign($password, $sign, $this->getSsl(), OPENSSL_ALGO_SHA1)){
			$sign = base64_encode($sign);
			
			if(openssl_public_encrypt($password, $cryped, $this->getDstSslPubKey())){
				#ve($cryped);
				#print __CLASS__.'->'.__FUNCTION__.' password: '.$cryped."\n";
				$passwordBase64 = base64_encode($cryped);
				$jsonStr = json_encode(array(
					'password' => $passwordBase64,
					'sign' => $sign,
				));
				#print __CLASS__.'->'.__FUNCTION__.' password: '.$jsonStr."\n";
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
		
		#print __CLASS__.'->'.__FUNCTION__.' password('.strlen($password).'): '.$password."\n";
		#print __CLASS__.'->'.__FUNCTION__.' passwordEncrypted: '.$passwordEncrypted."\n";
		
		if($passwordEncrypted){
			if(openssl_sign($text, $sign, $this->getSsl(), OPENSSL_ALGO_SHA1)){
				$sign = base64_encode($sign);
				$textBase64 = base64_encode($text);
				$srcUserNickname = base64_encode($this->getSrcUserNickname());
				
				$jsonStr = json_encode(array(
					'text' => $textBase64,
					'sign' => $sign,
					'srcUserNickname' => $srcUserNickname,
				));
				$data = gzencode($jsonStr, 9);
				
				$iv = substr(hash('sha512', mt_rand(0, 999999), true), 0, 16);
				$data = openssl_encrypt($data, 'AES-256-CBC', $password, 0, $iv);
				if($data !== false){
					$iv = base64_encode($iv);
					
					#print __CLASS__.'->'.__FUNCTION__.' data('.strlen($data).'): '.$data."\n";
					
					$jsonStr = json_encode(array(
						'data' => $data,
						'iv' => $iv,
					));
					$data = gzencode($jsonStr, 9);
					$data = base64_encode($data);
					
					$this->setText($data);
					
					$checksumData = $this->getVersion().'_'.$this->getId().'_'.$this->getSrcNodeId().'_'.$this->getDstNodeId().'_'.base64_encode($this->getDstSslPubKey()).'_'.base64_encode($text).'_'.$this->getTimeCreated();
					#print __CLASS__.'->'.__FUNCTION__.' checksumData('.strlen($checksumData).'): '.$checksumData."\n";
					$checksumSha512Bin = hash_hmac('sha512', $checksumData, $password, true);
					
					#$checksumSha512Bin = hash('sha512', $keyBin, true);
					$fingerprintHex = hash('ripemd160', $checksumSha512Bin, false);
					$fingerprintBin = hash('ripemd160', $checksumSha512Bin, true);
					
					$checksumHex = hash('sha512', hash('sha512', $fingerprintBin, true));
					$checksumHex = substr($checksumHex, 0, 8); // 4 Bytes
					$checksum = $fingerprintHex.$checksumHex;
					#$num = Hex::decode($fingerprintHex.$checksumHex);
					
					#print __CLASS__.'->'.__FUNCTION__.' checksum('.strlen($checksum).'): '.$checksum."\n";
					
					$this->setChecksum($checksum);
					
					$rv = true;
				}
			}
		}
		else{
			throw new RuntimeException('Can\'t create password.', 103);
		}
		
		#print __CLASS__.'->'.__FUNCTION__.' text: '.$this->getText()."\n";
		#print __CLASS__.'->'.__FUNCTION__.' checksum: '.$this->getChecksum()."\n";
		#print __CLASS__.'->'.__FUNCTION__.': done'."\n";
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
		if($json && isset($json['password']) && isset($json['sign'])){
			#ve($json);
			
			$passwordData = base64_decode($json['password']);
			$sign = base64_decode($json['sign']);
			
			#print __CLASS__.'->'.__FUNCTION__.': ssl = '.$this->getSsl()."\n";
			
			if(openssl_private_decrypt($passwordData, $decrypted, $this->getSsl())){
				if(openssl_verify($decrypted, $sign, $this->getSrcSslKeyPub(), OPENSSL_ALGO_SHA1)){
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
			
			#print __CLASS__.'->'.__FUNCTION__.' data: '.$data."\n";
			
			$json = json_decode($data, true);
			if($json && isset($json['data']) && isset($json['iv'])){
				#ve($json);
				
				$iv = base64_decode($json['iv']);
				#$data = base64_decode($json['data']);
				$data = $json['data'];
				
				#print __CLASS__.'->'.__FUNCTION__.' data: '.$data."\n";
				
				$data = openssl_decrypt($data, 'AES-256-CBC', $password, 0, $iv);
				if($data !== false){
					$data = gzdecode($data);
					
					#print __CLASS__.'->'.__FUNCTION__.' data: '.$data."\n";
					
					$json = json_decode($data, true);
					if($json && isset($json['text']) && isset($json['sign']) && isset($json['srcUserNickname'])){
						$text = base64_decode($json['text']);
						$sign = base64_decode($json['sign']);
						$srcUserNickname = base64_decode($json['srcUserNickname']);
						
						if(openssl_verify($text, $sign, $this->getSrcSslKeyPub(), OPENSSL_ALGO_SHA1)){
							$checksumData = $this->getVersion().'_'.$this->getId().'_'.$this->getSrcNodeId().'_'.$this->getDstNodeId().'_'.base64_encode($this->getDstSslPubKey()).'_'.base64_encode($text).'_'.$this->getTimeCreated();
							#print __CLASS__.'->'.__FUNCTION__.' checksumData('.strlen($checksumData).'): '.$checksumData."\n";
							#$checksum = hash_hmac('sha512', $checksumData, $password);
							
							$checksumSha512Bin = hash_hmac('sha512', $checksumData, $password, true);
							$fingerprintHex = hash('ripemd160', $checksumSha512Bin, false);
							$fingerprintBin = hash('ripemd160', $checksumSha512Bin, true);
							$checksumHex = hash('sha512', hash('sha512', $fingerprintBin, true));
							$checksumHex = substr($checksumHex, 0, 8); // 4 Bytes
							$checksum = $fingerprintHex.$checksumHex;
							
							#print __CLASS__.'->'.__FUNCTION__.' checksum A ('.strlen($checksum).'): '.$checksum."\n";
							#print __CLASS__.'->'.__FUNCTION__.' checksum B ('.strlen($this->getChecksum()).'): '.$this->getChecksum()."\n";
							
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
		
		#print __CLASS__.'->'.__FUNCTION__.' password('.strlen($password).'): '.$password."\n";
		#print __CLASS__.'->'.__FUNCTION__.' password'."\n";
		#ve($json);
		
		return $rv;
	}
	
}
