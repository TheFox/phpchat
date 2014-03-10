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
	
	private $id = 0;
	private $status = array();
	
	private $server = null;
	private $socket = null;
	private $node = null;
	private $ip = '';
	private $port = 0;
	private $ssl = null;
	
	private $recvBufferTmp = '';
	private $requestsId = 0;
	private $requests = array();
	private $actionsId = 0;
	private $actions = array();
	private $sslTestToken = '';
	private $sslPasswordLocal = '';
	private $sslPasswordPeer = '';
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->status['hasShutdown'] = false;
		$this->status['isChannel'] = false;
		
		$this->status['hasId'] = false;
		$this->status['hasSslInit'] = false;
		$this->status['hasSendSslInit'] = false;
		$this->status['hasSslInitOk'] = false;
		$this->status['hasSslTest'] = false;
		$this->status['hasSslVerify'] = false;
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
			$this->log('error', 'json_decode failed: "'.$msgRaw.'"');
		}
		
		#print __CLASS__.'->'.__FUNCTION__.': '.$this->getIp().':'.$this->getPort().' raw: '.$msgRaw."\n";
		#print __CLASS__.'->'.__FUNCTION__.': '.$this->getIp().':'.$this->getPort().' raw: '.$msgRaw."\n";
		#print __CLASS__.'->'.__FUNCTION__.': "'.$msgName.'"'."\n";
		print __CLASS__.'->'.__FUNCTION__.': "'.$msgName.'", '.json_encode($msg['data'])."\n";
		
		if($msgName == 'nop'){}
		elseif($msgName == 'hello'){
			if(array_key_exists('ip', $msgData)){
				$ip = $msgData['ip'];
				if($ip != '127.0.0.1' && strIsIp($ip) && $this->getSettings()){
					$this->getSettings()->data['node']['ipPub'] = $ip;
					$this->getSettings()->setDataChanged(true);
				}
			}
			
			$this->sendId();
		}
		elseif($msgName == 'id'){
			#print __CLASS__.'->'.__FUNCTION__.': '.$msgName.', '.(int)$this->getStatus('hasId')."\n";
			
			if($this->getTable()){
				if(!$this->getStatus('hasId')){
					$id = '';
					$port = 0;
					$strKeyPub = '';
					$strKeyPubFingerprint = '';
					$isChannel = false;
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
					if(array_key_exists('isChannel', $msgData)){
						$isChannel = (bool)$msgData['isChannel'];
					}
					
					if($isChannel){
						$this->setStatus('isChannel', true);
					}
					
					$idOk = false;
					
					if(strIsUuid($id)){
						if($strKeyPub){
							
							$node = new Node();
							$node->setIdHexStr($id);
							$node->setIp($this->getIp());
							$node->setPort($port);
							$node->setTimeLastSeen(time());
							
							$node = $this->getTable()->nodeEnclose($node);
							
							if(! $this->getLocalNode()->isEqual($node)){
								if($node->getSslKeyPub()){
									$this->log('debug', 'found old ssl public key');
									
									if( $node->getSslKeyPub() == $strKeyPub ){
										$this->log('debug', 'ssl public key ok');
										
										$idOk = true;
									}
									else{
										$this->sendError(230, $msgName);
										$this->log('warning', 'ssl public key changed since last handshake');
									}
								}
								else{
									$sslPubKey = openssl_pkey_get_public($strKeyPub);
									if($sslPubKey !== false){
										$sslPubKeyDetails = openssl_pkey_get_details($sslPubKey);
										
										if($sslPubKeyDetails['bits'] >= Node::SSL_KEY_LEN_MIN){
											$this->log('debug', 'no old ssl public key found. good. set new.');
											
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
						
						$this->log('debug', $this->getIp().':'.$this->getPort().' recv '.$msgName.': '.$id.', '.$port.', '.$node->getSslKeyPubFingerprint());
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
			$this->log('debug', $this->getIp().':'.$this->getPort().' recv '.$msgName);
			
			if($this->getStatus('hasId')){
				$actions = $this->actionsGetByCriterion(ClientAction::CRITERION_AFTER_ID_OK);
				foreach($actions as $actionsId => $action){
					$this->actionRemove($action);
					$action->functionExec($this);
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
				
				$this->log('debug', $this->getIp().':'.$this->getPort().' recv '.$msgName.': '.$rid.', '.$nodeId);
				
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
					$this->log('debug', $this->getIp().':'.$this->getPort().' recv '.$msgName.': '.$rid);
					
					$request = null;
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
				}
				else{
					$this->sendError(900, $msgName);
				}
			}
			else{
				$this->sendError(100, $msgName);
			}
		}
		
		elseif($msgName == 'ssl_init'){
			if($this->getStatus('hasId')){
				if(!$this->getStatus('hasSslInit')){
					$this->setStatus('hasSslInit', true);
					
					$this->sendSslInit();
					
					#$this->setStatus('hasSslInitOk', true);
					$this->sendSslInitOk();
				}
			}
			else{
				$this->sendError(100, $msgName);
			}
		}
		elseif($msgName == 'ssl_init_ok'){
			if($this->getStatus('hasSslInit') && !$this->getStatus('hasSslInitOk')){
				$this->setStatus('hasSslInitOk', true);
				#$this->setStatus('hasSslInit', true);
				#print __CLASS__.'->'.__FUNCTION__.': "'.$msgName.'", '.(int)$this->getStatus('hasSslInit').', '.(int)$this->getStatus('hasSslInitOk')."\n";
				
				$this->sslTestToken = (string)Uuid::uuid4();
				$this->sendSslTest($this->sslTestToken);
			}
			else{
				$this->sendError(250, $msgName);
				$this->log('warning', $msgName.' SSL: you already initialized ssl');
			}
		}
		elseif($msgName == 'ssl_test'){
			print __CLASS__.'->'.__FUNCTION__.': "'.$msgName.'", '.(int)$this->getStatus('hasSslInitOk').', '.(int)$this->getStatus('hasSslTest').''."\n";
			
			if($this->getStatus('hasSslInitOk') && !$this->getStatus('hasSslTest')){
				$token = '';
				if(array_key_exists('token', $msgData)){
					$token = $msgData['token'];
				}
				
				if($token){
					$this->setStatus('hasSslTest', true);
					$this->sendSslVerify($token);
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
		elseif($msgName == 'ssl_verify'){
			if($this->getStatus('hasSslTest') && !$this->getStatus('hasSslVerify')){
				$token = '';
				if(array_key_exists('token', $msgData)){
					$token = $msgData['token'];
				}
				
				if($token && $this->sslTestToken && $token == $this->sslTestToken){
					$this->setStatus('hasSslVerify', true);
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
		/*elseif(substr($line, 0, 17) == 'SSL_PASSWORD_PUT '){
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
		}*/
		
		elseif($msgName == 'ping'){
			$id = '';
			if(array_key_exists('id', $msgData)){
				$id = $msgData['id'];
			}
			$this->sendPong($id);
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
			
			$this->log('debug', $this->getIp().':'.$this->getPort().' recv '.$msgName.': '.$code.', '.$msg.', '.$name);
		}
		elseif($msgName == 'quit'){
			$this->shutdown();
		}
	}
	
	private function msgCreate($name, $data){
		print __CLASS__.'->'.__FUNCTION__.': "'.$name.'"'."\n";
		
		$json = array(
			'name' => $name,
			'data' => $data,
		);
		return json_encode($json);
	}
	
	private function dataSend($msg){
		$msg = base64_encode($msg);
		$this->getSocket()->write($msg.static::MSG_SEPARATOR);
	}
	
	public function sslPublicEncrypt($data){
		$rv = '';
		/*
		if(openssl_sign($data, $sign, $this->getSsl(), OPENSSL_ALGO_SHA1)){
			$sign = base64_encode($sign);
			
			if(openssl_public_encrypt($data, $cryped, $this->getNode()->getSslKeyPub())){
				$data = base64_encode($cryped);
				
				$jsonStr = json_encode(array('data' => $data, 'sign' => $sign));
				$gzdata = gzencode($jsonStr, 9);
				
				$rv = base64_encode($gzdata);
			}
		}
		*/
		return $rv;
	}
	
	public function sslPrivateDecrypt($data){
		$rv = '';
		/*
		$data = base64_decode($data);
		$data = gzdecode($data);
		$json = json_decode($data, true);
		
		$data = base64_decode($json['data']);
		$sign = base64_decode($json['sign']);
		
		if(openssl_private_decrypt($data, $decrypted, $this->getSsl())){
			if(openssl_verify($decrypted, $sign, $this->getNode()->getSslKeyPub(), OPENSSL_ALGO_SHA1)){
				$rv = $decrypted;
			}
		}
		*/
		return $rv;
	}
	
	public function sslPasswordEncrypt($data){
		$rv = '';
		/*
		if($this->getSslPassword() && $this->getSslPasswordNode()){
			$password = $this->getSslPassword().'_'.$this->getSslPasswordNode();
			
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
			}
		}
		*/
		return $rv;
	}
	
	public function sslPasswordDecrypt($data){
		$rv = '';
		/*
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
		*/
		return $rv;
	}
	
	public function sendNop(){
		$data = array(
		);
		$this->dataSend($this->msgCreate('nop', $data));
	}
	
	public function sendHello(){
		$data = array(
			'ip' => $this->getIp(),
		);
		$this->dataSend($this->msgCreate('hello', $data));
	}
	
	private function sendId($isChannel = false){
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$sslKeyPub = base64_encode($this->getLocalNode()->getSslKeyPub());
		
		$data = array(
			'id'        => $this->getLocalNode()->getIdHexStr(),
			'port'      => $this->getLocalNode()->getPort(),
			'sslKeyPub' => $sslKeyPub,
			'isChannel' => (bool)$isChannel,
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
	
	public function sendSslInit($x = ''){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		if($this->getStatus('hasSendSslInit')){
			print __CLASS__.'->'.__FUNCTION__.': set hasSslInit to true'."\n";
			
			$this->setStatus('hasSslInit', true);
		}
		else{
			print __CLASS__.'->'.__FUNCTION__.': send ssl_init'."\n";
			$this->setStatus('hasSendSslInit', true);
			#$this->setStatus('hasSslInit', true);
			
			$data = array(
				'x' => $x,
			);
			$this->dataSend($this->msgCreate('ssl_init', $data));
		}
		
		/*
		$this->setStatus('hasSslInit', true);
		
		print __CLASS__.'->'.__FUNCTION__.': send ssl_init'."\n";
		$data = array(
			'x' => $x,
		);
		$this->dataSend($this->msgCreate('ssl_init', $data));
		*/
	}
	
	public function sendSslInitOk(){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$data = array(
		);
		$this->dataSend($this->msgCreate('ssl_init_ok', $data));
	}
	
	public function sendSslTest($token){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$data = array(
			'token' => $token,
		);
		$this->dataSend($this->msgCreate('ssl_test', $data));
	}
	
	public function sendSslVerify($token){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$data = array(
			'token' => $token,
		);
		$this->dataSend($this->msgCreate('ssl_verify', $data));
	}
	
	private function sendPing($id = ''){
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
	
}
