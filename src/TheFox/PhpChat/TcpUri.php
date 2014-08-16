<?php

namespace TheFox\PhpChat;

use Zend\Uri\Uri;

class TcpUri extends Uri{
	
	protected static $validSchemes = array('tcp');
	
	public function __sleep(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#ve($this);
		
		return array('scheme', 'host', 'port', 'path');
	}
	
}
