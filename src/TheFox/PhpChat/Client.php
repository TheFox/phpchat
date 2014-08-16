<?php

namespace TheFox\PhpChat;

use Exception;
use RuntimeException;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

use TheFox\Utilities\Hex;
use TheFox\Network\AbstractSocket;
use TheFox\Dht\Kademlia\Node;
use TheFox\Pow\Hashcash;

class Client{
	
	const MSG_SEPARATOR = "\n";
	const MSG_SEPARATOR_LEN = 1;
	const NODE_FIND_NUM = 8;
	const NODE_FIND_MAX_NODE_IDS = 1024;
	const PING_TTL = 25;
	const PONG_TTL = 300;
	const HASHCASH_BITS_MIN = 12;
	const HASHCASH_BITS_MAX = 15;
	const HASHCASH_EXPIRATION = 172800; // 2 days
	const SSL_PASSWORD_TTL = 300;
	const SSL_PASSWORD_MSG_MAX = 100;
	
	private $debug = false;
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
	private $sslPasswordLocalNew = '';
	private $sslPasswordPeer = '';
	private $sslPasswordPeerNew = '';
	private $sslPasswordTime = 0;
	
	private $recvBufferTmp = '';
	private $requestsId = 0;
	private $requests = array();
	private $actionsId = 0;
	private $actions = array();
	private $pingTime = 0;
	private $pongTime = 0;
	private $sslMsgCount = 0;
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->status['hasShutdown'] = false;
		$this->status['isChannelLocal'] = false;
		$this->status['isChannelPeer'] = false;
		
		$this->status['hasId'] = false;
		
		$this->resetStatusSsl();
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
	
	private function resetStatusSsl(){
		$this->status['hasSslInit'] = false;
		$this->status['hasSendSslInit'] = false;
		$this->status['hasSslInitOk'] = false;
		$this->status['hasSslTest'] = false;
		$this->status['hasSslVerify'] = false;
		$this->status['hasSslPasswortPut'] = false;
		$this->status['hasReSslPasswortPutInit'] = false;
		$this->status['hasReSslPasswortPut'] = false;
		$this->status['hasSslPasswortTest'] = false;
		$this->status['hasReSslPasswortTest'] = false;
		$this->status['hasSslPasswortVerify'] = false;
		$this->status['hasSsl'] = false;
	}
	
	public function setServer(Server $server){
		$this->server = $server;
	}
	
	public function getServer(){
		return $this->server;
	}
	
	public function setSocket(AbstractSocket $socket){
		$this->socket = $socket;
		$this->socket->getPeerName($ip, $port);
		$this->setIp($ip);
		$this->setPort($port);
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
		return $this->ip;
	}
	
	public function setPort($port){
		$this->port = $port;
	}
	
