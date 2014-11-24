<?php

namespace TheFox\PhpChat;

use Zend\Uri\UriFactory;

class HttpClient extends Client{
	
	const TTL = 30;
	
	private $timeoutTime = 0;
	private $curl;
	
	public function __construct(){
		parent::__construct();
		
		$this->uri = UriFactory::factory('http://');
	}
	
	public function run(){
		#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.': '.$this->getUri()."\n");
		
		$this->checkTimeout();
	}
	
	private function checkTimeout(){
		if(!$this->timeoutTime){
			fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.': set time'."\n");
			$this->timeoutTime = time();
		}
		#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.': check '.( time() - static::TTL - $this->timeoutTime )."\n");
		if($this->timeoutTime < time() - static::TTL){
			fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.': shutdown'."\n");
			$this->shutdown();
		}
	}
	
	public function dataRecv($data = null){
		fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__."\n");
	}
	
	public function dataSend($msg){
		$url = (string)$this->getUri();
		fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.' url: /'.$url.'/'."\n");
		fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.' msg: /'.$msg.'/'."\n");
		
		$data = array(
			'data' => base64_encode($msg),
		);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_NOPROGRESS, true);
		curl_setopt($curl, CURLOPT_VERBOSE, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_TIMEOUT, 5);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		
		$this->curl = $curl;
	}
	
	public function sendHello(){
		$data = array(
			'ip' => $this->getUri()->getHost(),
		);
		return $this->dataSend($this->msgCreate('hello', $data));
	}
	
	public function sendId(){
		return '';
	}
	
	public function sendIdOk(){
		return '';
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
		return '';
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
		return '';
	}
	
	public function sendSslInit($useHashcash = true){
		return '';
	}
	
	private function sendSslInitResponse($code){
		return '';
	}
	
	private function sendSslTest(){
		return '';
	}
	
	private function sendSslVerify($token){
		return '';
	}
	
	private function sendSslPasswordPut(){
		return '';
	}
	
	private function sendSslPasswordReput(){
		return '';
	}
	
	private function sendSslPasswordTest(){
		return '';
	}
	
	private function sendSslPasswordRetest(){
		return '';
	}
	
	private function sendSslPasswordVerify($token){
		return '';
	}
	
	private function sendSslPasswordReverify($token){
		return '';
	}
	
	public function sendSslKeyPubGet($nodeSslKeyPubFingerprint){
		return '';
	}
	
	private function sendSslKeyPubPut($rid,
		$nodeId = null, $nodeIp = null, $nodePort = null, $nodeSslKeyPubFingerprint = null, $nodeSslKeyPub = null){
		return '';
	}
	
	public function sendTalkRequest($userNickname){
		return '';
	}
	
	public function sendTalkResponse($rid, $status, $userNickname = ''){
		return '';
	}
	
	public function sendTalkMsg($rid, $userNickname, $text, $ignore){
		return '';
	}
	
	public function sendTalkUserNicknameChange($userNicknameOld, $userNicknameNew){
		return '';
	}
	
	public function sendTalkClose($rid, $userNickname){
		return '';
	}
	
	public function sendPing($id = ''){
		return '';
	}
	
	public function sendPong($id = ''){
		return '';
	}
	
	private function sendError($errorCode = 999, $msgName = ''){
		return '';
	}
	
	public function sendQuit(){
		return '';
	}
	
	public function shutdown(){
		if(!$this->getStatus('hasShutdown')){
			$this->setStatus('hasShutdown', true);
			$this->log('debug', $this->getUri().' shutdown');
			
		}
	}
	
}
