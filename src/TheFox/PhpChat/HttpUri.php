<?php

namespace TheFox\PhpChat;

use Zend\Uri\Http;

class HttpUri extends Http{
	
	public function __sleep(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#ve($this);
		
		return array('scheme', 'host', 'port', 'path');
	}
	
}
