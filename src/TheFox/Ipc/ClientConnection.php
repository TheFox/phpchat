<?php

namespace TheFox\Ipc;

/**
 * @codeCoverageIgnore
 */
class ClientConnection extends Connection{
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->isServer(false);
	}
	
}
