<?php

namespace TheFox\PhpChat;

use Zend\Uri\Http;

class HttpUri extends Http
{
    public function __sleep()
    {
        return ['scheme', 'host', 'port', 'path'];
    }
}
