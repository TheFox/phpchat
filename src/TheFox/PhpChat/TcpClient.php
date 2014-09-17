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
	
	public function dataRecv($data = null){
		#fwrite(STDOUT, $this->getId().' dataRecv'."\n");
		
		$dataRecvReturnValue = '';
		if($data === null && $this->getSocket()){
			$data = $this->getSocket()->read();
		}
		
		$this->incTrafficIn(strlen($data));
		#fwrite(STDOUT, $this->getId().' dataRecv data: /'.$data.'/'."\n");
		#fwrite(STDOUT, $this->getId().'  > len: '.strlen($data).''."\n");
		
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
				#fwrite(STDOUT, $this->getId().' dataRecv msg: /'.$msg.'/'."\n");
				
				#$dataRecvReturnValue .= $this->msgHandle($msg);
				$msgHandleReturnValue = $this->msgHandleRaw($msg);
				$dataRecvReturnValue .= $msgHandleReturnValue;
				#$dataRecvReturnValue = array_merge($dataRecvReturnValue, $msgHandleReturnValue);
				#fwrite(STDOUT, 'msgHandle rv:         /'.$msgHandleReturnValue.'/'."\n");
				#fwrite(STDOUT, 'msgHandle rv:         /'.base64_decode($msgHandleReturnValue).'/'."\n");
				
				$data = substr($data, $separatorPos + 1);
			}
		}
		while($data);
		
		#fwrite(STDOUT, 'dataRecv: /'.base64_decode($dataRecvReturnValue).'/'."\n");
		#fwrite(STDOUT, 'dataRecv rv: /'.$dataRecvReturnValue.'/'."\n");
		#fwrite(STDOUT, 'dataRecv rv: /'.preg_split('/\n/', substr($dataRecvReturnValue, 0, -1)).'/'."\n");
		
		return $dataRecvReturnValue;
		#return join(static::MSG_SEPARATOR, $dataRecvReturnValue);
		#return join(',', $dataRecvReturnValue);
	}
	
	public function dataSend($data){
		#fwrite(STDOUT, $this->getId().' dataSend'."\n");
		#fwrite(STDOUT, $this->getId().' dataSend data: /'.$data.'/'."\n");
		#fwrite(STDOUT, $this->getId().' <  len: '.strlen($data).''."\n");
		
		$msg = '';
		
		if($data){
			$data = base64_encode($data);
			$this->incTrafficOut(strlen($data) + static::MSG_SEPARATOR_LEN);
			$msg = $data.static::MSG_SEPARATOR;
			if($this->getSocket()){
				$this->getSocket()->write($msg);
			}
		}
		
		return $msg;
	}
	
	public function shutdown(){
		if(!$this->getStatus('hasShutdown')){
			$this->setStatus('hasShutdown', true);
			$this->logColor('debug', $this->getUri().' shutdown', 'white', 'black');
			
			if($this->getSocket()){
				$this->getSocket()->shutdown();
				$this->getSocket()->close();
			}
			
			if($this->getSsl()){
				openssl_free_key($this->getSsl());
			}
		}
	}
	
}
