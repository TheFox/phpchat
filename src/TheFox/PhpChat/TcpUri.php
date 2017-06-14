<?php

namespace TheFox\PhpChat;

use Zend\Uri\Uri;

class TcpUri extends Uri
{
    protected static $validSchemes = ['tcp'];

    public function __sleep()
    {
        return ['scheme', 'host', 'port'];
    }
}
