<?php

namespace TheFox\PhpChat;

use Zend\Uri\UriFactory;

use TheFox\Network\AbstractSocket;

class TcpClient extends Client{
	
	const PING_TTL = 25;
	const PONG_TTL = 300;
	
	private $socket = null;
	private $recvBufferTmp = '';
	
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
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
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
	
	public function dataRecv($data = null){
		$dataRecvReturnValue = '';
		
		// @codeCoverageIgnoreStart
		if($data === null && $this->getSocket()){
			$data = $this->getSocket()->read();
		}
		// @codeCoverageIgnoreEnd
		
		$this->incTrafficIn(strlen($data));
		
		do{
			$separatorPos = strpos($data, static::MSG_SEPARATOR);
			if($separatorPos === false){
				$this->recvBufferTmp .= $data;
				$data = '';
			}
			else{
				$msg = $this->recvBufferTmp.substr($data, 0, $separatorPos);
				$this->recvBufferTmp = '';
				
				$msg = base64_decode($msg);
				
				$msgHandleReturnValue = $this->msgHandleRaw($msg);
				$dataRecvReturnValue .= $msgHandleReturnValue;
				
				$data = substr($data, $separatorPos + 1);
			}
		}
		while($data);
		
		return $dataRecvReturnValue;
	}
	
	public function dataSend($data){
		$msg = '';
		if($data){
			$data = base64_encode($data);
			$this->incTrafficOut(strlen($data) + static::MSG_SEPARATOR_LEN);
			$msg = $data.static::MSG_SEPARATOR;
			
			// @codeCoverageIgnoreStart
			if($this->getSocket()){
				$this->getSocket()->write($msg);
			}
			// @codeCoverageIgnoreEnd
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
