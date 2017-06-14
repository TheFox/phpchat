<?php

namespace TheFox\Ipc;

/**
 * @codeCoverageIgnore
 */
class ServerConnection extends Connection
{
    public function __construct()
    {
        $this->isServer(true);
    }
}
