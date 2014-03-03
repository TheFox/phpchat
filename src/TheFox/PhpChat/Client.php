<?php

namespace TheFox\PhpChat;

use Exception;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Utilities\Hex;

class Client{
	
	const ACTION_NODE_FIND_TTL = 30;
	const ACTION_NODE_FIND_MAX_NODE_IDS = 128;
	const ACTION_SSL_INIT_TTL = 30;
	const ACTION_SSL_KEY_PUB_GET_TTL = 30;
	const ACTION_TALK_REQUEST_TTL = 300;
	const ACTION_TALK_RESPONSE_TTL = 30;
	const ACTION_TALK_MSG_TTL = 30;
	const ACTION_TALK_CLOSE_TTL = 30;
	const SOCKET_CONNECT_TTL = 2;
	
	private $id = 0;
	private $socket = null;
	private $socketBuffer = '';
	private $socketConnected = false;
	private $ip = '';
	private $port = 0;
	private $node = null;
	private $hasId = false;
	private $hasSslInitSent = false;
	private $hasSslInit = false;
	private $hasSslTest = false;
	private $sslToken = '';
	private $hasSslVerified = false;
	private $sslPassword = '';
	private $sslPasswordNode = '';
	private $sslPasswordToken = '';
	private $hasSslPasswordTest = false;
	private $hasSsl = false;
	private $hasBootstrapped = false;
	private $bootstrapSuccess = null;
	private $isOnlyPing = false;
	private $isOnlyPingPinged = false;
	private $isOnlyNodeFind = false;
	private $isOnlyNodeFindFound = false;
	private $isNetworkBootstrap = false;
	private $isChannel = false;
	private $actionsId = 0;
	private $actions = array();
	private $timeCreated = time();
	private $timeLastSeen = time();
	private $settings = null;
	private $ssl = null;
	private $log = null;
	private $server = null;
	
	public function __construct(){
		print __CLASS__.'->'.__FUNCTION__.''."\n";
	}
	
