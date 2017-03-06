<?php

namespace TheFox\PhpChat;

use Zend\Uri\Uri;

class TcpUri extends Uri{
	
	protected static $validSchemes = array('tcp');
	
	public function __sleep(){
		return array('scheme', 'host', 'port');
	}
	
}
