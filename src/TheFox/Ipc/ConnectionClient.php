<?php

namespace TheFox\Ipc;

class ConnectionClient extends Connection{
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->isServer(false);
	}
	
}
