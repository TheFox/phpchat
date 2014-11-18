<?php

namespace TheFox\Ipc;

/**
 * @codeCoverageIgnore
 */
class ServerConnection extends Connection{
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->isServer(true);
	}
	
}