	public function getPort(){
		return $this->port;
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
	
	public function getLog(){
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
	
	public function getTable(){
		if($this->getServer()){
			return $this->getServer()->getTable();
		}
		
		return null;
	}
	
	public function getMsgDb(){
		if($this->getServer()){
			return $this->getServer()->getMsgDb();
		}
		
		return null;
	}
	
	public function getHashcashDb(){
		if($this->getServer()){
			return $this->getServer()->getHashcashDb();
		}
		
		return null;
	}
	
	public function hashcashMint($bits = null){
		if($bits === null){
			$bits = static::HASHCASH_BITS_MIN;
		}
		if($this->getLocalNode()){
			$hashcash = new Hashcash($bits, $this->getLocalNode()->getIdHexStr());
			$hashcash->setDate(date(Hashcash::DATE_FORMAT12));
			#$hashcash->setMintAttemptsMax(10);
			
			try{
				$this->log('debug', 'hashcash: mint '.$bits.' bits');
				$stamp = $hashcash->mint();
				#$this->log('debug', 'hashcash minted: '.$stamp);
				return $stamp;
			}
			catch(Exception $e){
				$this->log('error', $e->getMessage());
			}
		}
		
		return null;
	}
	
	public function hashcashVerify($hashcashStr, $resource, $bits = null){
		#$this->log('debug', 'hashcash: '.$hashcashStr);
		
		if($bits === null){
			$bits = static::HASHCASH_BITS_MIN;
		}
		
		$hashcash = new Hashcash();
		$hashcash->setExpiration(static::HASHCASH_EXPIRATION);
		try{
			if($hashcash->verify($hashcashStr)){
				#$this->log('debug', 'bits: '.$hashcash->getBits());
				$added = false;
				if($hashcash->getVersion() >= 1 && $hashcash->getBits() >= $bits && $hashcash->getResource() == $resource && $added = $this->getHashcashDb()->addHashcash($hashcash)){
					$this->log('debug', 'hashcash: OK');
					return true;
				}
				else{
					$this->log('debug', 'hashcash version: '.$hashcash->getVersion());
					$this->log('debug', 'hashcash bit: '.$hashcash->getBits().' (min: '.$bits.')');
					$this->log('debug', 'hashcash resource: '.$hashcash->getResource().' ('.$resource.')');
					$this->log('debug', 'hashcash added: '.($added ? 'yes' : 'no'));
				}
			}
		}
		catch(Exception $e){
			$this->log('warning', $e->getMessage());
		}
		
		$this->log('debug', 'hashcash: '.$hashcashStr.' failed');
		return false;
	}
	
	public function requestAdd($name, $rid, $data = array()){
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
	
	public function requestGetByRid($rid){
		foreach($this->requests as $requestId => $request){
			if($request['rid'] == $rid){
				return $request;
			}
		}
		return null;
	}
	
	public function requestRemove($request){
		unset($this->requests[$request['id']]);
	}
	
	public function actionAdd(ClientAction $action){
		$this->actionsId++;
		
		$action->setId($this->actionsId);
		
		$this->actions[$this->actionsId] = $action;
	}
	
	public function actionsGetByCriterion($criterion){
		$rv = array();
		foreach($this->actions as $actionsId => $action){
			if($action->hasCriterion($criterion)){
				$rv[] = $action;
			}
		}
		return $rv;
	}
	
	public function actionGetByCriterion($criterion){
		foreach($this->actions as $actionsId => $action){
			if($action->hasCriterion($criterion)){
				return $action;
			}
		}
		
		return null;
	}
	
	public function actionRemove(ClientAction $action){
		#print __CLASS__.'->'.__FUNCTION__.': '.$action->getId()."\n";
		unset($this->actions[$action->getId()]);
	}
	
	public function run(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->checkPingSend();
		$this->checkPongTimeout();
		$this->checkActions();
		$this->checkSslPasswordTimeout();
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
	
	private function checkActions(){
		#print __CLASS__.'->'.__FUNCTION__.': after actions'."\n";
		$action = $this->actionGetByCriterion(ClientAction::CRITERION_AFTER_PREVIOUS_ACTIONS);
		
		if($action && count($this->actions)){
			$actions = $this->actions;
			$caction = array_shift($actions);
			if($caction->getId() == $action->getId()){
				$this->actionRemove($action);
				$action->functionExec($this);
			}
		}
	}
	
	private function checkSslPasswordTimeout(){
		if(!$this->sslPasswordTime){
			$this->sslPasswordTime = time();
		}
		if($this->sslPasswordTime < time() - static::SSL_PASSWORD_TTL || $this->sslMsgCount >= static::SSL_PASSWORD_MSG_MAX){
			#$this->log('debug', 'SSL: password timed out: '.date('H:i:s', $this->sslPasswordTime));
			#$this->log('debug', 'SSL: msgs count: '.$this->sslMsgCount);
			
			$this->sslMsgCount = 0;
			$this->sslPasswordToken = '';
			$this->sslPasswordLocalNew = '';
			$this->sslPasswordPeerNew = '';
			$this->setStatus('hasReSslPasswortPutInit', true);
			$this->setStatus('hasReSslPasswortPut', false);
			$this->setStatus('hasReSslPasswortTest', false);
			
			$this->sendSslPasswordReput();
		}
	}
	
	public function dataRecv($data = null){
		#fwrite(STDOUT, 'data: /'.$data.'/'."\n");
		
		$rv = '';
		if($data === null && $this->getSocket()){
			#fwrite(STDOUT, 'data read from socket'."\n");
			$data = $this->getSocket()->read();
		}
		
		#fwrite(STDOUT, 'data: /'.$data.'/'."\n");
		
		do{
			$separatorPos = strpos($data, static::MSG_SEPARATOR);
			if($separatorPos === false){
				$this->recvBufferTmp .= $data;
				$data = '';
			}
			else{
				$msg = $this->recvBufferTmp.substr($data, 0, $separatorPos);
				$this->recvBufferTmp = '';
				
				$rv .= $this->msgHandle($msg);
				#fwrite(STDOUT, 'rv: /'.$rv.'/'."\n");
				
				$data = substr($data, $separatorPos + 1);
			}
		}
		while($data);
		
		return $rv;
	}
	
	public function msgHandle($msgRaw){
		$rv = '';
		$msg = json_decode(base64_decode($msgRaw), true);
		
		$msgName = '';
		$msgData = array();
		if($msg){
			$msgName = substr(strtolower($msg['name']), 0, 256);
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
		
		if($msgName == 'noop'){
			$noop = 0x90;
		}
		elseif($msgName == 'test'){
			$len = 0;
			$test_data = 'N/A';
			if(array_key_exists('len', $msgData)){
				$len = (int)$msgData['len'];
			}
			if(array_key_exists('test_data', $msgData)){
				$test_data = $msgData['test_data'];
			}
		}
		elseif($msgName == 'hello'){
			if(array_key_exists('ip', $msgData)){
				$ip = $msgData['ip'];
				if($ip != '127.0.0.1' && strIsIp($ip) && $this->getSettings()){
					$this->getSettings()->data['node']['ipPub'] = $ip;
					$this->getSettings()->setDataChanged(true);
				}
			}
			
			$action = $this->actionGetByCriterion(ClientAction::CRITERION_AFTER_HELLO);
			if($action){
				$this->actionRemove($action);
				$action->functionExec($this);
			}
			
			$rv .= $this->sendId();
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
					$hashcash = '';
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
					}
					if(array_key_exists('isChannel', $msgData)){ // isChannelPeer
						$isChannelPeer = (bool)$msgData['isChannel'];
					}
					#if(array_key_exists('hashcash', $msgData)){
					#	$hashcash = $msgData['hashcash'];
					#}
					
					if($isChannelPeer){
						$this->setStatus('isChannelPeer', true);
					}
					
					$idOk = false;
					$node = new Node();
					
					if(strIsUuid($id)){
						if($strKeyPub){
							#if($hashcash && $this->hashcashVerify($hashcash, $id, static::HASHCASH_BITS_MIN)){
								$node->setIdHexStr($id);
								$node->setUri('tcp://'.$this->getIp().':'.$port);
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
											$rv .= $this->sendError(2030, $msgName);
											#$this->log('warning', 'ssl public key changed since last handshake');
										}
									}
									else{
										$sslPubKey = openssl_pkey_get_public($strKeyPub);
										if($sslPubKey !== false){
											$sslPubKeyDetails = openssl_pkey_get_details($sslPubKey);
											
											if($sslPubKeyDetails['bits'] >= Node::SSL_KEY_LEN_MIN){
												#$this->log('debug', 'no old ssl public key found. good. set new.');
												
												$strKeyPubFingerprint = Node::genSslKeyFingerprint($strKeyPub);
												$fpnode = $this->getTable()->nodeFindByKeyPubFingerprint($strKeyPubFingerprint);
												if(!$fpnode){
													$idOk = true;
												}
												else{
													$rv .= $this->sendError(2035, $msgName);
												}
											}
											else{
												$rv .= $this->sendError(2020, $msgName);
											}
										}
										else{
											$rv .= $this->sendError(2040, $msgName);
										}
									}
								}
								else{
									$rv .= $this->sendError(1020, $msgName);
								}
							#}
							#else{
							#	$this->sendError(4000, $msgName);
							#}
						}
						else{
							$rv .= $this->sendError(2000, $msgName);
						}
					}
					else{
						$rv .= $this->sendError(9000, $msgName);
					}
					
					if($idOk){
						$node->setSslKeyPub($strKeyPub);
						
						$this->setStatus('hasId', true);
						$this->setNode($node);
						
						$rv .= $this->sendIdOk();
						
						$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$id.', '.$port);
					}
					else{
						$rv .= $this->sendQuit();
						$this->shutdown();
					}
				}
				else{
					$rv .= $this->sendError(1010, $msgName);
				}
			}
			else{
				$rv .= $this->sendError(9010, $msgName);
			}
		}
		elseif($msgName == 'id_ok'){
			$this->log('debug', $this->getIpPort().' recv '.$msgName);
			
			$action = $this->actionGetByCriterion(ClientAction::CRITERION_AFTER_ID_SUCCESSFULL);
			if($action){
				$this->actionRemove($action);
				$action->functionExec($this);
			}
			
			if($this->getStatus('isChannelPeer')){
				$this->consoleMsgAdd('New incoming channel connection from '.$this->getIpPort().'.', true, true, true);
			}
			
			if($this->getStatus('isChannelPeer') || $this->getStatus('isChannelLocal')){
				if($this->getServer() && $this->getServer()->getKernel()){
					$contact = $this->getServer()->getKernel()->getAddressbook()->contactGetByNodeId($this->getNode()->getIdHexStr());
					if($contact){
						$text = 'You talked to ';
						$text .= $this->getNode()->getIdHexStr().' ('.$contact->getUserNickname().')';
						$text .= ' once before.';
						$this->consoleMsgAdd($text, true, false);
					}
					else{
						$this->consoleMsgAdd('You never talked to '.$this->getNode()->getIdHexStr().' before.', true, false);
						$this->consoleMsgAdd('Verify the public keys with you conversation partner on another channel.', true, false);
						$this->consoleMsgAdd('Public keys fingerprints:', true, false);
						$this->consoleMsgAdd('  Yours: '.$this->getLocalNode()->getSslKeyPubFingerprint(), true, false);
						$this->consoleMsgAdd('  Peers: '.$this->getNode()->getSslKeyPubFingerprint(), true, false);
					}
				}
			}
		}
		elseif($msgName == 'node_find'){
			if($this->getStatus('hasId')){
				$rid = '';
				$num = static::NODE_FIND_NUM;
				$nodeId = '';
				$hashcash = '';
				if(array_key_exists('rid', $msgData)){
					$rid = $msgData['rid'];
				}
				if(array_key_exists('num', $msgData)){
					$num = $msgData['num'];
				}
				if(array_key_exists('nodeId', $msgData)){
					$nodeId = $msgData['nodeId'];
				}
				if(array_key_exists('hashcash', $msgData)){
					$hashcash = $msgData['hashcash'];
				}
				
				$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.', '.$nodeId.', '.(int)($this->getNode() != null));
				
				if($hashcash && $this->hashcashVerify($hashcash, $this->getNode()->getIdHexStr(), static::HASHCASH_BITS_MIN)){
					if($nodeId){
						$node = new Node();
						$node->setIdHexStr($nodeId);
						
						if( $node->isEqual($this->getLocalNode()) ){
							$this->log('debug', 'node find: find myself');
							
							$rv .= $this->sendNodeFound($rid);
						}
						elseif( !$node->isEqual($this->getNode()) && $onode = $this->getTable()->nodeFindInBuckets($node) ){
							$this->log('debug', 'node find: find in buckets');
							
							$rv .= $this->sendNodeFound($rid, array($onode));
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
							
							$rv .= $this->sendNodeFound($rid, $nodes);
						}
					}
				}
				else{
					$rv .= $this->sendError(4000, $msgName);
				}
			}
			else{
				$rv .= $this->sendError(1000, $msgName);
			}
		}
		elseif($msgName == 'node_found'){
			if($this->getStatus('hasId')){
				$rid = '';
				$nodes = array();
				$hashcash = '';
				if(array_key_exists('rid', $msgData)){
					$rid = $msgData['rid'];
				}
				if(array_key_exists('nodes', $msgData)){
					$nodes = $msgData['nodes'];
				}
				if(array_key_exists('hashcash', $msgData)){
					$hashcash = $msgData['hashcash'];
				}
				
				if($rid){
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid);
					
					$request = $this->requestGetByRid($rid);
					if($request){
						if($hashcash && $this->hashcashVerify($hashcash, $this->getNode()->getIdHexStr(), static::HASHCASH_BITS_MAX)){
							$this->requestRemove($request);
							
							$nodeId = $request['data']['nodeId'];
							$nodesFoundIds = $request['data']['nodesFoundIds'];
							$distanceOld = $request['data']['distance'];
							$uri = '';
							
							if($nodes){
								// Find the smallest distance.
								foreach($nodes as $nodeArId => $nodeAr){
									
									$node = new Node();
									if(isset($nodeAr['id'])){
										$node->setIdHexStr($nodeAr['id']);
									}
									if(isset($nodeAr['uri'])){
										$node->setUri($nodeAr['uri']);
									}
									if(isset($nodeAr['sslKeyPub']) && $nodeAr['sslKeyPub']){
										$node->setSslKeyPub(base64_decode($nodeAr['sslKeyPub']));
									}
									$node->setTimeLastSeen(time());
									
									$distanceNew = $this->getLocalNode()->distanceHexStr($node);
									
									$this->log('debug', 'node found: '.$nodeArId.', '.$nodeAr['id'].', do='.$distanceOld.', dn='.$distanceNew);
									
									if(!$this->getLocalNode()->isEqual($node)){
										if($this->getSettings()->data['node']['ipPub'] != $node->getUri()->getHost()
											|| $this->getLocalNode()->getUri()->getPort() != $node->getUri()->getPort()){
											if(!in_array($node->getIdHexStr(), $nodesFoundIds)){
												
												$nodesFoundIds[] = $nodeAr['id'];
												if(count($nodesFoundIds) > static::NODE_FIND_MAX_NODE_IDS){
													array_shift($nodesFoundIds);
												}
												
												if($nodeAr['id'] == $nodeId){
													$this->log('debug', 'node found: find completed');
													$uri = '';
												}
												else{
													if($distanceOld != $distanceNew){
														$distanceMin = Node::idMinHexStr($distanceOld, $distanceNew);
														if($distanceMin == $distanceNew){ // Is smaller then $distanceOld.
															$distanceOld = $distanceNew;
															$uri = $node->getUri();
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
											$this->log('debug', 'node found: myself, uri equal ('.$node->getUri().')');
										}
									}
									else{
										$this->log('debug', 'node found: myself, node equal');
									}
								}
							}
							
							if($uri){
								// Further search at the nearest node.
								$this->log('debug', 'node found: uri ('.$uri.') ok');
								
								$clientActions = array();
								$action = new ClientAction(ClientAction::CRITERION_AFTER_ID_SUCCESSFULL);
								$action->functionSet(function($action, $client){
									$client->sendNodeFind($nodeId, $distanceOld, $nodesFoundIds);
								});
								$clientActions[] = $action;
								
								$this->getServer()->connect($uri, $clientActions);
							}
						}
						else{
							$rv .= $this->sendError(4000, $msgName);
						}
					}
					else{
						$rv .= $this->sendError(9000, $msgName);
					}
				}
				else{
					$rv .= $this->sendError(9000, $msgName);
				}
			}
			else{
				$rv .= $this->sendError(1000, $msgName);
			}
		}
		
		elseif($msgName == 'msg'){
			if($this->getStatus('hasId')){
				if($this->getMsgDb()){
					$rid = '';
					$version = 0;
					$id = '';
					$srcNodeId = '';
					$srcSslKeyPub = '';
					$dstNodeId = '';
					$subject = '';
					$body = '';
					$password = '';
					$checksum = '';
					$relayCount = 0;
					$timeCreated = 0;
					if(array_key_exists('rid', $msgData)){
						$rid = $msgData['rid'];
					}
					if(array_key_exists('version', $msgData)){
						$version = (int)$msgData['version'];
					}
					if(array_key_exists('id', $msgData)){
						$id = $msgData['id'];
					}
					if(array_key_exists('srcNodeId', $msgData)){
						$srcNodeId = $msgData['srcNodeId'];
					}
					if(array_key_exists('srcSslKeyPub', $msgData)){
						$srcSslKeyPub = base64_decode($msgData['srcSslKeyPub']);
					}
					if(array_key_exists('dstNodeId', $msgData)){
						$dstNodeId = $msgData['dstNodeId'];
					}
					if(array_key_exists('body', $msgData)){
						$body = $msgData['body'];
					}
					if(array_key_exists('password', $msgData)){
						$password = $msgData['password'];
					}
					if(array_key_exists('checksum', $msgData)){
						$checksum = $msgData['checksum'];
					}
					if(array_key_exists('relayCount', $msgData)){
						$relayCount = (int)$msgData['relayCount'];
					}
					if(array_key_exists('timeCreated', $msgData)){
						$timeCreated = (int)$msgData['timeCreated'];
					}
					if(array_key_exists('hashcash', $msgData)){
						$hashcash = $msgData['hashcash'];
					}
					
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$id);
					
					#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.' body: '.$body."\n"); # TODO
					#$this->log('debug', 'msg '.$id.' body: '.$body);
					
					$status = 1; // New
					if($this->getMsgDb()->getMsgById($id)){
						$status = 2; // Reject
					}
					
					$srcNode = new Node();
					$srcNode->setIdHexStr($srcNodeId);
					$srcNode = $this->getTable()->nodeEnclose($srcNode);
					
					#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.': srcNode: '.$srcNode->getIdHexStr()."\n"); # TODO
					$this->log('debug', 'msg '.$id.' srcNode: '.$srcNode->getIdHexStr());
					
					if($srcNode->getSslKeyPub()){
						if($srcNode->getSslKeyPub() != $srcSslKeyPub){
							$status = 3; // Error
						}
					}
					else{
						if($srcNode->setSslKeyPub($srcSslKeyPub)){
							$srcNode->setDataChanged(true);
						}
					}
					
					if($hashcash && $this->hashcashVerify($hashcash, $this->getNode()->getIdHexStr(), static::HASHCASH_BITS_MAX)){
						$rv .= $this->sendMsgResponse($rid, $status);
						
						if($status == 1){
							$msg = new Msg();
							$msg->setVersion($version);
							$msg->setId($id);
							$msg->setRelayNodeId($this->getNode()->getIdHexStr());
							$msg->setSrcNodeId($srcNodeId);
							$msg->setSrcSslKeyPub($srcSslKeyPub);
							$msg->setDstNodeId($dstNodeId);
							$msg->setBody($body);
							$msg->setPassword($password);
							$msg->setChecksum($checksum);
							$msg->setRelayCount($relayCount);
							$msg->setEncryptionMode('D');
							$msg->setStatus('U');
							$msg->setTimeCreated($timeCreated);
							$msg->setTimeReceived(time());
							
							if($msg->getDstNodeId() == $this->getLocalNode()->getIdHexStr()){
								$msg->setDstSslPubKey($this->getLocalNode()->getSslKeyPub());
								$msg->setSsl($this->getSsl());
								
								try{
									if($msg->decrypt()){
										fwrite(STDOUT, 'msg '.$id.': decrypt ok'."\n"); # TODO
										$this->log('debug', 'msg '.$id.' decrypt ok');
										
										if(!$msg->getIgnore()){
											fwrite(STDOUT, 'msg '.$id.': not ignore'."\n"); # TODO
											$this->log('debug', 'msg '.$id.' not ignore');
											$this->log('debug', 'msg '.$id.' subject: '.$msg->getSubject());
											$this->getServer()->imapMailAdd($msg);
											$this->getServer()->consoleMsgAdd('You got mail.', true, true, true);
										}
										else{
											fwrite(STDOUT, 'msg '.$id.': ignore'."\n"); # TODO
											$this->log('debug', 'msg '.$id.' ignore');
										}
									}
									else{
										fwrite(STDOUT, 'msg '.$id.': decrypt failed B'."\n"); # TODO
										$this->log('debug', 'msg '.$id.' decrypt failed B');
									}
								}
								catch(Exception $e){
									fwrite(STDOUT, 'msg '.$id.': decrypt failed A: '.$e->getMessage()."\n"); # TODO
									$this->log('debug', 'msg '.$id.' decrypt failed A: '.$e->getMessage());
								}
							}
							else{
								fwrite(STDOUT, 'msg '.$id.': msg not for me'."\n"); # TODO
								$this->log('debug', 'msg '.$id.' not for me');
							}
							
							$this->getMsgDb()->msgAdd($msg); // Add all messages.
						}
					}
					else{
						$rv .= $this->sendError(4000, $msgName);
					}
				}
				else{
					$rv .= $this->sendError(3090, $msgName);
				}
			}
			else{
				$rv .= $this->sendError(1000, $msgName);
			}
		}
		elseif($msgName == 'msg_response'){
			if($this->getStatus('hasId')){
				$rid = '';
				$status = 0;
				if(array_key_exists('rid', $msgData)){
					$rid = $msgData['rid'];
				}
				if(array_key_exists('status', $msgData)){
					$status = (int)$msgData['status'];
				}
				
				$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.', '.$status);
				
				$request = $this->requestGetByRid($rid);
				if($request){
					#ve($request);
					
					$msg = $request['data']['msg'];
					$msg->addSentNode($this->getNode()->getIdHexStr());
					$msg->setStatus('S');
					if($this->getNode()->getIdHexStr() == $msg->getDstNodeId()){
						$msg->setStatus('D');
					}
					
					$action = $this->actionGetByCriterion(ClientAction::CRITERION_AFTER_MSG_RESPONSE_SUCCESSFULL);
					if($action){
						$this->actionRemove($action);
						$action->functionExec($this);
					}
				}
				else{
					$rv .= $this->sendError(9000, $msgName);
				}
			}
			else{
				$rv .= $this->sendError(1000, $msgName);
			}
			
			$action = $this->actionGetByCriterion(ClientAction::CRITERION_AFTER_MSG_RESPONSE);
			if($action){
				$this->actionRemove($action);
				$action->functionExec($this);
			}
		}
		
		elseif($msgName == 'ssl_init'){
			if($this->getSsl()){
				if($this->getStatus('hasId')){
					if(!$this->getStatus('hasSslInit')){
						$hashcash = '';
						if(array_key_exists('hashcash', $msgData)){
							$hashcash = $msgData['hashcash'];
						}
						
						$this->log('debug', 'SSL: init A');
						
						if($hashcash && $this->hashcashVerify($hashcash, $this->getNode()->getIdHexStr(), static::HASHCASH_BITS_MIN)){
							$this->log('debug', 'SSL: init B');
							
							$this->setStatus('hasSslInit', true);
							$rv .= $this->sendSslInit();
							$rv .= $this->sendSslInitResponse(1);
						}
						else{
							$this->resetStatusSsl();
							#$rv .= $this->sendError(4000, $msgName);
							$rv .= $this->sendSslInitResponse(4000);
						}
					}
				}
				else{
					$this->resetStatusSsl();
					#$rv .= $this->sendError(1000, $msgName);
					$rv .= $this->sendSslInitResponse(1000);
				}
			}
			else{
				$this->resetStatusSsl();
				#$rv .= $this->sendError(3090, $msgName);
				$rv .= $this->sendSslInitResponse(3090);
			}
		}
		elseif($msgName == 'ssl_init_response'){
			$code = 0;
			if(array_key_exists('code', $msgData)){
				$code = $msgData['code'];
			}
			
			$this->log('debug', 'SSL: init response: '.$code);
			
			if($code){
				if($code == 1){
					// Ok
					if($this->getStatus('hasSslInit') && !$this->getStatus('hasSslInitOk')){
						$this->log('debug', 'SSL: init ok');
						
						$this->setStatus('hasSslInitOk', true);
						$rv .= $this->sendSslTest();
					}
					else{
						$this->log('warning', $msgName.' SSL: you already initialized ssl');
						$rv .= $this->sendError(2050, $msgName);
					}
				}
				else{
					$this->log('warning', $msgName.' SSL: failed. code = '.$code);
					$this->resetStatusSsl();
					$rv .= $this->sendError(3100, $msgName);
				}
			}
			else{
				$this->log('warning', $msgName.' SSL: failed, invalid data');
				$this->resetStatusSsl();
				$rv .= $this->sendError(3100, $msgName);
			}
		}
		elseif($msgName == 'ssl_test'){
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
						$rv .= $this->sendSslVerify($token);
					}
					else{
						$rv .= $this->sendError(9000, $msgName);
					}
				}
				else{
					$rv .= $this->sendError(2070, $msgName);
					$this->log('warning', $msgName.' SSL: decryption failed');
				}
			}
			else{
				$rv .= $this->sendError(2060, $msgName);
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
						$rv .= $this->sendSslPasswordPut();
					}
					else{
						$rv .= $this->sendError(2080, $msgName);
					}
				}
				else{
					$rv .= $this->sendError(2070, $msgName);
					$this->log('warning', $msgName.' SSL: decryption failed');
				}
			}
			else{
				$rv .= $this->sendError(2060, $msgName);
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
						$this->log('debug', 'SSL: peer password: '.substr($this->sslPasswordPeer, 0, 20));
						
						$rv .= $this->sendSslPasswordTest();
					}
					else{
						$rv .= $this->sendError(9000, $msgName);
					}
				}
				else{
					$rv .= $this->sendError(2070, $msgName);
					$this->log('warning', $msgName.' SSL: decryption failed');
				}
			}
			else{
				$rv .= $this->sendError(2060, $msgName);
				$this->log('warning', $msgName.' SSL: you need to initialize ssl');
			}
		}
		elseif($msgName == 'ssl_password_reput'){
			if($this->getStatus('hasSsl') && !$this->getStatus('hasReSslPasswortPut')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData);
				if($msgData){
					$password = '';
					if(array_key_exists('password', $msgData)){
						$password = $msgData['password'];
					}
					
					if($password){
						$this->log('debug', 're-SSL: password reput');
						
						$this->sslPasswordPeerNew = $password;
						
						#$this->log('debug', 'SSL: peer password: '.substr($this->sslPasswordPeer, 0, 20));
						#$this->log('debug', 'SSL: peer password new: '.substr($this->sslPasswordPeerNew, 0, 20));
						
						if(!$this->getStatus('hasReSslPasswortPutInit')){
							$this->sslMsgCount = 0;
							$this->sslPasswordToken = '';
							$this->sslPasswordLocalNew = '';
							$this->setStatus('hasReSslPasswortPutInit', true);
							$this->setStatus('hasReSslPasswortTest', false);
							
							$rv .= $this->sendSslPasswordReput();
						}
						$rv .= $this->sendSslPasswordRetest();
					}
					else{
						$rv .= $this->sendError(9000, $msgName);
					}
				}
				else{
					$rv .= $this->sendError(2070, $msgName);
					$this->log('warning', $msgName.' re-SSL: decryption failed');
				}
			}
			else{
				$rv .= $this->sendError(2060, $msgName);
				$this->log('warning', $msgName.' re-SSL: you need to initialize ssl, hasSsl=/'.(int)$this->getStatus('hasSsl').'/ hasReSslPasswortPut=/'.(int)$this->getStatus('hasReSslPasswortPut').'/');
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
						$rv .= $this->sendSslPasswordVerify($token);
					}
					else{
						$rv .= $this->sendError(9000, $msgName);
					}
				}
				else{
					$rv .= $this->sendError(2070, $msgName);
					$this->log('warning', $msgName.' SSL: decryption failed');
				}
			}
			else{
				$rv .= $this->sendError(2060, $msgName);
				$this->log('warning', $msgName.' SSL: you need to initialize ssl');
			}
		}
		elseif($msgName == 'ssl_password_retest'){
			if($this->getStatus('hasSsl') && !$this->getStatus('hasReSslPasswortTest')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData, $this->sslPasswordLocalNew, $this->sslPasswordPeerNew);
				if($msgData){
					$token = '';
					if(array_key_exists('token', $msgData)){
						$token = $msgData['token'];
					}
					
					if($token){
						$this->log('debug', 're-SSL: password retest');
						
						$this->setStatus('hasReSslPasswortTest', true);
						$rv .= $this->sendSslPasswordReverify($token);
					}
					else{
						$rv .= $this->sendError(9000, $msgName);
					}
				}
				else{
					$rv .= $this->sendError(2070, $msgName);
					$this->log('warning', $msgName.' re-SSL: decryption failed');
				}
			}
			else{
				$rv .= $this->sendError(2060, $msgName);
				$this->log('warning', $msgName.' re-SSL: you need to initialize ssl, hasSsl=/'.(int)$this->getStatus('hasSsl').'/ hasReSslPasswortTest=/'.(int)$this->getStatus('hasReSslPasswortTest').'/');
			}
		}
		elseif($msgName == 'ssl_password_verify'){
			if($this->getStatus('hasSslPasswortTest')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData);
				if($msgData){
					$token = '';
					if(array_key_exists('token', $msgData)){
						$token = $msgData['token'];
					}
					
					#print __CLASS__.'->'.__FUNCTION__.': '.$msgName.' SSL: password token: '.$token."\n";
					
					if($token){
						$testToken = hash('sha512',
							$this->sslPasswordToken.'_'.$this->getNode()->getSslKeyPubFingerprint());
						if($this->sslPasswordToken && $token == $testToken){
							$this->log('debug', 'SSL: password verified');
							$this->log('debug', 'SSL: OK');
							
							$this->setStatus('hasSsl', true);
							
							$action = $this->actionGetByCriterion(ClientAction::CRITERION_AFTER_HAS_SSL);
							if($action){
								$this->actionRemove($action);
								$action->functionExec($this);
							}
						}
						else{
							$rv .= $this->sendError(2090, $msgName);
						}
					}
					else{
						$rv .= $this->sendError(9000, $msgName);
					}
				}
				else{
					$rv .= $this->sendError(2070, $msgName);
					$this->log('warning', $msgName.' SSL: decryption failed');
				}
			}
			else{
				$rv .= $this->sendError(2060, $msgName);
				$this->log('warning', $msgName.' SSL: you need to initialize ssl');
			}
			
			$this->sslTestToken = '';
			$this->sslPasswordToken = '';
		}
		elseif($msgName == 'ssl_password_reverify'){
			if($this->getStatus('hasSsl') && $this->getStatus('hasReSslPasswortTest')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData, $this->sslPasswordLocalNew, $this->sslPasswordPeerNew);
				if($msgData){
					$token = '';
					if(array_key_exists('token', $msgData)){
						$token = $msgData['token'];
					}
					
					#print __CLASS__.'->'.__FUNCTION__.': '.$msgName.' re-SSL: password token: '.$token."\n";
					
					if($token){
						$testToken = hash('sha512',
							$this->sslPasswordToken.'_'.$this->getNode()->getSslKeyPubFingerprint());
						if($this->sslPasswordToken && $token == $testToken){
							$this->log('debug', 're-SSL: password verified');
							$this->log('debug', 're-SSL: OK');
							
							$this->setStatus('hasReSslPasswortPutInit', false);
							
							$this->sslPasswordLocal = $this->sslPasswordLocalNew;
							$this->sslPasswordPeer = $this->sslPasswordPeerNew;
							
							$action = $this->actionGetByCriterion(ClientAction::CRITERION_AFTER_HAS_RESSL);
							if($action){
								$this->actionRemove($action);
								$action->functionExec($this);
							}
						}
						else{
							$rv .= $this->sendError(2090, $msgName);
						}
					}
					else{
						$rv .= $this->sendError(9000, $msgName);
					}
				}
				else{
					$rv .= $this->sendError(2070, $msgName);
					$this->log('warning', $msgName.' re-SSL: decryption failed');
				}
			}
			else{
				$rv .= $this->sendError(2060, $msgName);
				$this->log('warning', $msgName.' re-SSL: you need to initialize ssl, hasSsl=/'.(int)$this->getStatus('hasSsl').'/ hasReSslPasswortTest=/'.(int)$this->getStatus('hasReSslPasswortTest').'/');
			}
			
			$this->sslPasswordToken = '';
			$this->sslPasswordLocalNew = '';
			$this->sslPasswordLocalNew = '';
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
						
						$this->sendSslKeyPubPut($rid, $node->getIdHexStr(),
							$node->getIp(), $node->getPort(),
							$node->getSslKeyPubFingerprint(), $node->getSslKeyPub());
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
				$this->sendError(1000, $msgName);
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
				
				$debugText = '"'.$rid.'" "'.$nodeId.'" "'.$nodeIp.':'.$nodePort.'" "'.$nodeSslKeyPubFingerprint.'"';
				$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$debugText);
				
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
							$this->sendError(9000, $msgName);
						}
					}
				}
				else{
					$this->sendError(9000, $msgName);
				}
			}
			else{
				$this->sendError(1000, $msgName);
			}
		}
		
		elseif($msgName == 'talk_request'){
			if($this->getStatus('hasSsl')){
				$msgData = $this->sslMsgDataPasswordDecrypt($msgData);
				if($msgData){
					$rid = '';
					$userNickname = '';
					$hashcash = '';
					if(array_key_exists('rid', $msgData)){
						$rid = $msgData['rid'];
					}
					if(array_key_exists('userNickname', $msgData)){
						$userNickname = $msgData['userNickname'];
					}
					if(array_key_exists('hashcash', $msgData)){
						$hashcash = $msgData['hashcash'];
					}
					
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$rid.', '.$userNickname);
					
					if($hashcash && $this->hashcashVerify($hashcash, $this->getNode()->getIdHexStr(), static::HASHCASH_BITS_MAX)){
						if($rid){
							if($this->getServer() && $this->getServer()->kernelHasConsole()){
								$this->consoleTalkRequestAdd($rid, $userNickname);
							}
							else{
								$rv .= $this->sendTalkResponse($rid, 4);
							}
						}
						else{
							$rv .= $this->sendError(9000, $msgName);
						}
					}
					else{
						$rv .= $this->sendError(4000, $msgName);
					}
				}
				else{
					$rv .= $this->sendError(9000, $msgName);
				}
			}
			else{
				$rv .= $this->sendError(2060, $msgName);
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
						
						//if($status == 0){} // Undefined
						if($status == 1){
							// Accepted
							$this->consoleMsgAdd('Talk request accepted.', true, false);
							$this->consoleMsgAdd('Now talking to "'.$userNickname.'".', true, true);
							
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
							$this->consoleMsgAdd('Talk request declined.', true, true);
						}
						elseif($status == 3){
							// Timeout
							$this->consoleMsgAdd('Talk request timed out.', true, true);
						}
						elseif($status == 4){
							// No console, standalone server.
							$this->consoleMsgAdd($this->getIpPort().' has no user interface. Can\'t talk to you.', true, true);
						}
					}
					else{
						$rv .= $this->sendError(9000, $msgName);
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
					
					$debugText = $rid.', '.$userNickname.', '.(int)$ignore.', '.$text;
					$this->log('debug', $this->getIpPort().' recv '.$msgName.': '.$debugText);
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
					$this->consoleMsgAdd('User "'.$userNicknameOld.'" is now known as "'.$userNicknameNew.'".',
						true, true, true);
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
					
					$rv .= $this->sendQuit();
					$this->shutdown();
					
					$this->consoleMsgAdd('Talk closed by "'.$userNickname.'".', true, true, true);
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
			$rv .= $this->sendPong($id);
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
			$rv .= $this->sendError(9020, $msgName);
		}
		
		return $rv;
	}
	
	private function msgCreate($name, $data = array()){
		#print __CLASS__.'->'.__FUNCTION__.': "'.$name.'"'."\n";
		
		$json = array(
			'name' => $name,
		);
		if($data){
			$json['data'] = $data;
		}
		return json_encode($json);
	}
	
	private function dataSend($msg){
		#print __CLASS__.'->'.__FUNCTION__.': "'.$msg.'"'."\n";
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$rv = '';
		
		if($msg){
			$msg = base64_encode($msg);
			$rv = $msg.static::MSG_SEPARATOR;
			if($this->getSocket()){
				$this->getSocket()->write($rv);
			}
		}
		
		return $rv;
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
	
	private function sslMsgCreatePasswordEncrypt($name, $data, $sslPasswordLocal = null, $sslPasswordPeer = null){
		$this->sslMsgCount++;
		#print __CLASS__.'->'.__FUNCTION__.': /'.$name.'/ '.$this->sslMsgCount."\n";
		
		$data = json_encode($data);
		$dataEnc = $this->sslPasswordEncrypt($data, $sslPasswordLocal, $sslPasswordPeer);
		
		if($dataEnc){
			$json = array(
				'name' => $name,
				'data' => $dataEnc,
			);
			return json_encode($json);
		}
		
		return null;
	}
	
	private function sslMsgDataPasswordDecrypt($dataEnc, $sslPasswordLocal = null, $sslPasswordPeer = null){
		$data = $this->sslPasswordDecrypt($dataEnc, $sslPasswordLocal, $sslPasswordPeer);
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
		
		if(isset($json['data']) && isset($json['sign'])){
			$data = base64_decode($json['data']);
			$sign = base64_decode($json['sign']);
			
			if(openssl_private_decrypt($data, $decrypted, $this->getSsl())){
				if(openssl_verify($decrypted, $sign, $this->getNode()->getSslKeyPub(), OPENSSL_ALGO_SHA1)){
					$rv = $decrypted;
					
					return $rv;
				}
			}
		}
		
		return null;
	}
	
	private function sslPasswordEncrypt($data, $sslPasswordLocal = null, $sslPasswordPeer = null){
		#$this->log('debug', 'SSL password encrypt: /'.$sslPasswordLocal.'/ /'.$sslPasswordPeer.'/');
		
		if($sslPasswordLocal === null){
			#$this->log('debug', 'SSL password encrypt: no local password set');
			$sslPasswordLocal = $this->sslPasswordLocal;
		}
		if($sslPasswordPeer === null){
			#$this->log('debug', 'SSL password encrypt: no peer password set');
			$sslPasswordPeer = $this->sslPasswordPeer;
		}
		
		if($sslPasswordLocal && $sslPasswordPeer){
			$password = $sslPasswordLocal.'_'.$sslPasswordPeer;
			#$this->log('debug', 'SSL password encrypt pwd: '.$password);
			
			if(openssl_sign($data, $sign, $this->getSsl(), OPENSSL_ALGO_SHA1)){
				$sign = base64_encode($sign);
				$data = base64_encode($data);
				
				$jsonStr = json_encode(array('data' => $data, 'sign' => $sign));
				$data = gzencode($jsonStr, 9);
				
				$iv = substr(hash('sha512', mt_rand(0, 999999), true), 0, 16);
				$data = openssl_encrypt($data, 'AES-256-CBC', $password, 0, $iv);
				if($data !== false){
					$iv = base64_encode($iv);
					
					$data = gzencode(json_encode(array('data' => $data, 'iv' => $iv)), 9);
					$rv = base64_encode($data);
					
					return $rv;
				}
			}
		}
		
		return null;
	}
	
	private function sslPasswordDecrypt($data, $sslPasswordLocal = null, $sslPasswordPeer = null){
		#$this->log('debug', 'SSL password decrypt: /'.$sslPasswordLocal.'/ /'.$sslPasswordPeer.'/');
		
		if($sslPasswordLocal === null){
			#$this->log('debug', 'SSL password decrypt: no local password set');
			$sslPasswordLocal = $this->sslPasswordLocal;
		}
		if($sslPasswordPeer === null){
			#$this->log('debug', 'SSL password decrypt: no peer password set');
			$sslPasswordPeer = $this->sslPasswordPeer;
		}
		
		if($sslPasswordLocal && $sslPasswordPeer){
			$password = $sslPasswordPeer.'_'.$sslPasswordLocal;
			#$this->log('debug', 'SSL password decrypt pwd: '.$password);
			
			$data = base64_decode($data);
			$json = json_decode(gzdecode($data), true);
			if(isset($json['data']) && isset($json['iv'])){
				$data = $json['data'];
				$iv = base64_decode($json['iv']);
				
				$data = openssl_decrypt($data, 'AES-256-CBC', $password, 0, $iv);
				if($data !== false){
					$json = json_decode(gzdecode($data), true);
					if(isset($json['data']) && isset($json['sign'])){
						$data = base64_decode($json['data']);
						$sign = base64_decode($json['sign']);
						
						if(openssl_verify($data, $sign, $this->getNode()->getSslKeyPub(), OPENSSL_ALGO_SHA1)){
							return $data;
						}
						else{
							$this->log('warning', 'sslPasswordDecrypt: openssl_verify failed');
						}
					}
					else{
						$this->log('warning', 'sslPasswordDecrypt: data or sign not set');
					}
				}
				else{
					$this->log('warning', 'sslPasswordDecrypt: openssl_decrypt failed');
					while($openSslErrorStr = openssl_error_string()){
						$this->log('error', 'SSL: '.$openSslErrorStr);
					}
				}
			}
			else{
				$this->log('warning', 'sslPasswordDecrypt: data or iv not set');
			}
		}
		else{
			$this->log('warning', 'sslPasswordDecrypt: no passwords set');
		}
		
		return null;
	}
	
	public function sendNoop(){
		return $this->dataSend($this->msgCreate('noop'));
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
		return $this->dataSend($this->msgCreate('hello', $data));
	}
	
	public function sendId(){
		if(!$this->getLocalNode()){
			throw new RuntimeException('localNode not set.');
		}
		
		$sslKeyPub = base64_encode($this->getLocalNode()->getSslKeyPub());
		
		$data = array(
			'release'   => $this->getSettings()->data['release'],
			'id'        => $this->getLocalNode()->getIdHexStr(),
			'port'      => $this->getLocalNode()->getUri()->getPort(),
			'sslKeyPub' => $sslKeyPub,
			'isChannel' => $this->getStatus('isChannelLocal'),
			#'hashcash'  => $this->hashcashMint(static::HASHCASH_BITS_MIN),
		);
		return $this->dataSend($this->msgCreate('id', $data));
	}
	
	private function sendIdOk(){
		return $this->dataSend($this->msgCreate('id_ok'));
	}
	
	public function sendNodeFind($nodeId, $distance = null, $nodesFoundIds = null, $useHashcash = true){
		if(!$this->getTable()){
			throw new RuntimeException('table not set.');
		}
		if($distance === null){
			$distance = 'ffffffff-ffff-4fff-bfff-ffffffffffff';
		}
		if($nodesFoundIds === null){
			$nodesFoundIds = array();
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
			'hashcash'  => '',
		);
		if($useHashcash){
			$data['hashcash'] = $this->hashcashMint(static::HASHCASH_BITS_MIN);
		}
		return $this->dataSend($this->msgCreate('node_find', $data));
	}
	
	public function sendNodeFound($rid, $nodes = array(), $useHashcash = true){
		if(!$this->getTable()){
			throw new RuntimeException('table not set.');
		}
		
		$nodesOut = array();
		foreach($nodes as $nodeId => $node){
			$nodesOut[] = array(
				'id' => $node->getIdHexStr(),
				'uri' => (string)$node->getUri(),
				'sslKeyPub' => base64_encode($node->getSslKeyPub()),
			);
		}
		
		$data = array(
			'rid'       => $rid,
			'nodes'     => $nodesOut,
			'hashcash'  => '',
		);
		if($useHashcash){
			$data['hashcash'] = $this->hashcashMint(static::HASHCASH_BITS_MAX);
		}
		return $this->dataSend($this->msgCreate('node_found', $data));
	}
	
	public function sendMsg(Msg $msg){
		$rid = (string)Uuid::uuid4();
		
		$this->requestAdd('msg', $rid, array(
			'msg' => $msg,
		));
		
		$data = array(
			'rid' => $rid,
			
			'version' => $msg->getVersion(),
			'id' => $msg->getId(),
			'srcNodeId' => $msg->getSrcNodeId(),
			'srcSslKeyPub' => base64_encode($msg->getSrcSslKeyPub()),
			'srcUserNickname' => $msg->getSrcUserNickname(),
			'dstNodeId' => $msg->getDstNodeId(),
			'body' => $msg->getBody(),
			'password' => $msg->getPassword(),
			'checksum' => $msg->getChecksum(),
			'relayCount' => (int)$msg->getRelayCount() + 1,
			'timeCreated' => (int)$msg->getTimeCreated(),
			'hashcash' => $this->hashcashMint(static::HASHCASH_BITS_MAX),
		);
		return $this->dataSend($this->msgCreate('msg', $data));
	}
	
	private function sendMsgResponse($rid, $status){
		$data = array(
			'rid' => $rid,
			'status' => (int)$status,
		);
		return $this->dataSend($this->msgCreate('msg_response', $data));
	}
	
	public function sendSslInit($useHashcash = true){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$this->log('debug', 'send SSL init');
		
		if($this->getStatus('hasSendSslInit')){
			$this->setStatus('hasSslInit', true);
			
			$this->log('debug', 'send SSL init: set has send');
			return '';
		}
		else{
			$this->setStatus('hasSendSslInit', true);
			
			$this->log('debug', 'send SSL init: create data');
			
			$data = array('hashcash' => '');
			if($useHashcash){
				$data['hashcash'] = $this->hashcashMint(static::HASHCASH_BITS_MIN);
			}
			return $this->dataSend($this->msgCreate('ssl_init', $data));
		}
	}
	
	private function sendSslInitResponse($code){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$data = array('code' => $code);
		
		return $this->dataSend($this->msgCreate('ssl_init_response', $data));
	}
	
	private function sendSslTest(){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$this->sslTestToken = (string)Uuid::uuid4();
		
		$data = array(
			'token' => $this->sslTestToken,
		);
		return $this->dataSend($this->sslMsgCreatePublicEncrypt('ssl_test', $data));
	}
	
	private function sendSslVerify($token){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$data = array(
			'token' => $token,
		);
		return $this->dataSend($this->sslMsgCreatePublicEncrypt('ssl_verify', $data));
	}
	
	private function genSslPassword(){
		$addr = $this->getIp().':'.$this->getPort();
		$password = hash('sha512', $addr.'_'.mt_rand(0, 999999));
		#$password = substr(hash('sha512', $addr.'_'.mt_rand(0, 999999)), 0, 3);
		
		return $password;
	}
	
	private function sendSslPasswordPut(){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$this->sslPasswordLocal = $this->genSslPassword();
		$this->sslPasswordTime = time();
		#$this->log('debug', 'SSL: local password: '.substr($this->sslPasswordLocal, 0, 20));
		
		$data = array(
			'password' => $this->sslPasswordLocal,
		);
		return $this->dataSend($this->sslMsgCreatePublicEncrypt('ssl_password_put', $data));
	}
	
	private function sendSslPasswordReput(){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$this->sslPasswordLocalNew = $this->genSslPassword();
		$this->sslPasswordTime = time();
		#$this->log('debug', 're-SSL: local password:     '.substr($this->sslPasswordLocal, 0, 20));
		#$this->log('debug', 're-SSL: local password new: '.substr($this->sslPasswordLocalNew, 0, 20));
		#$this->log('debug', 're-SSL: peer password:      '.substr($this->sslPasswordPeer, 0, 20));
		#$this->log('debug', 're-SSL: peer password new:  '.substr($this->sslPasswordPeerNew, 0, 20));
		
		$data = array(
			'password' => $this->sslPasswordLocalNew,
		);
		return $this->dataSend($this->sslMsgCreatePasswordEncrypt('ssl_password_reput', $data));
	}
	
	private function sendSslPasswordTest(){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$this->sslPasswordToken = (string)Uuid::uuid4();
		
		$data = array(
			'token' => $this->sslPasswordToken,
		);
		return $this->dataSend($this->sslMsgCreatePasswordEncrypt('ssl_password_test', $data));
	}
	
	private function sendSslPasswordRetest(){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$this->sslPasswordToken = (string)Uuid::uuid4();
		#$this->log('debug', 're-SSL token: '.substr($this->sslPasswordToken, 0, 20));
		
		$data = array(
			'token' => $this->sslPasswordToken,
		);
		return $this->dataSend($this->sslMsgCreatePasswordEncrypt('ssl_password_retest', $data, $this->sslPasswordLocalNew, $this->sslPasswordPeerNew));
	}
	
	private function sendSslPasswordVerify($token){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		$token = hash('sha512', $token.'_'.$this->getLocalNode()->getSslKeyPubFingerprint());
		
		$data = array(
			'token' => $token,
		);
		return $this->dataSend($this->sslMsgCreatePasswordEncrypt('ssl_password_verify', $data));
	}
	
	private function sendSslPasswordReverify($token){
		if(!$this->getSsl()){
			throw new RuntimeException('ssl not set.');
		}
		
		#$this->log('debug', 're-SSL peer token: '.substr($token, 0, 20));
		$token = hash('sha512', $token.'_'.$this->getLocalNode()->getSslKeyPubFingerprint());
		#$this->log('debug', 're-SSL local token: '.substr($token, 0, 20));
		
		$data = array(
			'token' => $token,
		);
		return $this->dataSend($this->sslMsgCreatePasswordEncrypt('ssl_password_reverify', $data, $this->sslPasswordLocalNew, $this->sslPasswordPeerNew));
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
		return $this->dataSend($this->msgCreate('ssl_key_pub_get', $data));
	}
	
	private function sendSslKeyPubPut($rid,
		$nodeId = null, $nodeIp = null, $nodePort = null, $nodeSslKeyPubFingerprint = null, $nodeSslKeyPub = null){
		
		$data = array(
			'rid' => $rid,
			'nodeId' => $nodeId,
			'nodeIp' => $nodeIp,
			'nodePort' => $nodePort,
			'nodeSslKeyPubFingerprint' => $nodeSslKeyPubFingerprint,
			'nodeSslKeyPub' => base64_encode($nodeSslKeyPub),
		);
		return $this->dataSend($this->msgCreate('ssl_key_pub_put', $data));
	}
	
	public function sendTalkRequest($userNickname){
		$rid = (string)Uuid::uuid4();
		
		$this->requestAdd('talk_request', $rid, array(
			'userNickname' => $userNickname,
		));
		
		$data = array(
			'rid' => $rid,
			'userNickname' => $userNickname,
			'hashcash' => $this->hashcashMint(static::HASHCASH_BITS_MAX),
		);
		return $this->dataSend($this->sslMsgCreatePasswordEncrypt('talk_request', $data));
	}
	
	public function sendTalkResponse($rid, $status, $userNickname = ''){
		$data = array(
			'rid' => $rid,
			'status' => (int)$status,
			'userNickname' => $userNickname,
		);
		return $this->dataSend($this->sslMsgCreatePasswordEncrypt('talk_response', $data));
	}
	
	public function sendTalkMsg($rid, $userNickname, $text, $ignore){
		$data = array(
			'rid' => $rid,
			'userNickname' => $userNickname,
			'text' => $text,
			'ignore' => $ignore,
		);
		return $this->dataSend($this->sslMsgCreatePasswordEncrypt('talk_msg', $data));
	}
	
	public function sendTalkUserNicknameChange($userNicknameOld, $userNicknameNew){
		$data = array(
			'userNicknameOld' => $userNicknameOld,
			'userNicknameNew' => $userNicknameNew,
		);
		return $this->dataSend($this->sslMsgCreatePasswordEncrypt('talk_user_nickname_change', $data));
	}
	
	public function sendTalkClose($rid, $userNickname){
		$data = array(
			'rid' => $rid,
			'userNickname' => $userNickname,
		);
		return $this->dataSend($this->sslMsgCreatePasswordEncrypt('talk_close', $data));
	}
	
	public function sendPing($id = ''){
		$this->pingTime = time();
		
		$data = array(
			'id' => $id,
		);
		return $this->dataSend($this->msgCreate('ping', $data));
	}
	
	public function sendPong($id = ''){
		$data = array(
			'id' => $id,
		);
		return $this->dataSend($this->msgCreate('pong', $data));
	}
	
	private function sendError($errorCode = 999, $msgName = ''){
		$errors = array(
			// 1000-1999: ID
			1000 => 'You need to identify',
			1010 => 'You already identified',
			1020 => 'You are using my ID',
			
			// 2000-3999: SSL
			2000 => 'SSL: no public key found',
			2010 => 'SSL: you need a key with minimum length of '.Node::SSL_KEY_LEN_MIN.' bits',
			2020 => 'SSL: public key too short',
			2030 => 'SSL: public key changed since last handshake',
			2035 => 'SSL: public key already in table with different node id',
			2040 => 'SSL: invalid key',
			2050 => 'SSL: you already initialized ssl',
			2060 => 'SSL: you need to initialize ssl',
			2070 => 'SSL: decryption failed',
			2080 => 'SSL: verification failed',
			2090 => 'SSL: password verification failed',
			3090 => 'SSL: invalid setup',
			3100 => 'SSL: init failed',
			
			// 4000-4999: Hashcash
			4000 => 'Hashcash: verification failed',
			
			// 9000-9999: Misc
			9000 => 'Invalid data',
			9010 => 'Invalid setup',
			9020 => 'Unknown command',
			9030 => 'Command not implemented',
			9099 => 'Unknown error',
		);
		
		if(!isset($errors[$errorCode])){
			throw new RuntimeException('Error '.$errorCode.' not defined.');
		}
		
		$data = array(
			'code'   => $errorCode,
			'msg' => $errors[$errorCode],
			'name' => $msgName,
		);
		return $this->dataSend($this->msgCreate('error', $data));
	}
	
	public function sendQuit(){
		return $this->dataSend($this->msgCreate('quit'));
	}
	
	public function shutdown(){
		if(!$this->getStatus('hasShutdown')){
			$this->setStatus('hasShutdown', true);
			
			if($this->getSocket()){
				$this->getSocket()->shutdown();
				$this->getSocket()->close();
			}
			
			if($this->ssl){
				openssl_free_key($this->ssl);
			}
		}
	}
	
	private function consoleMsgAdd($msgText = '', $showDate = true, $printPs1 = true, $clearLine = false){
		if($this->getServer()){
			$this->getServer()->consoleMsgAdd($msgText, $showDate, $printPs1, $clearLine);
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
			
			$this->getServer()->getKernel()->getIpcConsoleConnection()
					->execAsync('talkMsgAdd', array($rid, $userNickname, $text));
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
