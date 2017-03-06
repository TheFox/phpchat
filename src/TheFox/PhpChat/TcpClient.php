<?php

namespace TheFox\PhpChat;

use Zend\Uri\UriFactory;
use TheFox\Network\AbstractSocket;

class TcpClient extends Client{
	
	const PING_TTL = 25;
	const PONG_TTL = 300;
	
	private $socket = null;
	private $tcpRecvBufferTmp = '';
	
	public function __construct(){
		parent::__construct();
		
		$this->uri = UriFactory::factory('tcp://');
	}
	
	public function setSocket(AbstractSocket $socket){
		$this->socket = $socket;
		$this->socket->getPeerName($ip, $port);
		$this->setUri('tcp://'.$ip.':'.$port);
	}
	
	public function getSocket(){
		return $this->socket;
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function run(){
		$this->checkPingSend();
		$this->checkPongTimeout();
		$this->checkActions();
		$this->checkSslPasswordTimeout();
	}
	
	private function checkPingSend(){
		if($this->pingTime < time() - static::PING_TTL){
			$this->sendPing();
		}
	}
	
	private function checkPongTimeout(){
		if(!$this->pongTime){
			$this->pongTime = time();
		}
		if($this->pongTime < time() - static::PONG_TTL){
			$this->sendQuit();
			$this->shutdown();
		}
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function dataRecv($data = null){
		// @codeCoverageIgnoreStart
		if($data === null && $this->getSocket()){
			$data = $this->getSocket()->read();
		}
		// @codeCoverageIgnoreEnd
		
		$this->incTrafficIn(strlen($data));
		
		$dataRecvReturnValue = '';
		do{
			$separatorPos = strpos($data, static::MSG_SEPARATOR);
			if($separatorPos === false){
				$this->tcpRecvBufferTmp .= $data;
				$data = '';
			}
			else{
				$msg = $this->tcpRecvBufferTmp.substr($data, 0, $separatorPos);
				$this->tcpRecvBufferTmp = '';
				
				$msg = base64_decode($msg);
				
				$msgHandleReturnValue = $this->msgHandleRaw($msg);
				$dataRecvReturnValue .= $msgHandleReturnValue;
				
				$data = substr($data, $separatorPos + 1);
			}
		}
		while($data);
		
		return $dataRecvReturnValue;
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function dataSend($data){
		$msg = parent::dataSend($data);
		if($msg && $this->getSocket()){
			$this->getSocket()->write($msg);
		}
		return $msg;
	}
	
	public function shutdown(){
		if(!$this->getStatus('hasShutdown')){
			$this->setStatus('hasShutdown', true);
			$this->logColor('debug', $this->getUri().' shutdown', 'white', 'black');
			
			// @codeCoverageIgnoreStart
			if($this->getSocket()){
				$this->getSocket()->shutdown();
				$this->getSocket()->close();
			}
			// @codeCoverageIgnoreEnd
			
			if($this->getSsl()){
				openssl_free_key($this->getSsl());
			}
		}
	}
	
}
