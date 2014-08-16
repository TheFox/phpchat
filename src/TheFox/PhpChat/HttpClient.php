<?php

namespace TheFox\PhpChat;

use Zend\Uri\UriFactory;

class HttpClient extends Client{
	
	const TTL = 30;
	
	private $timeoutTime = 0;
	
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
		fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__."\n");
	}
	
	public function shutdown(){
		if(!$this->getStatus('hasShutdown')){
			$this->setStatus('hasShutdown', true);
			$this->log('debug', $this->getUri().' shutdown');
			
		}
	}
	
}
