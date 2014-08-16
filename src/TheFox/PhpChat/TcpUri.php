<?php

namespace TheFox\PhpChat;

use Zend\Uri\Uri;

class TcpUri extends Uri{
	
	protected static $validSchemes = array('tcp');

	/*protected static $defaultPorts = array(
		'http'  => 80,
		'https' => 443,
	);*/

	#protected $validHostTypes = self::HOST_DNS_OR_IPV4_OR_IPV6_OR_REGNAME;

	#protected $user;
	#protected $password;

	/*public function isValid(){
		return parent::isValid();
	}*/

	/*public function getUser(){
	if (null !== $this->user) {
	return $this->user;
	}

	$this->parseUserInfo();
	return $this->user;
	}

	public function getPassword(){
	if (null !== $this->password) {
	return $this->password;
	}

	$this->parseUserInfo();
	return $this->password;
	}

	public function setUser($user){
	$this->user = $user;
	return $this;
	}

	public function setPassword($password){
	$this->password = $password;
	return $this;
	}*/

	/*public static function validateHost($host, $allowed = self::HOST_DNS_OR_IPV4_OR_IPV6){
		return parent::validateHost($host, $allowed);
	}*/

	/*protected function parseUserInfo(){
		// No user information? we're done
		if (null === $this->userInfo) {
			return;
		}

		// If no ':' separator, we only have a username
		if (false === strpos($this->userInfo, ':')) {
			$this->setUser($this->userInfo);
			return;
		}

		// Split on the ':', and set both user and password
		list($user, $password) = explode(':', $this->userInfo, 2);
		$this->setUser($user);
		$this->setPassword($password);
	}*/

	/*public function getPort(){
		if (empty($this->port)) {
			if (array_key_exists($this->scheme, static::$defaultPorts)) {
				return static::$defaultPorts[$this->scheme];
			}
		}
		return $this->port;
	}
	
	public function parse($uri){
		parent::parse($uri);
		
		if (empty($this->path)) {
			$this->path = '/';
		}

		return $this;
	}
	*/
}
