<?php

namespace TheFox\PhpChat;

use Zend\Uri\Http;

class HttpUri extends Http{
	
	public function __sleep(){
		return array('scheme', 'host', 'port', 'path');
	}
	
}