	public function __destruct(){
		$this->log('debug', __CLASS__.'->'.__FUNCTION__.': '.$this->getId());
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function setSocket($socket){
		$this->socket = $socket;
	}
	
	public function getSocket(){
		return $this->socket;
	}
	
	public function socketShutdown(){
		Socket::shutdown($this->getSocket());
		Socket::close($this->getSocket());
	}
	
	public function setSocketBuffer($socketBuffer){
		$this->socketBuffer = $socketBuffer;
	}
	
	public function appendSocketBuffer($buffer){
		$this->socketBuffer .= $buffer;
	}
	
	public function getSocketBuffer(){
		return $this->socketBuffer;
	}
	
	public function setSocketConnected($socketConnected){
		$this->socketConnected = $socketConnected;
	}
	
	public function getSocketConnected(){
		return $this->socketConnected;
	}
	
	public function setIp($ip){
		$this->ip = $ip;
	}
	
	public function getIp(){
		return $this->ip;
	}
	
	public function setPort($port){
		$this->port = $port;
	}
	
	public function getPort(){
		return $this->port;
	}
	
	public function setNode($node){
		$this->node = $node;
	}
	
	public function getNode(){
		return $this->node;
	}
	
	public function nodeIsSet(){
		return isset($this->node) && is_object($this->node);
	}
	
	public function setHasId($hasId){
		$this->hasId = $hasId;
	}
	
	public function getHasId(){
		return $this->hasId;
	}
	
	public function setHasSslInitSent($hasSslInitSent){
		$this->hasSslInitSent = $hasSslInitSent;
	}
	
	public function getHasSslInitSent(){
		return $this->hasSslInitSent;
	}
	
	public function setHasSslInit($hasSslInit){
		$this->hasSslInit = $hasSslInit;
	}
	
	public function getHasSslInit(){
		return $this->hasSslInit;
	}
	
	public function setHasSslTest($hasSslTest){
		$this->hasSslTest = $hasSslTest;
	}
	
	public function getHasSslTest(){
		return $this->hasSslTest;
	}
	
	public function setSslToken($sslToken){
		return $this->sslToken = $sslToken;
	}
	
	public function getSslToken(){
		return $this->sslToken;
	}
	
	public function setHasSslVerified($hasSslVerified){
		$this->hasSslVerified = $hasSslVerified;
	}
	
	public function getHasSslVerified(){
		return $this->hasSslVerified;
	}
	
	public function setSslPassword($sslPassword){
		$this->sslPassword = $sslPassword;
	}
	
	public function getSslPassword(){
		return $this->sslPassword;
	}
	
	public function setSslPasswordNode($sslPasswordNode){
		$this->sslPasswordNode = $sslPasswordNode;
	}
	
	public function getSslPasswordNode(){
		return $this->sslPasswordNode;
	}
	
	public function setSslPasswordToken($sslPasswordToken){
		return $this->sslPasswordToken = $sslPasswordToken;
	}
	
	public function getSslPasswordToken(){
		return $this->sslPasswordToken;
	}
	
	public function setHasSslPasswordTest($hasSslPasswordTest){
		$this->hasSslPasswordTest = $hasSslPasswordTest;
	}
	
	public function getHasSslPasswordTest(){
		return $this->hasSslPasswordTest;
	}
	
	public function setHasSsl($hasSsl){
		$this->hasSsl = $hasSsl;
	}
	
	public function getHasSsl(){
		return $this->hasSsl;
	}
	
	public function setHasBootstrapped($hasBootstrapped){
		$this->hasBootstrapped = $hasBootstrapped;
	}
	
	public function getHasBootstrapped(){
		return $this->hasBootstrapped;
	}
	
	public function setBootstrapSuccess($bootstrapSuccess){
		$this->bootstrapSuccess = $bootstrapSuccess;
	}
	
	public function getBootstrapSuccess(){
		return $this->bootstrapSuccess;
	}
	
	/*public function setIsOnlyPing($isOnlyPing){
		$this->isOnlyPing = $isOnlyPing;
	}
	
	public function getIsOnlyPing(){
		return $this->isOnlyPing;
	}
	
	public function setIsOnlyPingPinged($isOnlyPingPinged){
		$this->isOnlyPingPinged = $isOnlyPingPinged;
	}
	
	public function getIsOnlyPingPinged(){
		return $this->isOnlyPingPinged;
	}*/
	
	/*public function setIsOnlyNodeFind($isOnlyNodeFind){
		$this->isOnlyNodeFind = $isOnlyNodeFind;
	}*/
	
	public function getIsOnlyNodeFind(){
		return $this->isOnlyNodeFind;
	}
	
	public function setIsOnlyNodeFindFound($isOnlyNodeFindFound){
		$this->isOnlyNodeFindFound = $isOnlyNodeFindFound;
	}
	
	public function getIsOnlyNodeFindFound(){
		return $this->isOnlyNodeFindFound;
	}
	
	public function setIsNetworkBootstrap($isNetworkBootstrap){
		$this->isNetworkBootstrap = $isNetworkBootstrap;
	}
	
	public function getIsNetworkBootstrap(){
		return $this->isNetworkBootstrap;
	}
	
	public function setIsChannel($isChannel){
		$this->isChannel = $isChannel;
	}
	
	public function getIsChannel(){
		return $this->isChannel;
	}
	
	public function setActionsId($actionsId){
		$this->actionsId = $actionsId;
	}
	
	public function getActionsId(){
		return $this->actionsId;
	}
	
	public function actionsIdInc(){
		$this->actionsId++;
	}
	
	/*public function setActions($actions){
		$this->actions = $actions;
	}*/
	
	public function getActions(){
		return $this->actions;
	}
	
	public function actionRemove($action){
		unset($this->actions[$action['id']]);
	}
	
	/*public function setTimeCreated($timeCreated){
		$this->timeCreated = $timeCreated;
	}
	
	public function getTimeCreated(){
		return $this->timeCreated;
	}*/
	
	public function setTimeLastSeen($timeLastSeen){
		$this->timeLastSeen = $timeLastSeen;
	}
	
	public function getTimeLastSeen(){
		return $this->timeLastSeen;
	}
	
	public function setSettings($settings){
		$this->settings = $settings;
	}
	
	/*public function getSettings(){
		return $this->settings;
	}*/
	
	public function setSsl($ssl){
		$this->ssl = $ssl;
	}
	
	public function getSsl(){
		return $this->ssl;
	}
	
	public function setLog($log){
		$this->log = $log;
	}
	
	public function getLog(){
		return $this->log;
	}
	
	public function log($level, $text){
		if($this->getLog()){
			$log = $this->getLog();
			$log->$level($text);
		}
	}
	
	public function setServer($server){
		$this->server = $server;
	}
	
	public function getServer(){
		return $this->server;
	}
	
	
	public function connect($ip, $port){
		
		$socket = Socket::create();
		if(!$socket){
			return false;
		}
		
		$this->setSocket($socket);
		$this->setIp($ip);
		$this->setPort($port);
		
		$socketErrorNo = 0;
		$socketErrorStr = '';
		Socket::clearError($socket);
		
		$this->log('debug', 'connecting to: '.$ip.':'.$port);
		
		$timeStart = time();
		$timeout = true;
		
		while(time() - $timeStart <= static::SOCKET_CONNECT_TTL){
			Socket::connect($socket, $ip, $port);
			$errorNo = Socket::lastError($socket);
			if($errorNo == 56){ // "Socket is already connected"
				$this->log('debug', 'connected to: '.$ip.':'.$port.' ('.$socket.')');
				
				$timeout = false;
				$this->setSocketConnected(true);
				$this->sendHello($ip);
				break;
			}
			usleep(10000);
		}
		
		if($timeout){
			$this->log('warning', 'connection to '.$ip.':'.$port.' failed: timeout ('.(int)$errorNo.', '.Socket::strError($errorNo).')');
		}
		
		return $this->getSocketConnected();
	}
	
	public function sendHello(){
		$this->socketSendMsg('HELLO '.$this->getIp());
	}
	
	public function sendId(){
		$this->socketSendMsg('ID '.json_encode(array(
			'id' => $this->settings['tmp']['node']->getIdHexStr(),
			'port' => $this->settings['tmp']['node']->getPort(),
			'sslKeyPubFingerprint' => $this->settings['tmp']['node']->getSslKeyPubFingerprint(),
			'sslKeyPub' => base64_encode($this->settings['tmp']['node']->getSslKeyPub()),
			'isChannel' => $this->getIsChannel(),
		)));
	}
	
	public function sslInit(){
		if(!$this->getHasSsl()){
			$this->sendSslInit();
			$this->setHasSslInitSent(true);
		}
	}
	
	public function sendSslInit(){
		$keyPub = $this->settings['tmp']['node']->getSslKeyPub();
		$keyPub = base64_encode($keyPub);
		
		$json = array('keyPub' => $keyPub);
		$jsonStr = json_encode($json);
		
		$this->socketSendMsg('SSL_INIT '.$jsonStr);
	}
	
	public function sendSslTest(){
		$token = $this->setSslToken((string)Uuid::uuid4());
		#$this->log('debug', 'send SSL_TEST: '.$token);
		
		$data = $this->sslPublicEncrypt($token);
		if($data){
			$this->socketSendMsg('SSL_TEST '.$data);
		}
		#else{ $this->log('warning', 'sendSslTest sslPublicEncrypt failed'); }
	}
	
	public function sendSslVerify($token){
		#$this->log('debug', 'send SSL_VERIFY: '.$token);
		
		$data = $this->sslPublicEncrypt($token);
		if($data){
			$this->socketSendMsg('SSL_VERIFY '.$data);
		}
		#else{ $this->log('warning', 'sendSslVerify sslPublicEncrypt failed'); }
	}
	
	public function sslVerifyToken($token){
		if($token && $this->getSslToken() && $token == $this->getSslToken()){
			return true;
		}
		
		return false;
	}
	
	public function sendSslPasswordPut(){
		$addr_pub = (string)$this->settings['phpchat']['node']['addr_pub'];
		$password = hash('sha512', $addr_pub.'_'.mt_rand(0, 999999));
		
		$this->setSslPassword($password);
		
		#$this->log('debug', 'send SSL_PASSWORD_PUT: '.$password);
		
		$data = $this->sslPublicEncrypt($password);
		if($data){
			$this->socketSendMsg('SSL_PASSWORD_PUT '.$data);
		}
		#else{ $this->log('warning', 'sendSslPasswordPut sslPublicEncrypt failed'); }
	}
	
	public function sendSslPasswordTest(){
		$token = $this->setSslPasswordToken((string)Uuid::uuid4());
		
		#$this->log('debug', 'send SSL_PASSWORD_TEST: '.$token);
		
		$data = $this->sslPasswordEncrypt($token);
		if($data){
			$this->socketSendMsg('SSL_PASSWORD_TEST '.$data);
		}
		#else{ $this->log('warning', 'sendSslPasswordTest sslPasswordEncrypt failed'); }
	}
	
	public function sendSslPasswordVerify($token){
		#$this->log('debug', 'send SSL_PASSWORD_VERIFY: '.$token);
		
		$data = $this->sslPasswordEncrypt($token);
		if($data){
			$this->socketSendMsg('SSL_PASSWORD_VERIFY '.$data);
		}
		#else{ $this->log('warning', 'sendSslPasswordVerify sslPasswordEncrypt failed'); }
	}
	
	public function sslPasswordVerifyToken($token){
		if($token && $this->getSslPasswordToken() && $token == $this->getSslPasswordToken()){
			return true;
		}
		
		return false;
	}
	
	public function sendSslPubKeyGet($rid, $nodeSslKeyPubFingerprint){
		$json = array(
			'rid' => $rid,
			'nodeSslKeyPubFingerprint' => $nodeSslKeyPubFingerprint,
		);
		$jsonStr = json_encode($json);
		$this->socketSendMsg('SSL_KEY_PUB_GET '.$jsonStr);
	}
	
	public function sendSslPubKeyPut($rid, $nodeId = null, $nodeIp = null, $nodePort = null, $nodeSslKeyPubFingerprint = null, $nodeSslKeyPub = null){
		$this->log('debug', 'sendSslPubKey begin');
		
		$json = array(
			'rid' => $rid,
			'nodeId' => $nodeId,
			'nodeIp' => $nodeIp,
			'nodePort' => $nodePort,
			'nodeSslKeyPubFingerprint' => $nodeSslKeyPubFingerprint,
			'nodeSslKeyPub' => base64_encode($nodeSslKeyPub),
		);
		$jsonStr = json_encode($json);
		$this->socketSendMsg('SSL_KEY_PUB_PUT '.$jsonStr);
		
		$this->log('debug', 'sendSslPubKey done');
	}
	
	public function sendNodeFind($rid, $id){
		$this->socketSendMsg('NODE_FIND '.json_encode(array(
			'num' => Server::NODES_FIND_NUM,
			'rid' => $rid,
			'id' => $id,
		)));
	}
	
	public function sendNodeFound($rid, StackableArray $nodes){
		$json = array(
			'rid' => $rid,
			'nodes' => array(),
		);
		
		foreach($nodes as $node){
			$json['nodes'][] = array(
				'id' => $node->getIdHexStr(),
				'ip' => $node->getIp(),
				'port' => $node->getPort(),
				'sslKeyPub' => base64_encode($node->getSslKeyPub()),
			);
		}
		
		$jsonStr = json_encode($json);
		$this->socketSendMsg('NODE_FOUND '.$jsonStr);
	}
	
	public function sendTalkRequest($rid, $userNickname){
		$json = array(
			'rid' => $rid,
			'userNickname' => $userNickname,
		);
		$jsonStr = json_encode($json);
		$out = $this->sslPasswordEncrypt($jsonStr);
		if($out){
			$this->socketSendMsg('TALK_REQUEST '.$out);
		}
		else{ $this->log('warning', 'sendTalkRequest: sslPasswordEncrypt failed'); }
	}
	
	public function sendTalkResponse($rid, $status, $userNickname = ''){
		$json = array(
			'rid' => $rid,
			'status' => $status,
			'userNickname' => $userNickname,
		);
		$jsonStr = json_encode($json);
		$out = $this->sslPasswordEncrypt($jsonStr);
		if($out){
			$this->socketSendMsg('TALK_RESPONSE '.$out);
		}
		else{ $this->log('warning', 'sendTalkResponse: sslPasswordEncrypt failed'); }
	}
	
	public function sendTalkMsg($ignore, $userNickname, $text){
		$json = array(
			'ignore' => $ignore,
			'userNickname' => $userNickname,
			'text' => $text,
		);
		$jsonStr = json_encode($json);
		$out = $this->sslPasswordEncrypt($jsonStr);
		if($out){
			$this->socketSendMsg('TALK_MSG '.$out);
		}
		else{ $this->log('warning', 'sendTalkMsg: sslPasswordEncrypt failed'); }
	}
	
	public function sendTalkClose($userNickname){
		$json = array(
			'userNickname' => $userNickname,
		);
		$jsonStr = json_encode($json);
		$out = $this->sslPasswordEncrypt($jsonStr);
		if($out){
			$this->socketSendMsg('TALK_CLOSE '.$out);
		}
		else{ $this->log('warning', 'sendTalkClose: sslPasswordEncrypt failed'); }
	}
	
	public function sendPing(){
		$this->socketSendMsg('PING');
	}
	
	public function sendPong($msg = ''){
		$this->socketSendMsg('PONG'.($msg ? ' '.$msg : ''));
	}
	
	public function sendQuit(){
		$this->socketSendMsg('QUIT');
	}
	
	public function sendError($errorNo, $label = ''){
		$texts = array(
			// 100-199
			100 => 'You need to identify',
			110 => 'You already identified',
			120 => 'You are using my ID',
			
			// 200-399
			200 => 'SSL: You need to identify with SSL on',
			210 => 'SSL: No public key found',
			220 => 'SSL: Public key too short',
			230 => 'SSL: Public key changed since last handshake',
			240 => 'SSL: Decrypt failed',
			250 => 'SSL: Bad signature',
			260 => 'SSL: No signature',
			270 => 'SSL: No msg field found',
			280 => 'SSL: Public key verification failed',
			290 => 'SSL: we already hasSslInit',
			
			// 400
			400 => 'You are not registered as channel',
			
			// 900-999
			900 => 'Invalid data',
		);
		$text = $texts[$errorNo];
		
		$this->socketSendMsg('ERROR '.$errorNo.' ('.$text.')'.($label ? ': '.$label : ''));
	}
	
	public function sslPublicEncrypt($data){
		$rv = '';
		
		if(openssl_sign($data, $sign, $this->getSsl(), OPENSSL_ALGO_SHA1)){
			$sign = base64_encode($sign);
			
			if(openssl_public_encrypt($data, $cryped, $this->getNode()->getSslKeyPub())){
				$data = base64_encode($cryped);
				
				$jsonStr = json_encode(array('data' => $data, 'sign' => $sign));
				$gzdata = gzencode($jsonStr, 9);
				#$this->log('debug', 'gzdata len: '.strlen($gzdata));
				
				$rv = base64_encode($gzdata);
			}
			#else{ $this->log('warning', 'sslPublicEncrypt openssl_public_encrypt failed'); }
		}
		#else{ $this->log('warning', 'sslPublicEncrypt openssl_sign failed'); }
		
		return $rv;
	}
	
	public function sslPrivateDecrypt($data){
		$rv = '';
		
		$data = base64_decode($data);
		$data = gzdecode($data);
		$json = json_decode($data, true);
		
		$data = base64_decode($json['data']);
		$sign = base64_decode($json['sign']);
		
		if(openssl_private_decrypt($data, $decrypted, $this->getSsl())){
			if(openssl_verify($decrypted, $sign, $this->getNode()->getSslKeyPub(), OPENSSL_ALGO_SHA1)){
				$rv = $decrypted;
			}
			#else{ $this->log('warning', 'sslPrivateDecrypt openssl_verify failed'); }
		}
		#else{ $this->log('warning', 'sslPrivateDecrypt openssl_private_decrypt failed'); }
		
		return $rv;
	}
	
	public function sslPasswordEncrypt($data){
		$rv = '';
		
		if($this->getSslPassword() && $this->getSslPasswordNode()){
			$password = $this->getSslPassword().'_'.$this->getSslPasswordNode();
			#$this->log('debug', 'password: '.$password);
			
			if(openssl_sign($data, $sign, $this->getSsl(), OPENSSL_ALGO_SHA1)){
				$sign = base64_encode($sign);
				$data = base64_encode($data);
				
				$jsonStr = json_encode(array('data' => $data, 'sign' => $sign));
				$data = gzencode($jsonStr, 9);
				
				$iv = substr(hash('sha512', mt_rand(0, 999999), true), 0, 16);
				$data = openssl_encrypt($data, 'AES-256-CBC', $password, 0, $iv);
				if($data !== false){
					$iv = base64_encode($iv);
					
					$data = gzencode( json_encode(array('data' => $data, 'iv' => $iv)) , 9);
					$rv = base64_encode($data);
				}
				#else{ $this->log('warning', 'sslPasswordEncrypt openssl_encrypt failed'); }
			}
		}
		#else{ $this->log('warning', 'sslPasswordEncrypt no passwords set'); }
		
		return $rv;
	}
	
	public function sslPasswordDecrypt($data){
		$rv = '';
		
		if($this->getSslPassword() && $this->getSslPasswordNode()){
			$password = $this->getSslPasswordNode().'_'.$this->getSslPassword();
			#$this->log('debug', 'password: '.$password);
			
			$data = base64_decode($data);
			$json = json_decode(gzdecode($data), true);
			
			$data = $json['data'];
			$iv = base64_decode($json['iv']);
			
			$data = openssl_decrypt($data, 'AES-256-CBC', $password, 0, $iv);
			if($data !== false){
				$json = json_decode(gzdecode($data), true);
				
				$data = base64_decode($json['data']);
				$sign = base64_decode($json['sign']);
				
				if(openssl_verify($data, $sign, $this->getNode()->getSslKeyPub(), OPENSSL_ALGO_SHA1)){
					$rv = $data;
				}
				else{ $this->log('warning', 'sslPasswordDecrypt openssl_verify failed'); }
			}
			else{ $this->log('warning', 'sslPasswordDecrypt openssl_decrypt failed'); }
		}
		else{ $this->log('warning', 'sslPasswordDecrypt no passwords set'); }
		
		return $rv;
	}
	
	public function socketSendMsg($msg){
		$status = Socket::getPeerName($this->getSocket(), $ip, $port);
		
		if($status){
			$this->log('debug', 'socket send '.$ip.':'.$port.': "'.$msg.'"');
			$this->socketSendRaw($msg."\n");
		}
	}
	
	public function socketSendRaw($data){
		$dataLen = strlen($data);
		
		if($data && $dataLen && $this->getSocketConnected()){
			$bytes = 0;
			try{
				$bytes = Socket::write($this->getSocket(), $data, $dataLen);
				#if($bytes === false){ $this->log('warning', 'socket_write: '.Socket::lastErrorByLastError($this->getSocket()) ); }
			}
			catch(Exception $e){
				$this->log('error', $e->getMessage());
			}
		}
	}
	
	public function handleLine($line){
		$breakit = false;
		
		if($line && strlen($line)){
			
			#$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' raw: "'.$line.'"');
			
			if($line == 'NOP'){
				// No operation
			}
			elseif($line == 'PING'){
				$this->sendPong();
				if($this->nodeIsSet()){
					$this->getNode()->setTimeLastSeen(time());
				}
			}
			elseif(substr($line, 0, 5) == 'PING '){
				$data = substr($line, 5);
				$this->sendPong($data);
				
				if($this->nodeIsSet()){
					$this->getNode()->setTimeLastSeen(time());
				}
			}
			elseif($line == 'PONG'){
				/*if($this->getIsOnlyPing()){
					$this->setIsOnlyPingPinged(true);
				}*/
			}
			elseif($line == 'HELLO' || substr($line, 0, 6) == 'HELLO '){
				$data = substr($line, 6);
				if(!$this->settings['phpchat']['node']['addr_pub'] && $data != '127.0.0.1'){
					# TODO: add private space: 10.0.x.x, 192.168.x.x, ...etc
					
					$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' HELLO');
					
					$this->settings['phpchat']['node']['addr_pub'] = $data;
					$this->settings['tmp']['node']->setIp($data);
				}
				
				$this->sendId();
			}
			elseif(substr($line, 0, 3) == 'ID '){
				if(!$this->getHasId()){
					$data = substr($line, 3);
					
					$json = json_decode($data, true);
					if( $json && isset($json['id']) && isset($json['port']) && isset($json['sslKeyPubFingerprint'])
						&& isset($json['sslKeyPub'])
						&& Node::sslKeyPubFingerprintVerify($json['sslKeyPubFingerprint'])
						&& (int)$json['port'] <= 0xffff ){
						
						$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' ID: "'.$json['id'].'", '.$json['port'].', '.$json['sslKeyPubFingerprint']);
						
						$node = new Node();
						$node->setIdHexStr($json['id']);
						$node->setIp($this->getIp());
						$node->setPort($json['port']);
						$node->setSslKeyPub(base64_decode($json['sslKeyPub']));
						$node->setTimeLastSeen(time());
						
						if($node->getSslKeyPubFingerprint() == $json['sslKeyPubFingerprint']){
							
							if(isset($json['isChannel'])){
								#$this->log('debug', 'ID, isChannel: '.(int)$this->getIsChannel().', '.(int)$json['isChannel']);
								$this->setIsChannel($this->getIsChannel() || $json['isChannel']);
							}
							
							if($this->getIsChannel()){
								$this->consoleMsgAdd('New connection: '.$this->getIp().':'.$this->getPort().', ID='.$node->getIdHexStr());
							}
							
							if(!$this->settings['tmp']['node']->isEqual($node)){
								$this->setNode($this->settings['tmp']['table']->nodeEnclose($node));
								$this->setHasId(true);
								
								$this->log('debug', $this->getIp().':'.$this->getPort().' ID: ok');
								$this->bootstrap(true);
							}
							else{
								$this->sendError(120, 'ID');
								$this->bootstrap(false);
							}
						}
						else{
							$this->sendError(280, 'ID');
							$this->bootstrap(false);
						}
					}
					else{
						$this->sendError(900, 'ID');
						$this->bootstrap(false);
					}
				}
				else{
					$this->sendError(110, 'ID');
				}
			}
			elseif(substr($line, 0, 9) == 'SSL_INIT '){
				if($this->getHasId()){
					if(!$this->getHasSslInit()){
						$data = substr($line, 9);
						
						$json = json_decode($data, true);
						if($json && isset($json['keyPub']) && $json['keyPub']){
							
							if(!$this->getHasSslInitSent()){
								$this->sslInit();
							}
							
							$strKeyPub = base64_decode($json['keyPub']);
							$strKeyPubFingerprint = Node::genSslKeyFingerprint($strKeyPub);
							
							$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' SSL_INIT: '.$this->getNode()->getSslKeyPubFingerprint().', '.$strKeyPubFingerprint);
							
							if($this->getNode()->getSslKeyPubFingerprint() == $strKeyPubFingerprint){
								if($this->getNode()->getSslKeyPub()){
									$this->log('debug', 'found old ssl public key');
									
									if( $this->getNode()->getSslKeyPub() == $strKeyPub ){
										$this->log('debug', 'ssl public key ok');
										
										$this->setHasSslInit(true);
										$this->sendSslTest();
										
										if($this->getIsChannel()){
											$this->consoleMsgSslFoundOldPublicKey($this->getNode()->getIdHexStr());
											
											if( $this->settings['tmp']['addressbook']->contactGetByNodeId($this->getNode()->getIdHexStr()) ){
												$this->consoleMsgSslKnownNode();
											}
											else{
												$this->consoleMsgSslWarningNeverConnectedBefore($strKeyPubFingerprint);
											}
										}
									}
									else{
										$this->sendError(230, 'SSL_INIT');
										$this->log('warning', 'ssl public key changed since last handshake');
										if($this->getIsChannel()){
											$this->consoleMsgSslWarningChangedSinceLastHandshake($this->getNode()->getIdHexStr(), $this->getIp().':'.$this->getPort(), $this->getNode()->getSslKeyPubFingerprint(), $strKeyPubFingerprint);
										}
									}
								}
								else{
									$sslPubKey = openssl_pkey_get_public($strKeyPub);
									$sslPubKeyDetails = openssl_pkey_get_details($sslPubKey);
									
									if($sslPubKeyDetails['bits'] >= Settings::SSL_KEY_LEN_MIN){
										$this->log('debug', 'no old ssl public key found. good. set new.');
										if($this->getIsChannel()){
											$this->consoleMsgSslWarningNeverConnectedBefore($strKeyPubFingerprint);
										}
										
										$this->getNode()->setSslKeyPub($strKeyPub);
										
										$this->setHasSslInit(true);
										$this->sendSslTest();
									}
									else{
										$this->sendError(220, 'SSL_INIT');
										$this->log('warning', 'ssl public key too short');
										
										if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
									}
								}
							}
							else{
								$this->sendError(280, 'SSL_INIT');
								$this->log('warning', 'ssl public key verification failed');
								
								if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
							}
							
						}
						else{
							$this->sendError(210, 'SSL_INIT');
							$this->log('warning', 'ssl no public key found');
						}
					}
					else{
						$this->sendError(290, 'SSL_INIT');
						$this->log('warning', 'ssl we already hasSslInit');
					}
				}
				else{
					$this->sendError(100, 'SSL_INIT');
					
					if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
				}
			}
			elseif(substr($line, 0, 9) == 'SSL_TEST '){
				if($this->getHasSslInit() && !$this->getHasSslTest()){
					$data = substr($line, 9);
					
					$data = $this->sslPrivateDecrypt($data);
					if($data){
						$token = $data;
						
						$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' SSL_TEST: '.$token);
						
						$this->setHasSslTest(true);
						$this->sendSslVerify($token);
					}
					else{
						#$this->log('warning', 'SSL_TEST sslPrivateDecrypt failed');
						
						if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
					}
				}
				else{
					#$this->log('warning', 'ssl test: failed');
				}
			}
			elseif(substr($line, 0, 11) == 'SSL_VERIFY '){
				if($this->getHasSslTest() && !$this->getHasSslVerified()){
					$data = substr($line, 11);
					
					$data = $this->sslPrivateDecrypt($data);
					if($data){
						$token = $data;
						
						$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' SSL_VERIFY: '.$token.', '.(int)$this->getIsChannel());
						
						if($this->sslVerifyToken($token)){
							$this->setHasSslVerified(true);
							$this->sendSslPasswordPut();
						}
					}
					else{
						#$this->log('warning', 'SSL_VERIFY sslPrivateDecrypt failed');
						
						if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
					}
				}
				else{
					#$this->log('warning', 'ssl verify: failed');
					
					if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
				}
			}
			elseif(substr($line, 0, 17) == 'SSL_PASSWORD_PUT '){
				if($this->getHasSslVerified() && !$this->getSslPasswordNode()){
					$data = substr($line, 17);
					
					$data = $this->sslPrivateDecrypt($data);
					if($data){
						$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' SSL_PASSWORD_PUT: '.$data);
						
						$this->setSslPasswordNode($data);
						$this->sendSslPasswordTest();
					}
					else{
						#$this->log('warning', 'SSL_PASSWORD_PUT sslPrivateDecrypt failed');
						
						if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
					}
				}
				else{
					#$this->log('warning', 'ssl password put: failed');
					
					if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
				}
			}
			elseif(substr($line, 0, 18) == 'SSL_PASSWORD_TEST '){
				if($this->getSslPasswordNode() && !$this->getHasSslPasswordTest()){
					$data = substr($line, 18);
					
					$data = $this->sslPasswordDecrypt($data);
					if($data){
						$token = $data;
						
						$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' SSL_PASSWORD_TEST: '.$token);
						
						$this->setHasSslPasswordTest(true);
						$this->sendSslPasswordVerify($token);
					}
					else{
						#$this->log('warning', 'SSL_PASSWORD_TEST sslPasswordDecrypt failed');
						
						if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
					}
				}
				else{
					#$this->log('warning', 'ssl password test: failed');
					
					if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
				}
			}
			elseif(substr($line, 0, 20) == 'SSL_PASSWORD_VERIFY '){
				if($this->getHasSslPasswordTest() && !$this->getHasSsl()){
					$data = substr($line, 20);
					
					$data = $this->sslPasswordDecrypt($data);
					if($data){
						$token = $data;
						
						$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' SSL_PASSWORD_TEST: '.$token);
						
						if($this->sslPasswordVerifyToken($token)){
							$this->setHasSsl(true);
							
							if($this->getIsChannel()){ $this->consoleMsgSslOk($this->getNode()->getIdHexStr()); }
						}
						else{
							if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
						}
					}
					else{
						$this->log('warning', 'SSL_PASSWORD_TEST sslPasswordDecrypt failed');
						
						if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
					}
				}
				else{
					$this->log('warning', 'ssl password verify: failed');
					
					if($this->getIsChannel()){ $this->consoleMsgSslFailed(); }
				}
			}
			elseif(substr($line, 0, 16) == 'SSL_KEY_PUB_GET '){
				if($this->getHasId()){
					$data = substr($line, 16);
					
					$json = json_decode($data, true);
					#ve($json);
					
					#print "check ".(int)$json.' '.(int)isset($json['rid']).' '.(int)isset($json['nodeSslKeyPubFingerprint'])."\n";
					
					if( $json && isset($json['rid']) && isset($json['nodeSslKeyPubFingerprint'])
						&& strIsUuid($json['rid']) ){
						
						$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' SSL_KEY_PUB_GET: '.$json['rid'].', '.$json['nodeSslKeyPubFingerprint']);
						
						if(Node::sslKeyPubFingerprintVerify($json['nodeSslKeyPubFingerprint'])){
							$node = $this->settings['tmp']['table']->nodeFindByKeyPubFingerprint($json['nodeSslKeyPubFingerprint']);
							if(is_object($node)){
								$this->sendSslPubKeyPut($json['rid'], $node->getIdHexStr(), $node->getIp(), $node->getPort(), $node->getSslKeyPubFingerprint(), $node->getSslKeyPub());
							}
							else{
								// Not found.
								$this->sendSslPubKeyPut($json['rid']);
							}
						}
						else{
							// Fingerprint not valid.
							$this->sendSslPubKeyPut($json['rid']);
						}
					}
					else{
						$this->sendError(900, 'SSL_KEY_PUB_GET');
					}
				}
				else{
					$this->log('warning', 'ssl key pub get: failed');
				}
			}
			elseif(substr($line, 0, 16) == 'SSL_KEY_PUB_PUT '){
				if($this->getHasId()){
					$data = substr($line, 16);
					
					$json = json_decode($data, true);
					if( $json && isset($json['rid']) && strIsUuid($json['rid']) ){
						
						$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' SSL_KEY_PUB_PUT: '.$json['rid']);
						
						$action = $this->actionSslKeyPublicGetFindByRid($json['rid']);
						if($action){
							
							$nodeSslKeyPub = '';
							$nodeSslKeyPubFingerprint = '';
							if(isset($json['nodeSslKeyPub']) && $json['nodeSslKeyPub']){
								$nodeSslKeyPub = base64_decode($json['nodeSslKeyPub']);
								$nodeSslKeyPubFingerprint = Node::genSslKeyFingerprint($nodeSslKeyPub);
								
								$this->log('debug', 'socket      '.$this->getIp().':'.$this->getPort().' SSL_KEY_PUB_PUT: '.$json['rid'].', '.$nodeSslKeyPubFingerprint);
							}
							
							$node = new Node();
							if(isset($json['nodeId'])){
								$node->setIdHexStr($json['nodeId']);
							}
							if(isset($json['nodeIp'])){
								$node->setIp($json['nodeIp']);
							}
							if(isset($json['nodePort'])){
								$node->setPort($json['nodePort']);
							}
							$node->setTimeLastSeen(time());
							
							if($nodeSslKeyPub){
								if(
									isset($json['nodeSslKeyPubFingerprint'])
									&&    $json['nodeSslKeyPubFingerprint']
									&&    $json['nodeSslKeyPubFingerprint'] == $nodeSslKeyPubFingerprint
									&&  $action['nodeSslKeyPubFingerprint'] == $nodeSslKeyPubFingerprint
								){
									$node->setSslKeyPub($nodeSslKeyPub);
									
									$this->log('debug', 'socket      '.$this->getIp().':'.$this->getPort().' SSL_KEY_PUB_PUT: '.$json['rid'].', set ssl key pub');
								}
							}
							elseif(
								isset($json['nodeSslKeyPubFingerprint'])
								&&    $json['nodeSslKeyPubFingerprint']
								&&    $json['nodeSslKeyPubFingerprint'] == $action['nodeSslKeyPubFingerprint']
								&& Node::sslKeyPubFingerprintVerify($json['nodeSslKeyPubFingerprint'])
							){
								$node->setSslKeyPubFingerprint($json['nodeSslKeyPubFingerprint']);
								
								$this->log('debug', 'socket      '.$this->getIp().':'.$this->getPort().' SSL_KEY_PUB_PUT: '.$json['rid'].', set ssl key pub fingerprint');
							}
							
							$onode = $this->settings['tmp']['table']->nodeFindInBuckets($node);
							if(is_object($onode)){
								$this->log('debug', 'socket      '.$this->getIp().':'.$this->getPort().' SSL_KEY_PUB_PUT: found old node');
								
								if(!$onode->getSslKeyPub() && $node->getSslKeyPub()){
									$onode->setSslKeyPub($node->getSslKeyPub());
									$onode->setDataChanged();
								}
								if(!$onode->getSslKeyPubFingerprint() && $node->getSslKeyPubFingerprint()){
									$onode->setSslKeyPubFingerprint($node->getSslKeyPubFingerprint());
									$onode->setDataChanged();
								}
							}
							else{
								$this->log('debug', 'socket      '.$this->getIp().':'.$this->getPort().' SSL_KEY_PUB_PUT: new node');
								
								$this->settings['tmp']['table']->nodeEnclose($node);
							}
							
							$this->actionRemove($action);
						}
						else{
							$this->log('debug', 'socket      '.$this->getIp().':'.$this->getPort().' SSL_KEY_PUB_PUT: action not found');
						}
					}
					else{
						$this->sendError(900, 'SSL_KEY_PUB_PUT');
					}
				}
				else{
					$this->log('warning', 'ssl key pub put: failed');
				}
			}
			elseif(substr($line, 0, 10) == 'NODE_FIND '){
				if($this->getHasId()){
					$data = substr($line, 10);
					
					$json = json_decode($data, true);
					if($json){
						
						$num = Server::NODES_FIND_NUM;
						if(isset($json['num'])){
							$num = (int)$json['num'];
						}
						
						$rid = '';
						if(isset($json['rid'])){
							$rid = $json['rid'];
						}
						
						$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' NODE_FIND: "'.$rid.'"');
						
						if(isset($json['id'])){
							$node = new Node();
							$node->setIdHexStr($json['id']);
							
							if( $node->isEqual($this->settings['tmp']['node']) ){
								$this->log('debug', 'node find: find myself');
								
								$this->sendNodeFound($rid, array(array()));
							}
							elseif( !$node->isEqual($this->getNode()) && $onode = $this->settings['tmp']['table']->nodeFindInBuckets($node) ){
								$this->log('debug', 'node find: find in buckets');
								
								$this->sendNodeFound($rid, array(array($onode)));
							}
							else{
								$this->log('debug', 'node find: closest to "'.$node->getIdHexStr().'"');
								
								$nodes = $this->settings['tmp']['table']->nodeFindClosest($node, $num);
								foreach($nodes as $cnodeId => $cnode){
									if($cnode->isEqual($this->getNode())){
										unset($nodes[$cnodeId]);
										break;
									}
								}
								
								$this->sendNodeFound($rid, $nodes);
								
							}
						}
					}
				}
				else{
					$this->sendError(100, 'NODE_FIND');
				}
			}
			elseif(substr($line, 0, 11) == 'NODE_FOUND '){
				if($this->getHasId()){
					$data = substr($line, 11);
					
					$json = json_decode($data, true);
					if( $json && isset($json['rid']) ){
						$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' NODE_FOUND: "'.$json['rid'].'"');
						
						$action = null;
						$action = $this->actionNodeFindFindByRid($json['rid']);
						if($action){
							$nodesFoundIds = $action['nodesFoundIds'];
							
							$distanceOld = $action['distance'];
							$ip = ''; $port = 0;
							
							// Find the smallest distance.
							if(isset($json['nodes'])){
								foreach($json['nodes'] as $nodeArId => $nodeAr){
									
									$node = new Node();
									if(isset($nodeAr['id'])){   $node->setIdHexStr($nodeAr['id']); }
									if(isset($nodeAr['ip'])){   $node->setIp($nodeAr['ip']); }
									if(isset($nodeAr['port'])){ $node->setPort($nodeAr['port']); }
									if(isset($nodeAr['sslKeyPub'])){ $node->setSslKeyPub(base64_decode($nodeAr['sslKeyPub'])); }
									$node->setTimeLastSeen(time());
									
									$distanceNew = $this->settings['tmp']['node']->distanceHexStr($node);
									
									$this->log('debug', 'node found: '.$nodeArId.', '.$nodeAr['id'].', do='.$distanceOld.', dn='.$distanceNew);
									
									if(!$this->settings['tmp']['node']->isEqual($node)){
										if($this->settings['phpchat']['node']['addr_pub'] != $node->getIp() || $this->settings['tmp']['node']->getPort() != $node->getPort()){
											if(!in_array($node->getIdHexStr(), $nodesFoundIds->toArray())){
												
												$nodesFoundIds[] = $nodeAr['id'];
												if(count($nodesFoundIds) > static::ACTION_NODE_FIND_MAX_NODE_IDS){
													$tmp = $nodesFoundIds->toArray();
													array_shift($tmp);
													$nodesFoundIds = array($tmp);
												}
												
												if($nodeAr['id'] == $action['nodeId']){
													$this->log('debug', 'node found: find completed');
													$ip = ''; $port = 0;
												}
												else{
													if($distanceOld != $distanceNew){
														$distanceMin = Node::idMinHexStr($distanceOld, $distanceNew);
														if($distanceMin == $distanceNew){
															$distanceOld = $distanceNew;
															$ip = $node->getIp(); $port = $node->getPort();
														}
													}
												}
												
												$this->settings['tmp']['table']->nodeEnclose($node);
											}
											else{ $this->log('debug', 'node found: already known'); }
										}
										else{ $this->log('debug', 'node found: myself, ip:port equal ('.$node->getIp().':'.$node->getPort().')'); }
									}
									else{ $this->log('debug', 'node found: myself, node equal'); }
								}
							}
							
							if($ip){
								// Further search at the nearest node.
								$this->log('debug', 'node found: ip ('.$ip.':'.$port.') ok');
								
								$arguments = array();
								$arguments['nodeId'] = $action['nodeId'];
								$arguments['distance'] = $distanceOld;
								$arguments['nodesFoundIds'] = $nodesFoundIds;
								
								$followupActions = array();
								$followupActions[] = Action::followupCreate('clientActionNodeFindAdd', $arguments);
								
								$this->serverActionConnectAdd($ip, $port, false, $followupActions);
							}
							
							$this->actionRemove($action);
						}
						
						if($this->getIsOnlyNodeFind() && !$this->getIsNetworkBootstrap()){
							$this->setIsOnlyNodeFindFound(true);
						}
						
					}
				}
				else{
					$this->sendError(100, 'NODE_FOUND');
				}
			}
			elseif(substr($line, 0, 13) == 'TALK_REQUEST '){
				if($this->getHasSsl()){
					if($this->getIsChannel()){
						$data = substr($line, 13);
						
						if($data = $this->sslPasswordDecrypt($data)){
							$json = json_decode($data, true);
							
							if($json && isset($json['rid']) && isset($json['userNickname'])){
								$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' TALK_REQUEST: "'.$json['rid'].'", "'.$json['userNickname'].'"');
								
								$userNickname = substr($json['userNickname'], 0, Settings::USER_NICKNAME_LEN_MAX);
								
								$this->consoleTalkRequestAdd($this->getId(), $this->getNode()->getIdHexStr(), $json['rid'], $this->getIp(), $this->getPort(), $userNickname);
							}
							else{
								$this->sendError(900, 'TALK_REQUEST');
								$this->sendQuit();
								$breakit = true;
							}
						}
						else{
							$this->log('warning', 'sslPasswordDecrypt failed');
						}
					}
					else{
						$this->sendError(400, 'TALK_REQUEST');
					}
				}
				else{
					$this->sendError(100, 'TALK_REQUEST');
				}
			}
			elseif(substr($line, 0, 14) == 'TALK_RESPONSE '){
				if($this->getHasSsl()){
					if($this->getIsChannel()){
						$data = substr($line, 14);
						
						if($data = $this->sslPasswordDecrypt($data)){
							$json = json_decode($data, true);
							if($json && isset($json['rid']) && isset($json['status'])){
								$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' TALK_RESPONSE: "'.$json['rid'].'"');
								
								$userNickname = isset($json['userNickname']) ? $json['userNickname'] : '';
								
								if($action = $this->actionTalkRequestFindByRid($json['rid'])){
									$this->actionRemove($action);
								}
								
								if($json['status'] == 1){
									$this->consoleSetModeChannel(true);
									$this->consoleSetChannelServerClientId($this->getId());
									$this->consoleMsgAdd($this->getIp().':'.$this->getPort().' accepted your talk request.'.PHP_EOL.'Now talking to "'.$userNickname.'".');
									
									$this->settings['tmp']['addressbook']->contactAdd($this->getNode()->getIdHexStr(), $userNickname);
								}
								elseif($json['status'] == 2){
									$this->consoleMsgAdd($this->getIp().':'.$this->getPort().' declined your talk request.');
									
									$this->sendQuit();
									$breakit = true;
								}
								elseif($json['status'] == 3){
									$this->consoleMsgAdd('Talk request to '.$this->getIp().':'.$this->getPort().' timed out.');
									
									$this->sendQuit();
									$breakit = true;
								}
								
							}
							else{
								$this->sendError(900, 'TALK_RESPONSE');
								$this->sendQuit();
								$breakit = true;
							}
						}
					}
					else{
						$this->sendError(400, 'TALK_RESPONSE');
					}
				}
				else{
					$this->sendError(100, 'TALK_RESPONSE');
				}
			}
			elseif(substr($line, 0, 9) == 'TALK_MSG '){
				if($this->getHasSsl()){
					if($this->getIsChannel()){
						$data = substr($line, 9);
						
						if($data = $this->sslPasswordDecrypt($data)){
							$json = json_decode($data, true);
							if($json && isset($json['ignore']) && isset($json['userNickname']) && isset($json['text'])){
								$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' TALK_MSG: '.(int)$json['ignore']);
								
								if(!$json['ignore']){
									$this->consoleTalkMsgRcev(substr($json['userNickname'], 0, Settings::USER_NICKNAME_LEN_MAX), $json['text']);
								}
							}
							else{
								$this->sendError(900, 'TALK_MSG');
								$this->sendQuit();
								$breakit = true;
							}
						}
					}
					else{
						$this->sendError(400, 'TALK_MSG');
					}
				}
				else{
					$this->sendError(100, 'TALK_MSG');
				}
			}
			elseif(substr($line, 0, 11) == 'TALK_CLOSE '){
				if($this->getHasSsl()){
					if($this->getIsChannel()){
						$data = substr($line, 11);
						
						$json = json_decode($data, true);
						if($json && isset($json['userNickname'])){
							$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' TALK_CLOSE');
							
							$this->setIsChannel(false);
							$this->consoleSetModeChannel(false);
							$this->consoleSetChannelServerClientId(0);
							$this->consoleMsgAdd('Closed talk to "'.$json['userNickname'].'".');
							
							$this->sendQuit();
							$breakit = true;
						}
						else{
							$this->sendError(900, 'TALK_CLOSE');
							$this->sendQuit();
							$breakit = true;
						}
					}
					else{
						$this->sendError(400, 'TALK_CLOSE');
					}
				}
				else{
					$this->sendError(100, 'TALK_CLOSE');
				}
			}
			elseif(substr($line, 0, 6) == 'ERROR '){
				$data = substr($line, 6);
				$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' ERROR: "'.$data.'"');
				
				if($this->getIsChannel()){
					$this->consoleMsgAdd('Received ERROR: '.$data);
				}
			}
			elseif($line == 'EXIT' || $line == 'QUIT'){
				$this->log('debug', 'socket rcev '.$this->getIp().':'.$this->getPort().' QUIT');
				
				$breakit = true;
			}
			
		}
		
		return $breakit;
	}
	
	public function bootstrap($success = true){
		$this->log('debug', $this->getIp().':'.$this->getPort().' bootstrap: '.($success ? 'ok' : 'failed'));
		
		$this->setHasBootstrapped(true);
		$this->setBootstrapSuccess($success);
		
		if($success){
			if($this->settings['phpchat']['isBootstrap']){
				$this->settings['phpchat']['isBootstrap'] = false;
				
				$this->log('debug', 'network bootstrap');
				
				$this->setIsNetworkBootstrap(true);
				
				$this->actionNodeFindAdd($this->settings['tmp']['node']->getIdHexStr());
			}
			
			if(!$this->getNode()->getSslKeyPub()){
				$this->actionSslKeyPublicGetAdd($this->getNode()->getSslKeyPubFingerprint());
			}
		}
	}
	
	public function actionAdd($action){
		$this->actionsIdInc();
		
		$action['id'] = $this->getActionsId();
		
		$this->actions[$action['id']] = $action;
	}
	
	public function actionNodeFindAdd($nodeId, $distance = null, $nodesFoundIds = null){
		$this->log('debug', 'client action, node find: '.$nodeId);
		
		if(!$distance){
			$distance = 'ffffffff-ffff-4fff-bfff-ffffffffffff';
		}
		if(!is_object($nodesFoundIds)){
			$nodesFoundIds = array();
		}
		
		$action = array();
		$action['name'] = 'nodeFind';
		$action['exec'] = false;
		$action['timeCreated'] = time();
		
		$action['rid'] = (string)Uuid::uuid4(); // Reference ID.
		$action['nodeId'] = $nodeId;
		$action['distance'] = $distance;
		$action['nodesFoundIds'] = $nodesFoundIds;
		
		$this->actionAdd($action);
	}
	
	public function actionNodeFindFindByRid($rid){
		foreach($this->getActions() as $actionId => $action){
			if($action['name'] == 'nodeFind' && $action['rid'] == $rid){
				return $action;
			}
		}
		
		return null;
	}
	
	public function actionSslInitAdd(){
		$this->log('debug', 'client action, ssl init');
		
		$action = array();
		$action['name'] = 'sslInit';
		$action['exec'] = false;
		$action['timeCreated'] = time();
		
		$this->actionAdd($action);
	}
	
	public function actionTalkRequestAdd(){
		$this->log('debug', 'client action, talk request');
		
		$action = array();
		$action['name'] = 'talkRequest';
		$action['exec'] = false;
		$action['timeCreated'] = time();
		
		$action['rid'] = (string)Uuid::uuid4(); // Reference ID.
		
		$this->actionAdd($action);
	}
	
	public function actionTalkRequestFindByRid($rid){
		foreach($this->getActions() as $actionId => $action){
			if($action['name'] == 'talkRequest' && $action['rid'] == $rid){
				return $action;
			}
		}
		
		return null;
	}
	
	public function actionSslKeyPublicGetAdd($nodeSslKeyPubFingerprint){
		$this->log('debug', 'client action, ssl key pub');
		
		$action = array();
		$action['name'] = 'sslKeyPubGet';
		$action['exec'] = false;
		$action['timeCreated'] = time();
		
		$action['rid'] = (string)Uuid::uuid4(); // Reference ID.
		$action['nodeSslKeyPubFingerprint'] = $nodeSslKeyPubFingerprint;
		
		$this->actionAdd($action);
	}
	
	public function actionSslKeyPublicGetFindByRid($rid){
		foreach($this->getActions() as $actionId => $action){
			if($action['name'] == 'sslKeyPubGet' && $action['rid'] == $rid){
				return $action;
			}
		}
		
		return null;
	}
	
	public function serverActionConnectAdd($ip, $port, $isChannel, $followupActions){
		if($this->getServer()){
			$this->getServer()->actionConnectAdd($ip, $port, $isChannel, $followupActions);
		}
	}
	
	public function actionsExec(){
		$rv = false;
		
		foreach($this->getActions() as $actionId => $action){
			#$this->log('debug', 'client '.$this->getId().' action: '.$actionId.', name='.$action['name'].', getHasId='.(int)$this->getHasId().', getHasSsl='.(int)$this->getHasSsl());
			
			if($this->getHasId() && $action['name'] == 'nodeFind'){
				if(!$action['exec']){
					$action['exec'] = true;
					
					$this->log('debug', 'client action, node find: '.$action['id'].', '.$action['nodeId']);
					
					$this->sendNodeFind($action['rid'], $action['nodeId']);
				}
				if($action['timeCreated'] < time() - static::ACTION_NODE_FIND_TTL){
					$this->log('debug', 'client action, node find TTL: '.$action['id'].', '.$action['nodeId']);
					$this->actionRemove($action);
				}
			}
			elseif($this->getHasId() && $action['name'] == 'sslInit'){
				if(!$action['exec']){
					$action['exec'] = true;
					
					$this->log('debug', 'client action, ssl init: '.$action['id'].', '.$action['nodeId']);
					
					$this->sslInit();
					$this->actionRemove($action);
				}
				if($action['timeCreated'] < time() - static::ACTION_SSL_INIT_TTL){
					$this->log('debug', 'client action, node find TTL: '.$action['id'].', '.$action['nodeId']);
					$this->actionRemove($action);
				}
			}
			elseif($this->getHasId() && $action['name'] == 'sslKeyPubGet'){
				if(!$action['exec']){
					$action['exec'] = true;
					
					$this->sendSslPubKeyGet($action['rid'], $action['nodeSslKeyPubFingerprint']);
					#$this->actionRemove($action);
				}
				if($action['timeCreated'] < time() - static::ACTION_SSL_KEY_PUB_GET_TTL){
					$this->log('debug', 'client action, talk msg TTL: '.$action['id']);
					$this->actionRemove($action);
				}
			}
			elseif($this->getHasSsl() && $action['name'] == 'talkRequest'){
				if(!$action['exec']){
					$action['exec'] = true;
					
					$this->log('debug', 'client action, talk request: '.$action['id']);
					
					$this->sendTalkRequest($action['rid'], $this->settings['phpchat']['user']['nickname']);
					
					$this->consoleMsgAdd('Waiting for '.$this->getIp().':'.$this->getPort().' to accept.', true);
				}
				if($action['timeCreated'] < time() - static::ACTION_TALK_REQUEST_TTL){
					$this->log('debug', 'client action, talk request TTL: '.$action['id']);
					$this->actionRemove($action);
				}
			}
			elseif($this->getHasSsl() && $action['name'] == 'talkResponse'){
				if(!$action['exec']){
					$action['exec'] = true;
					
					$this->log('debug', 'client action, talk response: '.$action['id']);
					
					$this->sendTalkResponse($action['rid'], $action['status'], $action['userNickname']);
					$this->actionRemove($action);
				}
				if($action['timeCreated'] < time() - static::ACTION_TALK_RESPONSE_TTL){
					$this->log('debug', 'client action, talk response TTL: '.$action['id']);
					$this->actionRemove($action);
				}
			}
			elseif($this->getHasSsl() && $action['name'] == 'talkMsg'){
				if(!$action['exec']){
					$action['exec'] = true;
					
					$this->sendTalkMsg($action['ignore'], $action['userNickname'], $action['text']);
					$this->actionRemove($action);
				}
				if($action['timeCreated'] < time() - static::ACTION_TALK_MSG_TTL){
					$this->log('debug', 'client action, talk msg TTL: '.$action['id']);
					$this->actionRemove($action);
				}
			}
			elseif($this->getHasSsl() && $action['name'] == 'talkClose'){
				if(!$action['exec']){
					$action['exec'] = true;
					
					$this->sendTalkClose($action['userNickname']);
					$this->actionRemove($action);
					
					$this->sendQuit();
					$rv = true;
					break;
				}
				if($action['timeCreated'] < time() - static::ACTION_TALK_CLOSE_TTL){
					$this->log('debug', 'client action, talk close TTL: '.$action['id']);
					$this->actionRemove($action);
				}
			}
		}
		
		return $rv;
	}
	
	
	
	/*public function consoleMsgAdd($text, $modeRead = true){
		if(isset($this->settings['tmp']['console'])){
			$this->settings['tmp']['console']->msgAdd($text, $modeRead);
		}
	}
	
	public function consoleMsgSslOk($id){
		$this->consoleMsgAdd('SSL connection to node ID='.$id.' established.');
	}
	
	public function consoleMsgSslFailed(){
		$this->consoleMsgAdd('SSL connection failed.');
	}
	
	public function consoleMsgSslWarningNeverConnectedBefore($fingerprint){
		$this->consoleMsgAdd('SECURITY WARNING: You never connected to this node before.'.PHP_EOL.'Exchange the public key fingerprint on another channel:'.PHP_EOL.'  Your public key fingerprint: '.$this->settings['tmp']['node']->getSslKeyPubFingerprint().PHP_EOL.'  Peer public key fingerprint: '.$fingerprint);
	}
	
	public function consoleMsgSslWarningChangedSinceLastHandshake($nodeId, $ipPort, $fpOld, $fpNew){
		$this->consoleMsgAdd('SECURITY WARNING: The public key for '.$nodeId.' ('.$ipPort.') changed since last handshake.'.PHP_EOL.'Maybe this connection is compromised.'.PHP_EOL.'  Peer old fingerprint: '.$fpOld.PHP_EOL.'  Peer new fingerprint: '.$fpNew);
	}
	
	public function consoleMsgSslKnownNode(){
		$this->consoleMsgAdd('Known node: you already talked to this node once before.');
	}
	
	public function consoleMsgSslFoundOldPublicKey($nodeId){
		$this->consoleMsgAdd('Found old public key for node ID='.$nodeId.'.');
	}
	
	public function consoleSetModeChannel($modeChannel = true){
		if(isset($this->settings['tmp']['console'])){
			$this->settings['tmp']['console']->setModeChannel($modeChannel);
		}
	}
	
	public function consoleSetChannelServerClientId($clientId){
		if(isset($this->settings['tmp']['console'])){
			$this->settings['tmp']['console']->setChannelServerClientId($clientId);
		}
	}
	
	public function consoleTalkRequestAdd($clientId, $nodeId, $rid, $ip, $port, $userNickname){
		if(isset($this->settings['tmp']['console'])){
			$this->settings['tmp']['console']->talkRequestAdd($clientId, $nodeId, $rid, $ip, $port, $userNickname);
		}
	}
	
	public function consoleTalkMsgRcev($userNickname, $text){
		if(isset($this->settings['tmp']['console'])){
			$this->settings['tmp']['console']->channelMsgRcev($userNickname, $text);
		}
	}*/
	
}
