<?php

namespace TheFox\PhpChat;

use Exception;
use RuntimeException;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Utilities\Hex;
use TheFox\Network\AbstractSocket;
use TheFox\Dht\Kademlia\Node;

class Client{
	
	const MSG_SEPARATOR = "\n";
	const NODE_FIND_NUM = 8;
	const NODE_FIND_MAX_NODE_IDS = 1024;
	const PING_TTL = 25;
	const PONG_TTL = 30;
	
	private $id = 0;
	private $status = array();
	
	private $server = null;
	private $socket = null;
	private $node = null;
	private $ip = '';
	private $port = 0;
	private $ssl = null;
	private $sslTestToken = '';
	private $sslPasswordToken = '';
	private $sslPasswordLocal = '';
	private $sslPasswordPeer = '';
	
	private $recvBufferTmp = '';
	private $requestsId = 0;
	private $requests = array();
	private $actionsId = 0;
	private $actions = array();
	private $pingTime = 0;
	private $pongTime = 0;
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->status['hasShutdown'] = false;
		$this->status['isChannelLocal'] = false;
		$this->status['isChannelPeer'] = false;
		
		$this->status['hasId'] = false;
		$this->status['hasSslInit'] = false;
		$this->status['hasSendSslInit'] = false;
		$this->status['hasSslInitOk'] = false;
		$this->status['hasSslTest'] = false;
		$this->status['hasSslVerify'] = false;
		$this->status['hasSslPasswortPut'] = false;
		$this->status['hasSslPasswortTest'] = false;
		$this->status['hasSslPasswortVerify'] = false;
		$this->status['hasSsl'] = false;
	}
	
	public function __sleep(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		return array('id', 'ip', 'port', 'node');
	}
	
	public function __destruct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function getStatus($name){
		if(array_key_exists($name, $this->status)){
			return $this->status[$name];
		}
		return null;
	}
	
	public function setStatus($name, $value){
		$this->status[$name] = $value;
	}
	
	public function setServer(Server $server){
		$this->server = $server;
	}
	
	public function getServer(){
		return $this->server;
	}
	
	public function setSocket(AbstractSocket $socket){
		$this->socket = $socket;
	}
	
	public function getSocket(){
		return $this->socket;
	}
	
	public function setNode(Node $node){
		$this->node = $node;
	}
	
	public function getNode(){
		return $this->node;
	}
	
	public function setIp($ip){
		$this->ip = $ip;
	}
	
	public function getIp(){
		if(!$this->ip){
			$this->setIpPort();
		}
		return $this->ip;
	}
	
	public function setPort($port){
		$this->port = $port;
	}
	
	public function getPort(){
		if(!$this->port){
			$this->setIpPort();
		}
		return $this->port;
	}
	
	public function setIpPort($ip = '', $port = 0){
		$this->getSocket()->getPeerName($ip, $port);
		$this->setIp($ip);
		$this->setPort($port);
	}
	
	public function getIpPort(){
		return $this->getIp().':'.$this->getPort();
	}
	
	public function setSsl($ssl){
		$this->ssl = $ssl;
	}
	
	public function getSsl(){
		return $this->ssl;
	}
	
	public function setSslPrv($sslKeyPrvPath, $sslKeyPrvPass){
		$this->setSsl(openssl_pkey_get_private(file_get_contents($sslKeyPrvPath), $sslKeyPrvPass));
	}
	
	public function getLocalNode(){
		if($this->getServer()){
			return $this->getServer()->getLocalNode();
		}
		return null;
	}
	
	public function getSettings(){
		if($this->getServer()){
			return $this->getServer()->getSettings();
		}
		
		return null;
	}
	
	private function getLog(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if($this->getServer()){
			return $this->getServer()->getLog();
		}
		
		return null;
	}
	
	private function log($level, $msg){
		#print __CLASS__.'->'.__FUNCTION__.': '.$level.', '.$msg."\n";
		
		if($this->getLog()){
			if(method_exists($this->getLog(), $level)){
				$this->getLog()->$level($msg);
			}
		}
	}
	
	private function getTable(){
		if($this->getServer()){
			return $this->getServer()->getTable();
		}
		
		return null;
	}
	
	private function requestAdd($name, $rid, $data = array()){
		$this->requestsId++;
		
		$request = array(
			'id' => $this->requestsId,
			'name' => $name,
			'rid' => $rid,
			'data' => $data,
		);
		
		$this->requests[$this->requestsId] = $request;
		
		return $request;
	}
	
	private function requestGetByRid($rid){
		foreach($this->requests as $requestId => $request){
			if($request['rid'] == $rid){
				return $request;
			}
		}
		return null;
	}
	
	private function requestRemove($request){
		unset($this->requests[$request['id']]);
	}
	
	public function actionAdd(ClientAction $action){
		$this->actionsId++;
		
		$action->setId($this->actionsId);
		
		$this->actions[$this->actionsId] = $action;
	}
	
	private function actionsGetByCriterion($criterion){
		$rv = array();
		foreach($this->actions as $actionsId => $action){
			if($action->hasCriterion($criterion)){
				$rv[] = $action;
			}
		}
		return $rv;
	}
	
	public function actionRemove(ClientAction $action){
		unset($this->actions[$action->getId()]);
	}
	
	public function run(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->checkPingSend();
		$this->checkPongTimeout();
	}
	
	private function checkPingSend(){
		if($this->pingTime < time() - static::PING_TTL){
			#print __CLASS__.'->'.__FUNCTION__.''."\n";
			
			$this->sendPing();
		}
	}
	
	private function checkPongTimeout(){
		if(!$this->pongTime){
			#print __CLASS__.'->'.__FUNCTION__.': set pong time'."\n";
			$this->pongTime = time();
		}
		#print __CLASS__.'->'.__FUNCTION__.': check '.( time() - static::PONG_TTL - $this->pongTime )."\n";
		if($this->pongTime < time() - static::PONG_TTL){
			#print __CLASS__.'->'.__FUNCTION__.': shutdown'."\n";
			$this->sendQuit();
			$this->shutdown();
		}
	}
	
	public function dataRecv(){
		$data = $this->getSocket()->read();
		
		do{
			$separatorPos = strpos($data, static::MSG_SEPARATOR);
			if($separatorPos === false){
				$this->recvBufferTmp .= $data;
				$data = '';
			}
			else{
				$msg = $this->recvBufferTmp.substr($data, 0, $separatorPos);
				$this->recvBufferTmp = '';
				
				$this->msgHandle($msg);
				
				$data = substr($data, $separatorPos + 1);
			}
		}while($data);
	}
	
	private function msgHandle($msgRaw){
		$msg = json_decode(base64_decode($msgRaw), true);
		
		$msgName = '';
		$msgData = array();
		if($msg){
			$msgName = strtolower($msg['name']);
			if(array_key_exists('data', $msg)){
				$msgData = $msg['data'];
			}
		}
		else{
			#$this->log('error', 'json_decode failed: "'.$msgRaw.'"');
			$this->log('error', 'json_decode failed: "'.base64_decode($msgRaw).'"');
			#$this->log('error', 'json_decode failed');
		}
		
		#print __CLASS__.'->'.__FUNCTION__.': '.$this->getIp().':'.$this->getPort().' raw: '.$msgRaw."\n";
		#print __CLASS__.'->'.__FUNCTION__.': '.$this->getIp().':'.$this->getPort().' raw: '.$msgRaw."\n";
		#print __CLASS__.'->'.__FUNCTION__.': "'.$msgName.'"'."\n";
		#print __CLASS__.'->'.__FUNCTION__.': "'.$msgName.'", '.json_encode($msg['data'])."\n";
		
		if($msgName == 'nop'){}
		elseif($msgName == 'test'){
			$len = 0;
			$test_data = 'N/A';
			if(array_key_exists('len', $msgData)){
				$len = (int)$msgData['len'];
			}
			if(array_key_exists('test_data', $msgData)){
				$test_data = $msgData['test_data'];
			}
			
			#print __CLASS__.'->'.__FUNCTION__.': '.$msgName.', '.$len.' == '.strlen($test_data).', "'.substr($test_data, 0, 10).'", "'.substr($test_data, -10).'"'."\n";
		}
		elseif($msgName == 'hello'){
			if(array_key_exists('ip', $msgData)){
				$ip = $msgData['ip'];
				if($ip != '127.0.0.1' && strIsIp($ip) && $this->getSettings()){
					$this->getSettings()->data['node']['ipPub'] = $ip;
					$this->getSettings()->setDataChanged(true);
				}
			}
			
			$actions = $this->actionsGetByCriterion(ClientAction::CRITERION_AFTER_HELLO);
			foreach($actions as $actionsId => $action){
				$this->actionRemove($action);
				$action->functionExec($this);
				
				#print __CLASS__.'->'.__FUNCTION__.': action CRITERION_AFTER_HELLO'."\n";
				#ve($action);
			}
			
			$this->sendId();
		}
		elseif($msgName == 'id'){
			#print __CLASS__.'->'.__FUNCTION__.': '.$msgName.', '.(int)$this->getStatus('hasId')."\n";
			
			if($this->getTable()){
				if(!$this->getStatus('hasId')){
					$release = 0;
					$id = '';
					$port = 0;
					$strKeyPub = '';
					$strKeyPubFingerprint = '';
					$isChannelPeer = false;
					if(array_key_exists('release', $msgData)){
						$release = (int)$msgData['release'];
					}
					if(array_key_exists('id', $msgData)){
						$id = $msgData['id'];
					}
					if(array_key_exists('port', $msgData)){
						$port = (int)$msgData['port'];
					}
					if(array_key_exists('sslKeyPub', $msgData)){
						$strKeyPub = base64_decode($msgData['sslKeyPub']);
						$strKeyPubFingerprint = Node::genSslKeyFingerprint($strKeyPub);
					}
					if(array_key_exists('isChannel', $msgData)){ // isChannelPeer
						$isChannelPeer = (bool)$msgData['isChannel'];
					}
					
					if($isChannelPeer){
						$this->setStatus('isChannelPeer', true);
					}
					
					$idOk = false;
					$node = new Node();
					
					if(strIsUuid($id)){
						if($strKeyPub){
							$node->setIdHexStr($id);
							$node->setIp($this->getIp());
							$node->setPort($port);
							$node->setTimeLastSeen(time());
							
							$node = $this->getTable()->nodeEnclose($node);
							
							if(! $this->getLocalNode()->isEqual($node)){
								if($node->getSslKeyPub()){
									#$this->log('debug', 'found old ssl public key');
									
									if( $node->getSslKeyPub() == $strKeyPub ){
										#$this->log('debug', 'ssl public key ok');
										
										$idOk = true;
									}
									else{
										$this->sendError(230, $msgName);
										#$this->log('warning', 'ssl public key changed since last handshake');
									}
								}
								else{
									$sslPubKey = openssl_pkey_get_public($strKeyPub);
									if($sslPubKey !== false){
										$sslPubKeyDetails = openssl_pkey_get_details($sslPubKey);
										
										if($sslPubKeyDetails['bits'] >= Node::SSL_KEY_LEN_MIN){
											#$this->log('debug', 'no old ssl public key found. good. set new.');
											
											$idOk = true;
										}
										else{
											$this->sendError(220, $msgName);
										}
									}
									else{
										$this->sendError(240, $msgName);
									}
								}
							}
							else{
								$this->sendError(120, $msgName);
							}
						}
						else{
							$this->sendError(200, $msgName);
						}
					}
					else{
						$this->sendError(900, $msgName);
					}
					
					if($idOk){
						$node->setSslKeyPub($strKeyPub);
						
						$this->setStatus('hasId', true);
						$this->setNode($node);
						
						$this->sendIdOk();
						
						#$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$id.', '.$port.', '.$node->getSslKeyPubFingerprint());
						$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$id.', '.$port);
					}
					
				}
				else{
					$this->sendError(110, $msgName);
				}
			}
			else{
				$this->sendError(910, $msgName);
			}
		}
		elseif($msgName == 'id_ok'){
			$this->log('debug', $this->getIpPort().' recv '.$msgName);
			
			if($this->getStatus('hasId')){
				$actions = $this->actionsGetByCriterion(ClientAction::CRITERION_AFTER_ID_OK);
				foreach($actions as $actionsId => $action){
					$this->actionRemove($action);
					$action->functionExec($this);
				}
				
				if($this->getStatus('isChannelPeer')){
					$this->consoleMsgAdd('New incoming channel connection from '.$this->getIpPort().'.');
				}
				
				if($this->getStatus('isChannelPeer') || $this->getStatus('isChannelLocal')){
					if($this->getServer() && $this->getServer()->getKernel()){
						$contact = $this->getServer()->getKernel()->getAddressbook()->contactGetByNodeId($this->getNode()->getIdHexStr());
						if($contact){
							$this->consoleMsgAdd('You talked to '.$this->getNode()->getIdHexStr().' ('.$contact->getUserNickname().') once before.');
						}
						else{
							$this->consoleMsgAdd('You never talked to '.$this->getNode()->getIdHexStr().' before.'.PHP_EOL.'Verify the public keys with you conversation partner on another channel.'.PHP_EOL.'Public keys fingerprints:'.PHP_EOL.'  Yours: '.$this->getLocalNode()->getSslKeyPubFingerprint().PHP_EOL.'  Peers: '.$this->getNode()->getSslKeyPubFingerprint());
						}
					}
				}
				
			}
			else{
				$this->sendError(100, $msgName);
			}
		}
		elseif($msgName == 'node_find'){
			if($this->getStatus('hasId')){
				$rid = '';
				$num = static::NODE_FIND_NUM;
				$nodeId = '';
				if(array_key_exists('rid', $msgData)){
					$rid = $msgData['rid'];
				}
				if(array_key_exists('num', $msgData)){
					$num = $msgData['num'];
				}
				if(array_key_exists('nodeId', $msgData)){
					$nodeId = $msgData['nodeId'];
				}
				
				$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.', '.$nodeId);
				
				if($nodeId){
					$node = new Node();
					$node->setIdHexStr($nodeId);
					
					if( $node->isEqual($this->getLocalNode()) ){
						$this->log('debug', 'node find: find myself');
						
						$this->sendNodeFound($rid);
					}
					elseif( !$node->isEqual($this->getNode()) && $onode = $this->getTable()->nodeFindInBuckets($node) ){
						$this->log('debug', 'node find: find in buckets');
						
						$this->sendNodeFound($rid, array($onode));
					}
					else{
						$this->log('debug', 'node find: closest to "'.$node->getIdHexStr().'"');
						
						$nodes = $this->getTable()->nodeFindClosest($node, $num);
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
			else{
				$this->sendError(100, $msgName);
			}
		}
		elseif($msgName == 'node_found'){
			if($this->getStatus('hasId')){
				$rid = '';
				$nodes = array();
				if(array_key_exists('rid', $msgData)){
					$rid = $msgData['rid'];
				}
				if(array_key_exists('nodes', $msgData)){
					$nodes = $msgData['nodes'];
				}
				
				if($rid){
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid);
					
					$request = $this->requestGetByRid($rid);
					if($request){
						$this->requestRemove($request);
						
						$nodeId = $request['data']['nodeId'];
						$nodesFoundIds = $request['data']['nodesFoundIds'];
						$distanceOld =   $request['data']['distance'];
						$ip = ''; $port = 0;
						
						if($nodes){
							// Find the smallest distance.
							foreach($nodes as $nodeArId => $nodeAr){
								
								$node = new Node();
								if(isset($nodeAr['id'])){
									$node->setIdHexStr($nodeAr['id']);
								}
								if(isset($nodeAr['ip'])){
									$node->setIp($nodeAr['ip']);
								}
								if(isset($nodeAr['port'])){
									$node->setPort($nodeAr['port']);
								}
								if(isset($nodeAr['sslKeyPub'])){
									$node->setSslKeyPub(base64_decode($nodeAr['sslKeyPub']));
								}
								$node->setTimeLastSeen(time());
								
								$distanceNew = $this->getLocalNode()->distanceHexStr($node);
								
								$this->log('debug', 'node found: '.$nodeArId.', '.$nodeAr['id'].', do='.$distanceOld.', dn='.$distanceNew);
								
								if(!$this->getLocalNode()->isEqual($node)){
									if($this->getSettings()->data['node']['ipPub'] != $node->getIp() || $this->getLocalNode()->getPort() != $node->getPort()){
										if(!in_array($node->getIdHexStr(), $nodesFoundIds)){
											
											$nodesFoundIds[] = $nodeAr['id'];
											if(count($nodesFoundIds) > static::NODE_FIND_MAX_NODE_IDS){
												array_shift($nodesFoundIds);
											}
											
											if($nodeAr['id'] == $nodeId){
												$this->log('debug', 'node found: find completed');
												$ip = ''; $port = 0;
											}
											else{
												if($distanceOld != $distanceNew){
													$distanceMin = Node::idMinHexStr($distanceOld, $distanceNew);
													if($distanceMin == $distanceNew){ // Is smaller then $distanceOld.
														$distanceOld = $distanceNew;
														$ip = $node->getIp(); $port = $node->getPort();
													}
												}
											}
											
											$this->getTable()->nodeEnclose($node);
										}
										else{
											$this->log('debug', 'node found: already known');
										}
									}
									else{
										$this->log('debug', 'node found: myself, ip:port equal ('.$node->getIp().':'.$node->getPort().')');
									}
								}
								else{
									$this->log('debug', 'node found: myself, node equal');
								}
							}
						}
						
						if($ip){
							// Further search at the nearest node.
							$this->log('debug', 'node found: ip ('.$ip.':'.$port.') ok');
							
							$clientActions = array();
							$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_OK);
							$action->functionSet(function($client){ $client->sendNodeFind($nodeId, $distanceOld, $nodesFoundIds); });
							$clientActions[] = $action;
							
							$this->getServer()->connect($ip, $port, $clientActions);
						}
					}
					else{
						$this->sendError(900, $msgName);
					}
				}
				else{
					$this->sendError(900, $msgName);
				}
			}
			else{
				$this->sendError(100, $msgName);
			}
		}
		
		elseif($msgName == 'msg'){
			if($this->getStatus('hasId')){
				$rid = '';
				if(array_key_exists('rid', $msgData)){
					$rid = $msgData['rid'];
				}
				
				$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.'');
				
				$this->sendMsgResponse($rid);
				
				
				
			}
			else{
				$this->sendError(100, $msgName);
			}
		}
		elseif($msgName == 'msg_response'){
			if($this->getStatus('hasId')){
				$rid = '';
				if(array_key_exists('rid', $msgData)){
					$rid = $msgData['rid'];
				}
				
				$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.'');
				
			}
			else{
				$this->sendError(100, $msgName);
			}
		}
		
		elseif($msgName == 'ssl_init'){
			if($this->getSsl()){
				if($this->getStatus('hasId')){
					if(!$this->getStatus('hasSslInit')){
						$this->log('debug', 'SSL: init');
						
						$this->setStatus('hasSslInit', true);
						$this->sendSslInit();
						$this->sendSslInitOk();
					}
				}
				else{
					$this->sendError(100, $msgName);
				}
			}
			else{
				$this->sendError(390, $msgName);
			}
		}
		elseif($msgName == 'ssl_init_ok'){
			if($this->getStatus('hasSslInit') && !$this->getStatus('hasSslInitOk')){
				$this->log('debug', 'SSL: init ok');
				
				$this->setStatus('hasSslInitOk', true);
				$this->sendSslTest();
			}
			else{
				$this->sendError(250, $msgName);
				$this->log('warning', $msgName.' SSL: you already initialized ssl');
			}
		}
		elseif($msgName == 'ssl_test'){
			#print __CLASS__.'->'.__FUNCTION__.': "'.$msgName.'", '.(int)$this->getStatus('hasSslInitOk').', '.(int)$this->getStatus('hasSslTest').''."\n";
			
			if($this->getStatus('hasSslInitOk') && !$this->getStatus('hasSslTest')){
				$msgData = $this->sslMsgDataPrivateDecrypt($msgData);
				if($msgData){
					$token = '';
					if(array_key_exists('token', $msgData)){
						$token = $msgData['token'];
					}
					
					if($token){
						$this->log('debug', 'SSL: test');
						
						$this->setStatus('hasSslTest', true);
						$this->sendSslVerify($token);
					}
					else{
						$this->sendError(900, $msgName);
					}
				}
				else{
					$this->sendError(270, $msgName);
					$this->log('warning', $msgName.' SSL: decryption failed');
				}
			}
			else{
				$this->sendError(260, $msgName);
				$this->log('warning', $msgName.' SSL: you need to initialize ssl');
			}
		}
		elseif($msgName == 'ssl_verify'){
			if($this->getStatus('hasSslTest') && !$this->getStatus('hasSslVerify')){
				$msgData = $this->sslMsgDataPrivateDecrypt($msgData);
				if($msgData){
					$token = '';
					if(array_key_exists('token', $msgData)){
						$token = $msgData['token'];
					}
					
					if($token && $this->sslTestToken && $token == $this->sslTestToken){
						$this->log('debug', 'SSL: verified');
						#print __CLASS__.'->'.__FUNCTION__.': '.$msgName.' SSL: verified'."\n";
						
						$this->setStatus('hasSslVerify', true);
						$this->sendSslPasswordPut();
					}
					else{
						$this->sendError(280, $msgName);
					}
				}
				else{
					$this->sendError(270, $msgName);
					$this->log('warning', $msgName.' SSL: decryption failed');
				}
			}
			else{
				$this->sendError(260, $msgName);
				$this->log('warning', $msgName.' SSL: you need to initialize ssl');
			}
		}
		elseif($msgName == 'ssl_password_put'){
			if($this->getStatus('hasSslVerify') && !$this->getStatus('hasSslPasswortPut')){
				$msgData = $this->sslMsgDataPrivateDecrypt($msgData);
				if($msgData){
					$password = '';
					if(array_key_exists('password', $msgData)){
						$password = $msgData['password'];
					}
					
					if($password){
						$this->log('debug', 'SSL: password put');
						
						$this->setStatus('hasSslPasswortPut', true);
						$this->sslPasswordPeer = $password;
						
						$this->sendSslPasswordTest();
					}
					else{
						$this->sendError(900, $msgName);
					}
				}
				else{
					$this->sendError(270, $msgName);
					$this->log('warning', $msgName.' SSL: decryption failed');
				}
			}
			else{
				$this->sendError(260, $msgName);
				$this->log('warning', $msgName.' SSL: you need to initialize ssl');
			}
		}
		elseif($msgName == 'ssl_password_test'){
			if($this->getStatus('hasSslPasswortPut') && !$this->getStatus('hasSslPasswortTest')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData);
				if($msgData){
					$token = '';
					if(array_key_exists('token', $msgData)){
						$token = $msgData['token'];
					}
					
					if($token){
						$this->setStatus('hasSslPasswortTest', true);
						$this->sendSslPasswordVerify($token);
					}
					else{
						$this->sendError(900, $msgName);
					}
				}
				else{
					$this->sendError(270, $msgName);
					$this->log('warning', $msgName.' SSL: decryption failed');
				}
			}
			else{
				$this->sendError(260, $msgName);
				$this->log('warning', $msgName.' SSL: you need to initialize ssl');
			}
		}
		elseif($msgName == 'ssl_password_verify'){
			if($this->getStatus('hasSslPasswortTest') && !$this->getStatus('hasSsl')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData);
				if($msgData){
					$token = '';
					if(array_key_exists('token', $msgData)){
						$token = $msgData['token'];
					}
					
					#print __CLASS__.'->'.__FUNCTION__.': '.$msgName.' SSL: password token: '.$token."\n";
					
					if($token){
						if($this->sslPasswordToken && $token == hash('sha512', $this->sslPasswordToken.'_'.$this->getNode()->getSslKeyPubFingerprint())){
							$this->log('debug', 'SSL: password verified');
							
							$this->setStatus('hasSsl', true);
							
							$actions = $this->actionsGetByCriterion(ClientAction::CRITERION_AFTER_HAS_SSL);
							foreach($actions as $actionsId => $action){
								$this->actionRemove($action);
								$action->functionExec($this);
							}
						}
						else{
							$this->sendError(290, $msgName);
						}
						
						$this->sslTestToken = '';
						$this->sslPasswordToken = '';
					}
					else{
						$this->sendError(900, $msgName);
					}
				}
				else{
					$this->sendError(270, $msgName);
					$this->log('warning', $msgName.' SSL: decryption failed');
				}
			}
			else{
				$this->sendError(260, $msgName);
				$this->log('warning', $msgName.' SSL: you need to initialize ssl');
			}
		}
		elseif($msgName == 'ssl_key_pub_get'){
			if($this->getStatus('hasId')){
				$rid = '';
				$nodeSslKeyPubFingerprint = '';
				if(array_key_exists('rid', $msgData)){
					$rid = $msgData['rid'];
				}
				if(array_key_exists('nodeSslKeyPubFingerprint', $msgData)){
					$nodeSslKeyPubFingerprint = $msgData['nodeSslKeyPubFingerprint'];
				}
				
				$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid);
				
				if(Node::sslKeyPubFingerprintVerify($nodeSslKeyPubFingerprint)){
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': pub key fp ok');
					
					$node = $this->getTable()->nodeFindByKeyPubFingerprint($nodeSslKeyPubFingerprint);
					if($node){
						$this->log('debug', $this->getIpPort().' recv '.$msgName.': found node');
						
						$this->sendSslKeyPubPut($rid, $node->getIdHexStr(), $node->getIp(), $node->getPort(), $node->getSslKeyPubFingerprint(), $node->getSslKeyPub());
					}
					else{
						// Not found.
						$this->sendSslKeyPubPut($rid);
						
						$this->log('debug', $this->getIpPort().' recv '.$msgName.': node not found A');
					}
				}
				else{
					// Fingerprint not valid.
					$this->sendSslKeyPubPut($rid);
					
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': node not found B');
				}
			}
			else{
				$this->sendError(100, $msgName);
			}
		}
		elseif($msgName == 'ssl_key_pub_put'){
			if($this->getStatus('hasId')){
				$rid = '';
				$nodeId = '';
				$nodeIp = '';
				$nodePort = '';
				$nodeSslKeyPubFingerprint = '';
				#$nodeSslKeyPubFingerprintByKeyPub = '';
				$nodeSslKeyPub = '';
				if(array_key_exists('rid', $msgData)){
					$rid = $msgData['rid'];
				}
				if(array_key_exists('nodeId', $msgData)){
					$nodeId = $msgData['nodeId'];
				}
				if(array_key_exists('nodeIp', $msgData)){
					$nodeIp = $msgData['nodeIp'];
				}
				if(array_key_exists('nodePort', $msgData)){
					$nodePort = $msgData['nodePort'];
				}
				if(array_key_exists('nodeSslKeyPubFingerprint', $msgData)){
					$nodeSslKeyPubFingerprint = $msgData['nodeSslKeyPubFingerprint'];
				}
				if(array_key_exists('nodeSslKeyPub', $msgData)){
					$nodeSslKeyPub = base64_decode($msgData['nodeSslKeyPub']);
					#$nodeSslKeyPubFingerprintByKeyPub = Node::genSslKeyFingerprint($nodeSslKeyPub);
				}
				
				$this->log('debug', $this->getIpPort().' recv '.$msgName.': "'.$rid.'" "'.$nodeId.'" "'.$nodeIp.'" "'.$nodePort.'" "'.$nodeSslKeyPubFingerprint.'"');
				
				$request = $this->requestGetByRid($rid);
				if($request){
					$this->requestRemove($request);
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': request '.$request['id']);
					
					if($nodeId){
						$node = new Node();
						$node->setIdHexStr($nodeId);
						$node->setIp($nodeIp);
						$node->setPort($nodePort);
						$node->setSslKeyPub($nodeSslKeyPub);
						$node->setTimeLastSeen(time());
						$node->setDataChanged(true);
						
						if(
							$nodeSslKeyPubFingerprint
							&& $nodeSslKeyPub
							&& Node::sslKeyPubFingerprintVerify($nodeSslKeyPubFingerprint)
							&& $request['data']['nodeSslKeyPubFingerprint'] == $nodeSslKeyPubFingerprint
							&& $node->getSslKeyPubFingerprint() == $nodeSslKeyPubFingerprint
						){
							
							$onode = $this->getTable()->nodeFindInBuckets($node);
							if($onode){
								$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.', old node');
								
								if(!$onode->getSslKeyPub() && $node->getSslKeyPub()){
									$onode->setSslKeyPub($node->getSslKeyPub());
									$onode->setDataChanged(true);
								}
							}
							else{
								$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.', new node');
								
								$this->getTable()->nodeEnclose($node);
							}
							
						}
						else{
							$this->sendError(900, $msgName);
						}
					}
				}
				else{
					$this->sendError(900, $msgName);
				}
			}
			else{
				$this->sendError(100, $msgName);
			}
		}
		
		elseif($msgName == 'talk_request'){
			if($this->getStatus('hasSsl')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData);
				if($msgData){
					$rid = '';
					$userNickname = '';
					if(array_key_exists('rid', $msgData)){
						$rid = $msgData['rid'];
					}
					if(array_key_exists('userNickname', $msgData)){
						$userNickname = $msgData['userNickname'];
					}
					
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.', '.$userNickname);
					
					if($rid){
						if($this->getServer() && $this->getServer()->kernelHasConsole()){
							$this->consoleTalkRequestAdd($rid, $userNickname);
						}
						else{
							$this->sendTalkResponse($rid, 4);
						}
					}
					else{
						$this->sendError(900, $msgName);
					}
				}
				else{
					$this->sendError(900, $msgName);
				}
			}
			else{
				$this->sendError(260, $msgName);
				$this->log('warning', $msgName.' SSL: you need to initialize ssl');
			}
		}
		elseif($msgName == 'talk_response'){
			if($this->getStatus('hasSsl')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData);
				if($msgData){
					$rid = '';
					$status = 0;
					$userNickname = '';
					if(array_key_exists('rid', $msgData)){
						$rid = $msgData['rid'];
					}
					if(array_key_exists('status', $msgData)){
						$status = (int)$msgData['status'];
					}
					if(array_key_exists('userNickname', $msgData)){
						$userNickname = $msgData['userNickname'];
					}
					
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.', '.(int)$status.', '.$userNickname);
					
					$request = $this->requestGetByRid($rid);
					if($request){
						$this->requestRemove($request);
						$this->log('debug', $this->getIpPort().' recv '.$msgName.': request ok');
						
						if($status == 0){
							// Undefined
						}
						elseif($status == 1){
							// Accepted
							$this->consoleMsgAdd('Talk request accepted.'.PHP_EOL.'Now talking to "'.$userNickname.'".');
							$this->consoleSetModeChannel(true);
							$this->consoleSetModeChannelClient($this);
							
							if($this->getServer() && $this->getServer()->getKernel()){
								// Add to addressbook.
								$contact = new Contact();
								$contact->setNodeId($this->getNode()->getIdHexStr());
								$contact->setUserNickname($userNickname);
								
								$this->getServer()->getKernel()->getAddressbook()->contactAdd($contact);
							}
						}
						elseif($status == 2){
							// Declined
							$this->consoleMsgAdd('Talk request declined.');
						}
						elseif($status == 3){
							// Timeout
							$this->consoleMsgAdd('Talk request timed-out.');
						}
						elseif($status == 4){
							// No console, standalone server.
							$this->consoleMsgAdd($this->getIpPort().' has no user interface. Can\'t talk to you.');
						}
					}
					else{
						$this->sendError(900, $msgName);
					}
				}
			}
		}
		elseif($msgName == 'talk_msg'){
			#$this->log('debug', $this->getIpPort().' recv '.$msgName);
			
			if($this->getStatus('hasSsl')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData);
				if($msgData){
					$rid = '';
					$userNickname = '';
					$text = '';
					$ignore = false;
					if(array_key_exists('rid', $msgData)){
						$rid = $msgData['rid'];
					}
					if(array_key_exists('userNickname', $msgData)){
						$userNickname = $msgData['userNickname'];
					}
					if(array_key_exists('text', $msgData)){
						$text = $msgData['text'];
					}
					if(array_key_exists('ignore', $msgData)){
						$ignore = $msgData['ignore'];
					}
					
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.', '.$userNickname.', '. (int)$ignore .', '.$text);
					if(!$ignore){
						$this->consoleTalkMsgAdd($rid, $userNickname, $text);
					}
				}
			}
		}
		elseif($msgName == 'talk_user_nickname_change'){
			#$this->log('debug', $this->getIpPort().' recv '.$msgName);
			
			if($this->getStatus('hasSsl')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData);
				if($msgData){
					$userNicknameOld = '';
					$userNicknameNew = '';
					if(array_key_exists('userNicknameOld', $msgData)){
						$userNicknameOld = $msgData['userNicknameOld'];
					}
					if(array_key_exists('userNicknameNew', $msgData)){
						$userNicknameNew = $msgData['userNicknameNew'];
					}
					
					if($this->getServer() && $this->getServer()->getKernel()){
						$contact = $this->getServer()->getKernel()->getAddressbook()->contactGetByNodeId($this->getNode()->getIdHexStr());
						if($contact){
							if(!$userNicknameOld){
								$userNicknameOld = $contact->getUserNickname();
							}
							
							$contact->setUserNickname($userNicknameNew);
							$this->getServer()->getKernel()->getAddressbook()->setDataChanged(true);
						}
					}
					
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$userNicknameOld.', '.$userNicknameNew);
					$this->consoleMsgAdd('User "'.$userNicknameOld.'" is now known as "'.$userNicknameNew.'".');
				}
			}
		}
		elseif($msgName == 'talk_close'){
			if($this->getStatus('hasSsl')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData);
				if($msgData){
					$rid = '';
					$userNickname = '';
					if(array_key_exists('rid', $msgData)){
						$rid = $msgData['rid'];
					}
					if(array_key_exists('userNickname', $msgData)){
						$userNickname = $msgData['userNickname'];
					}
					
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.', '.$userNickname);
					
					$this->sendQuit();
					
					$this->consoleMsgAdd('Talk closed by "'.$userNickname.'".');
					$this->consoleSetModeChannel(false);
					$this->consoleSetModeChannelClient(null);
				}
			}
		}
		
		elseif($msgName == 'ping'){
			$id = '';
			if(array_key_exists('id', $msgData)){
				$id = $msgData['id'];
			}
			$this->sendPong($id);
		}
		elseif($msgName == 'pong'){
			$id = '';
			if(array_key_exists('id', $msgData)){
				$id = $msgData['id'];
			}
			$this->pongTime = time();
		}
		elseif($msgName == 'error'){
			$code = 0;
			$msg = '';
			$name = '';
			if(array_key_exists('msg', $msgData)){
				$code = (int)$msgData['code'];
			}
			if(array_key_exists('msg', $msgData)){
				$msg = $msgData['msg'];
			}
			if(array_key_exists('msg', $msgData)){
				$name = $msgData['name'];
			}
			
			$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$code.', '.$msg.', '.$name);
		}
		elseif($msgName == 'quit'){
			$this->shutdown();
		}
		else{
			$this->log('debug', $this->getIpPort().' recv '.$msgName.': not implemented.');
		}
	}
	
	private function msgCreate($name, $data){
		#print __CLASS__.'->'.__FUNCTION__.': "'.$name.'"'."\n";
		
		$json = array(
			'name' => $name,
			'data' => $data,
		);
		return json_encode($json);
	}
	
	private function dataSend($msg){
		#print __CLASS__.'->'.__FUNCTION__.': "'.$msg.'"'."\n";
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if($msg){
			$msg = base64_encode($msg);
			$this->getSocket()->write($msg.static::MSG_SEPARATOR);
		}
	}
	
	private function sslMsgCreatePublicEncrypt($name, $data){
		#print __CLASS__.'->'.__FUNCTION__.': "'.$name.'"'."\n";
		#ve($data);
		
		$data = json_encode($data);
		
		#ve($data);
		$dataEnc = $this->sslPublicEncrypt($data);
		
		if($dataEnc){
			#ve($dataEnc);
			
			$json = array(
				'name' => $name,
				'data' => $dataEnc,
			);
			
			#print __CLASS__.'->'.__FUNCTION__.': "'.$name.'", "'.json_encode($json).'"'."\n";
			return json_encode($json);
		}
		
		return null;
	}
	
	private function sslMsgDataPrivateDecrypt($dataEnc){
		$data = $this->sslPrivateDecrypt($dataEnc);
		if($data){
			$data = json_decode($data, true);
			
			return $data;
		}
		
		return null;
	}
	
	private function sslMsgCreatePasswordEncrypt($name, $data){
		#print __CLASS__.'->'.__FUNCTION__.': "'.$name.'"'."\n";
		
		$data = json_encode($data);
		$dataEnc = $this->sslPasswordEncrypt($data);
		
		if($dataEnc){
			$json = array(
				'name' => $name,
				'data' => $dataEnc,
			);
			return json_encode($json);
		}
		
		return null;
	}
	
	private function sslMsgDataPasswordDecrypt($dataEnc){
		$data = $this->sslPasswordDecrypt($dataEnc);
		if($data){
			$data = json_decode($data, true);
			return $data;
		}
		
		return null;
	}
	
	private function sslPublicEncrypt($data){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if(openssl_sign($data, $sign, $this->getSsl(), OPENSSL_ALGO_SHA1)){
			$sign = base64_encode($sign);
			
			if(openssl_public_encrypt($data, $cryped, $this->getNode()->getSslKeyPub())){
				$data = base64_encode($cryped);
				$jsonStr = json_encode(array('data' => $data, 'sign' => $sign));
				$gzdata = gzencode($jsonStr, 9);
				$rv = base64_encode($gzdata);
				
				return $rv;
			}
		}
		
		return null;
	}
	
	private function sslPrivateDecrypt($data){
		$data = base64_decode($data);
		$data = gzdecode($data);
		$json = json_decode($data, true);
		
		$data = base64_decode($json['data']);
		$sign = base64_decode($json['sign']);
		
		if(openssl_private_decrypt($data, $decrypted, $this->getSsl())){
			if(openssl_verify($decrypted, $sign, $this->getNode()->getSslKeyPub(), OPENSSL_ALGO_SHA1)){
				$rv = $decrypted;
				
				return $rv;
			}
		}
		
		return null;
	}
	
	private function sslPasswordEncrypt($data){
		if($this->sslPasswordLocal && $this->sslPasswordPeer){
			$password = $this->sslPasswordLocal.'_'.$this->sslPasswordPeer;
			
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
					
					return $rv;
				}
			}
		}
		
		return null;
	}
	
	private function sslPasswordDecrypt($data){
		if($this->sslPasswordLocal && $this->sslPasswordPeer){
			$password = $this->sslPasswordPeer.'_'.$this->sslPasswordLocal;
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
					return $data;
				}
				else{ $this->log('warning', 'sslPasswordDecrypt openssl_verify failed'); }
			}
			else{ $this->log('warning', 'sslPasswordDecrypt openssl_decrypt failed'); }
		}
		else{ $this->log('warning', 'sslPasswordDecrypt no passwords set'); }
		
		return null;
	}
	
	public function sendNop(){
		$data = array(
		);
		$this->dataSend($this->msgCreate('nop', $data));
	}
	
	public function sendTest(){
		$test_data = 'BEGIN_'.str_repeat('abcdef', 4096).'_END';
		$len = strlen($test_data);
		$data = array(
			'len' => $len,
			'test_data' => $test_data,
		);
		$this->dataSend($this->msgCreate('test', $data));
	}
	
	public function sendHello(){
		$data = array(
			'ip' => $this->getIp(),
		);
		$this->dataSend($this->msgCreate('hello', $data));
	}
	
	private function sendId(){
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$sslKeyPub = base64_encode($this->getLocalNode()->getSslKeyPub());
		
		$data = array(
			'release'   => $this->getSettings()->data['release'],
			'id'        => $this->getLocalNode()->getIdHexStr(),
			'port'      => $this->getLocalNode()->getPort(),
			'sslKeyPub' => $sslKeyPub,
			'isChannel' => $this->getStatus('isChannelLocal'),
		);
		$this->dataSend($this->msgCreate('id', $data));
	}
	
	private function sendIdOk(){
		$data = array(
		);
		$this->dataSend($this->msgCreate('id_ok', $data));
	}
	
	public function sendNodeFind($nodeId, $distance = 'ffffffff-ffff-4fff-bfff-ffffffffffff', $nodesFoundIds = array()){
		if(!$this->getTable()){
			throw new RuntimeException('table not set.');
		}
		
		$rid = (string)Uuid::uuid4();
		
		$this->requestAdd('node_find', $rid, array(
			'nodeId' => $nodeId,
			'distance' => $distance,
			'nodesFoundIds' => $nodesFoundIds,
		));
		
		$data = array(
			'rid'       => $rid,
			'num'       => static::NODE_FIND_NUM,
			'nodeId'    => $nodeId,
		);
		$this->dataSend($this->msgCreate('node_find', $data));
	}
	
	private function sendNodeFound($rid, $nodes = array()){
		if(!$this->getTable()){
			throw new RuntimeException('table not set.');
		}
		
		$nodesOut = array();
		foreach($nodes as $nodeId => $node){
			$nodesOut[] = array(
				'id' => $node->getIdHexStr(),
				'ip' => $node->getIp(),
				'port' => $node->getPort(),
				'sslKeyPub' => base64_encode($node->getSslKeyPub()),
			);
		}
		
		$data = array(
			'rid'       => $rid,
			'nodes'     => $nodesOut,
		);
		$this->dataSend($this->msgCreate('node_found', $data));
	}
	
	private function sendMsg(){
		$rid = (string)Uuid::uuid4();
		
		$data = array(
			'rid' => $rid,
		);
		$this->dataSend($this->msgCreate('msg', $data));
	}
	
	private function sendMsgResponse($rid){
		$data = array(
			'rid' => $rid,
		);
		$this->dataSend($this->msgCreate('msg_response', $data));
	}
	
	public function sendSslInit(){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		if($this->getStatus('hasSendSslInit')){
			$this->setStatus('hasSslInit', true);
		}
		else{
			$this->setStatus('hasSendSslInit', true);
			
			$data = array(
			);
			$this->dataSend($this->msgCreate('ssl_init', $data));
		}
	}
	
	private function sendSslInitOk(){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$data = array(
		);
		$this->dataSend($this->msgCreate('ssl_init_ok', $data));
	}
	
	private function sendSslTest(){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$this->sslTestToken = (string)Uuid::uuid4();
		
		$data = array(
			'token' => $this->sslTestToken,
		);
		$this->dataSend($this->sslMsgCreatePublicEncrypt('ssl_test', $data));
	}
	
	private function sendSslVerify($token){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$data = array(
			'token' => $token,
		);
		$this->dataSend($this->sslMsgCreatePublicEncrypt('ssl_verify', $data));
	}
	
	private function sendSslPasswordPut(){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$addr = $this->getIp().':'.$this->getPort();
		$password = hash('sha512', $addr.'_'.mt_rand(0, 999999));
		
		$this->sslPasswordLocal = $password;
		#$this->log('debug', 'SSL: local password: '.$this->sslPasswordLocal);
		
		$data = array(
			'password' => $password,
		);
		$this->dataSend($this->sslMsgCreatePublicEncrypt('ssl_password_put', $data));
	}
	
	private function sendSslPasswordTest(){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$this->sslPasswordToken = (string)Uuid::uuid4();
		
		$data = array(
			'token' => $this->sslPasswordToken,
		);
		$this->dataSend($this->sslMsgCreatePasswordEncrypt('ssl_password_test', $data));
	}
	
	private function sendSslPasswordVerify($token){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$token = hash('sha512', $token.'_'.$this->getLocalNode()->getSslKeyPubFingerprint());
		
		$data = array(
			'token' => $token,
		);
		$this->dataSend($this->sslMsgCreatePasswordEncrypt('ssl_password_verify', $data));
	}
	
	public function sendSslKeyPubGet($nodeSslKeyPubFingerprint){
		$rid = (string)Uuid::uuid4();
		
		#print __CLASS__.'->'.__FUNCTION__.': '.$rid.', '.$nodeSslKeyPubFingerprint."\n";
		
		$this->requestAdd('ssl_key_pub_get', $rid, array(
			'nodeSslKeyPubFingerprint' => $nodeSslKeyPubFingerprint,
		));
		
		$data = array(
			'rid' => $rid,
			'nodeSslKeyPubFingerprint' => $nodeSslKeyPubFingerprint,
		);
		$this->dataSend($this->msgCreate('ssl_key_pub_get', $data));
	}
	
	private function sendSslKeyPubPut($rid, $nodeId = null, $nodeIp = null, $nodePort = null, $nodeSslKeyPubFingerprint = null, $nodeSslKeyPub = null){
		
		$data = array(
			'rid' => $rid,
			'nodeId' => $nodeId,
			'nodeIp' => $nodeIp,
			'nodePort' => $nodePort,
			'nodeSslKeyPubFingerprint' => $nodeSslKeyPubFingerprint,
			'nodeSslKeyPub' => base64_encode($nodeSslKeyPub),
		);
		$this->dataSend($this->msgCreate('ssl_key_pub_put', $data));
	}
	
	public function sendTalkRequest($userNickname){
		$rid = (string)Uuid::uuid4();
		
		$this->requestAdd('talk_request', $rid, array(
			'userNickname' => $userNickname,
		));
		
		$data = array(
			'rid' => $rid,
			'userNickname' => $userNickname,
		);
		$this->dataSend($this->sslMsgCreatePasswordEncrypt('talk_request', $data));
	}
	
	public function sendTalkResponse($rid, $status, $userNickname = ''){
		$data = array(
			'rid' => $rid,
			'status' => (int)$status,
			'userNickname' => $userNickname,
		);
		$this->dataSend($this->sslMsgCreatePasswordEncrypt('talk_response', $data));
	}
	
	public function sendTalkMsg($rid, $userNickname, $text, $ignore){
		$data = array(
			'rid' => $rid,
			'userNickname' => $userNickname,
			'text' => $text,
			'ignore' => $ignore,
		);
		$this->dataSend($this->sslMsgCreatePasswordEncrypt('talk_msg', $data));
	}
	
	public function sendTalkUserNicknameChange($userNicknameOld, $userNicknameNew){
		$data = array(
			'userNicknameOld' => $userNicknameOld,
			'userNicknameNew' => $userNicknameNew,
		);
		$this->dataSend($this->sslMsgCreatePasswordEncrypt('talk_user_nickname_change', $data));
	}
	
	public function sendTalkClose($rid, $userNickname){
		$data = array(
			'rid' => $rid,
			'userNickname' => $userNickname,
		);
		$this->dataSend($this->sslMsgCreatePasswordEncrypt('talk_close', $data));
	}
	
	private function sendPing($id = ''){
		$this->pingTime = time();
		
		$data = array(
			'id' => $id,
		);
		$this->dataSend($this->msgCreate('ping', $data));
	}
	
	private function sendPong($id = ''){
		$data = array(
			'id' => $id,
		);
		$this->dataSend($this->msgCreate('pong', $data));
	}
	
	private function sendError($errorCode = 999, $msgName = ''){
		$errors = array(
			// 100-199: ID
			100 => 'You need to identify',
			110 => 'You already identified',
			120 => 'You are using my ID',
			
			// 200-399: SSL
			200 => 'SSL: no public key found',
			210 => 'SSL: you need a key with minimum length of '.Node::SSL_KEY_LEN_MIN.' bits',
			220 => 'SSL: public key too short',
			230 => 'SSL: public key changed since last handshake',
			240 => 'SSL: invalid key',
			250 => 'SSL: you already initialized ssl',
			260 => 'SSL: you need to initialize ssl',
			270 => 'SSL: decryption failed',
			280 => 'SSL: verification failed',
			290 => 'SSL: password verification failed',
			390 => 'SSL: invalid setup',
			
			// 900-999: Misc
			900 => 'Invalid data',
			910 => 'Invalid setup',
			999 => 'Unknown error',
		);
		
		if(!isset($errors[$errorCode])){
			throw new RuntimeException('Error '.$errorCode.' not defined.');
		}
		
		$data = array(
			'code'   => $errorCode,
			'msg' => $errors[$errorCode],
			'name' => $msgName,
		);
		$this->dataSend($this->msgCreate('error', $data));
	}
	
	public function sendQuit(){
		$data = array(
		);
		$this->dataSend($this->msgCreate('quit', $data));
	}
	
	public function shutdown(){
		if(!$this->getStatus('hasShutdown')){
			$this->setStatus('hasShutdown', true);
			
			$this->getSocket()->shutdown();
			$this->getSocket()->close();
			
			if($this->ssl){
				openssl_free_key($this->ssl);
			}
		}
	}
	
	private function consoleMsgAdd($msgText){
		if($this->getServer()){
			$this->getServer()->consoleMsgAdd($msgText);
		}
	}
	
	private function consoleTalkRequestAdd($rid, $userNickname){
		if(
			$this->getServer()
			&& $this->getServer()->getKernel()
			&& $this->getServer()->getKernel()->getIpcConsoleConnection()){
			
			$this->getServer()->getKernel()->getIpcConsoleConnection()->execAsync('talkRequestAdd', 
				array($this, $rid, $userNickname));
		}
	}
	
	private function consoleTalkMsgAdd($rid, $userNickname, $text){
		if(
			$this->getServer()
			&& $this->getServer()->getKernel()
			&& $this->getServer()->getKernel()->getIpcConsoleConnection()){
			
			$this->getServer()->getKernel()->getIpcConsoleConnection()->execAsync('talkMsgAdd', array($rid, $userNickname, $text));
		}
	}
	
	private function consoleSetModeChannel($modeChannel){
		if($this->getServer()){
			$this->getServer()->consoleSetModeChannel($modeChannel);
		}
	}
	
	private function consoleSetModeChannelClient($client){
		if($this->getServer()){
			$this->getServer()->consoleSetModeChannelClient($client);
		}
	}
	
}
