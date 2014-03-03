<?php

namespace TheFox\Ipc;

class ConnectionServer extends Connection{
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->isServer(true);
	}
	
}
