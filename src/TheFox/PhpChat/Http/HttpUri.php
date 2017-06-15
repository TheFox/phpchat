<?php

namespace TheFox\PhpChat\Http;

use Zend\Uri\Http;

class HttpUri extends Http
{
    public function __sleep()
    {
        return ['scheme', 'host', 'port', 'path'];
    }
}
