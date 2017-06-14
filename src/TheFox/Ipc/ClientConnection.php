<?php

namespace TheFox\Ipc;

/**
 * @codeCoverageIgnore
 */
class ClientConnection extends Connection{
	
	public function __construct(){
		$this->isServer(false);
	}
	
}
