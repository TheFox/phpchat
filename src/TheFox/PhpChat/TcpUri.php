<?php

namespace TheFox\PhpChat;

use Zend\Uri\Uri;

class TcpUri extends Uri{
	
	protected static $validSchemes = array('tcp');
	
}
